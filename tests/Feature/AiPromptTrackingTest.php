<?php

namespace Tests\Feature;

use App\Models\AiPromptRun;
use App\Models\AiTrackedPrompt;
use App\Models\AiTrackingProfile;
use App\Models\Competitor;
use App\Models\SemrushReport;
use App\Models\Tenant;
use App\Services\AiTracking\AiEngineClient;
use App\Services\AiTracking\AnswerAnalyzer;
use App\Services\AiTracking\PromptTracker;
use App\Services\AiTracking\TrackerReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiPromptTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        config([
            'services.ai_tracking.openai' => ['key' => 'test-openai', 'model' => 'gpt-test'],
            'services.ai_tracking.gemini' => ['key' => 'test-gemini', 'model' => 'gemini-test'],
            'services.ai_tracking.perplexity' => ['key' => 'test-pplx', 'model' => 'sonar'],
            'services.ai_tracking.sentiment' => 'off',
        ]);
    }

    private function fakeEngines(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response(['model' => 'gpt-test', 'output' => [
                ['type' => 'web_search_call', 'status' => 'completed'],
                ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => 'Top picks: Rashof sidr honey, then Al Shifa honey.', 'annotations' => [
                    ['type' => 'url_citation', 'url' => 'https://www.alshifahoney.com/en/sidr', 'title' => 'Sidr'],
                    ['type' => 'url_citation', 'url' => 'https://rashof.com/ar', 'title' => 'Rashof'],
                ]]]],
            ]]),
            'generativelanguage.googleapis.com/*' => Http::response(['modelVersion' => 'gemini-test', 'candidates' => [[
                'content' => ['parts' => [['text' => 'جرّب عسل الشفاء أو منتجات أخرى.']]],
                'groundingMetadata' => ['groundingChunks' => [['web' => ['uri' => 'https://vertexaisearch.cloud.google.com/grounding-api-redirect/abc', 'title' => 'noon.com']]]],
            ]]]),
            'api.perplexity.ai/*' => Http::response(['model' => 'sonar', 'choices' => [['message' => ['content' => 'Rashof is popular in Riyadh.']]],
                'search_results' => [['title' => 'Rashof', 'url' => 'https://rashof.com/']], 'citations' => ['https://rashof.com/', 'https://amazon.sa/x']]),
        ]);
    }

    private function brands(): array
    {
        return [
            ['name' => 'Al Shifa', 'domain' => 'alshifahoney.com', 'aliases' => ['عسل الشفاء'], 'client' => true],
            ['name' => 'Rashof', 'domain' => 'rashof.com', 'aliases' => [], 'client' => false],
            ['name' => 'Asalbarri', 'domain' => 'asalbarri.sa', 'aliases' => [], 'client' => false],
        ];
    }

    public function test_analyzer_finds_rank_citations_aliases_and_whole_words(): void
    {
        $a = new AnswerAnalyzer;
        $r = $a->analyze('Rashof first, then AlShifa.', [['domain' => 'shop.alshifahoney.com']], $this->brands());
        $this->assertSame([true, 2, true], [$r['mentioned'], $r['rank'], $r['cited']]);
        $this->assertSame(1, collect($r['brands'])->firstWhere('name', 'Rashof')['rank']);

        $ar = $a->analyze('أنصح بـ عسل الشفاء', [], $this->brands());
        $this->assertSame([true, 1, false], [$ar['mentioned'], $ar['rank'], $ar['cited']]);

        $none = $a->analyze('Rashoffer and Al Shifabad are unrelated.', [['domain' => 'notalshifahoney.com']], $this->brands());
        $this->assertSame([false, null, false], [$none['mentioned'], $none['rank'], $none['cited']]);
        $this->assertNull(collect($none['brands'])->firstWhere('name', 'Rashof')['rank']);
    }

    public function test_each_engine_response_is_parsed_into_text_and_sources(): void
    {
        $this->fakeEngines();
        $c = new AiEngineClient;

        $gpt = $c->ask('chatgpt', 'best honey', 'sa');
        $this->assertStringContainsString('Al Shifa', $gpt['text']);
        $this->assertSame(['alshifahoney.com', 'rashof.com'], array_column($gpt['sources'], 'domain'));

        $gem = $c->ask('gemini', 'best honey');
        $this->assertSame('noon.com', $gem['sources'][0]['domain']);

        $px = $c->ask('perplexity', 'best honey', 'sa');
        $this->assertSame(['rashof.com', 'amazon.sa'], array_column($px['sources'], 'domain'));

        Http::assertSent(fn ($req) => str_contains($req->url(), 'openai') && $req['tools'][0]['type'] === 'web_search' && $req['tools'][0]['user_location']['country'] === 'SA');
        Http::assertSent(fn ($req) => str_contains($req->url(), 'generativelanguage') && $req->hasHeader('x-goog-api-key', 'test-gemini') && ! str_contains($req->url(), 'key='));
    }

    public function test_weekly_run_stores_results_skips_repeats_and_records_errors(): void
    {
        $this->fakeEngines();
        $t = Tenant::factory()->create(['name' => 'Al Shifa']);
        Competitor::create(['tenant_id' => $t->id, 'name' => 'Rashof', 'website' => 'https://rashof.com/ar']);
        $profile = AiTrackingProfile::create(['tenant_id' => $t->id, 'domain' => 'alshifahoney.com', 'aliases' => ['عسل الشفاء'], 'engines' => ['chatgpt', 'gemini', 'perplexity']]);
        AiTrackedPrompt::create(['tenant_id' => $t->id, 'prompt' => 'best sidr honey', 'prompt_hash' => AiTrackedPrompt::hashOf('best sidr honey'), 'country' => 'sa']);

        $stats = app(PromptTracker::class)->run($profile);
        $this->assertSame(['asked' => 3, 'skipped' => 0, 'failed' => 0], $stats);
        $runs = AiPromptRun::all()->keyBy('engine');
        $this->assertSame([true, 2, true], [$runs['chatgpt']->brand_mentioned, $runs['chatgpt']->brand_rank, $runs['chatgpt']->brand_cited]);
        $this->assertSame([true, 1, false], [$runs['gemini']->brand_mentioned, $runs['gemini']->brand_rank, $runs['gemini']->brand_cited]);
        $this->assertSame([false, null], [$runs['perplexity']->brand_mentioned, $runs['perplexity']->brand_rank]);

        $this->assertSame(['asked' => 0, 'skipped' => 3, 'failed' => 0], app(PromptTracker::class)->run($profile));

        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['api.perplexity.ai/*' => Http::response(['error' => 'bad key'], 401)]);
        $this->assertSame(['asked' => 0, 'skipped' => 0, 'failed' => 1], app(PromptTracker::class)->run($profile, ['perplexity'], true));
        $this->assertSame('error', AiPromptRun::where('engine', 'perplexity')->value('status'));

        // Engines without a key are skipped.
        config(['services.ai_tracking.gemini.key' => null]);
        $this->assertSame(['chatgpt', 'perplexity'], app(PromptTracker::class)->enginesFor($profile));
    }

    public function test_seed_copies_one_semrush_prompt_per_topic_first(): void
    {
        $t = Tenant::factory()->create();
        SemrushReport::create(['tenant_id' => $t->id, 'domain' => 'client.com', 'is_client' => true, 'report_month' => '2026-11', 'captured_at' => '2026-11-10', 'topics' => [], 'source_files' => [], 'prompts' => [
            ['prompt' => 'A1', 'topic_name' => 'A', 'topic_volume' => 100, 'country' => 'sa'],
            ['prompt' => 'A2', 'topic_name' => 'A', 'topic_volume' => 100, 'country' => 'sa'],
            ['prompt' => 'B1', 'topic_name' => 'B', 'topic_volume' => 50, 'country' => 'ae'],
            ['prompt' => 'a1 ', 'topic_name' => 'A', 'topic_volume' => 100, 'country' => 'sa'],
        ]]);
        $this->assertSame(2, app(PromptTracker::class)->seedFromSemrush($t->id, 2));
        $this->assertSame(['A1', 'B1'], AiTrackedPrompt::orderBy('id')->pluck('prompt')->all());
        $this->assertSame('client.com', AiTrackingProfile::where('tenant_id', $t->id)->value('domain'));
        $this->assertSame(1, app(PromptTracker::class)->seedFromSemrush($t->id, 3));
    }

    public function test_report_builds_weekly_kpis_share_of_voice_sources_and_gaps(): void
    {
        $b = fn ($client, $rival) => [['name' => 'Brand', 'client' => true, 'rank' => $client, 'cited' => false], ['name' => 'Rival', 'client' => false, 'rank' => $rival, 'cited' => false]];
        $runs = [
            // previous week: 1 of 2 answers name the brand
            ['prompt' => 1, 'engine' => 'chatgpt', 'run_on' => '2026-11-03', 'mentioned' => true, 'rank' => 2, 'cited' => false, 'brands' => $b(2, 1), 'sources' => [], 'sentiment' => null],
            ['prompt' => 2, 'engine' => 'chatgpt', 'run_on' => '2026-11-03', 'mentioned' => false, 'rank' => null, 'cited' => false, 'brands' => $b(null, 1), 'sources' => [], 'sentiment' => null],
            // this week: 3 of 4 name the brand, 1 cites it
            ['prompt' => 1, 'engine' => 'chatgpt', 'run_on' => '2026-11-10', 'mentioned' => true, 'rank' => 1, 'cited' => true, 'brands' => $b(1, 2), 'sources' => [['domain' => 'brand.com'], ['domain' => 'noon.com']], 'sentiment' => 'positive'],
            ['prompt' => 1, 'engine' => 'gemini', 'run_on' => '2026-11-10', 'mentioned' => true, 'rank' => 1, 'cited' => false, 'brands' => $b(1, null), 'sources' => [['domain' => 'noon.com']], 'sentiment' => 'neutral'],
            ['prompt' => 2, 'engine' => 'chatgpt', 'run_on' => '2026-11-10', 'mentioned' => true, 'rank' => 2, 'cited' => false, 'brands' => $b(2, 1), 'sources' => [], 'sentiment' => null],
            ['prompt' => 3, 'engine' => 'gemini', 'run_on' => '2026-11-11', 'mentioned' => false, 'rank' => null, 'cited' => false, 'brands' => $b(null, 1), 'sources' => [], 'sentiment' => null],
        ];
        $prompts = [1 => ['prompt' => 'p1', 'volume' => 10], 2 => ['prompt' => 'p2', 'volume' => 20], 3 => ['prompt' => 'p3', 'volume' => 30]];
        $r = TrackerReport::build($runs, $prompts, 'brand.com');

        $this->assertTrue($r['available']);
        $this->assertSame('2026-11-09', $r['week']);
        $this->assertEqualsWithDelta(75.0, $r['kpis']['visibility'], 0.01);
        $this->assertEqualsWithDelta(25.0, $r['kpis']['citation'], 0.01);
        $this->assertEqualsWithDelta(4 / 3, $r['kpis']['rank'], 0.01);
        $this->assertEqualsWithDelta(50.0, $r['kpis']['sov'], 0.01);
        $this->assertEqualsWithDelta(25.0, $r['deltas']['visibility'], 0.01);
        $this->assertSame([['Brand', 3], ['Rival', 3]], $r['sovPie']);
        $this->assertSame(['noon.com', 2, false], [$r['sources'][0]['l'], $r['sources'][0]['v'], $r['sources'][0]['own']]);
        $this->assertTrue($r['sources'][1]['own']);
        $this->assertSame([3], array_column($r['gaps'], 'id'));
        $this->assertSame(['positive' => 1, 'neutral' => 1, 'negative' => 0], $r['sentiment']);
        $this->assertSame(['ChatGPT', 'Gemini'], array_values($r['engineLabels']));
        $this->assertFalse(TrackerReport::build([], [], null)['available']);
    }
}
