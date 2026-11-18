<?php

namespace App\Services\AiTracking;

use App\Models\AiPromptRun;
use App\Models\AiTrackedPrompt;
use App\Models\AiTrackingProfile;
use App\Models\Competitor;
use App\Models\SemrushReport;
use App\Models\Tenant;
use App\Services\OllamaService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Runs a client's tracked prompts through each AI assistant once a week and
 * stores who was named, in what order, and which sites were cited.
 */
class PromptTracker
{
    public function __construct(private AiEngineClient $client, private AnswerAnalyzer $analyzer) {}

    /** Client first, then the competitors from the Competitors tab. */
    public function brandsFor(AiTrackingProfile $profile): array
    {
        $tenant = Tenant::find($profile->tenant_id);
        $brands = [['name' => $tenant?->name ?? 'Client', 'domain' => $profile->domain, 'aliases' => $profile->aliases ?? [], 'client' => true]];
        foreach (Competitor::where('tenant_id', $profile->tenant_id)->orderBy('sort_order')->orderBy('name')->get() as $c) {
            $brands[] = ['name' => $c->name, 'domain' => $c->website ? AiEngineClient::domainOf($c->website) : null, 'aliases' => $c->aliases ?? [], 'client' => false];
        }

        return $brands;
    }

    /** Engines this profile asks that also have an API key. */
    public function enginesFor(AiTrackingProfile $profile, array $only = []): array
    {
        $wanted = $profile->engines ?: array_keys(AiEngineClient::ENGINES);
        if ($only) {
            $wanted = array_intersect($wanted, $only);
        }

        return array_values(array_filter($wanted, fn ($e) => AiEngineClient::configured($e)));
    }

    /**
     * @return array{asked: int, skipped: int, failed: int}
     */
    public function run(AiTrackingProfile $profile, array $only = [], bool $force = false, ?int $limit = null, ?callable $progress = null): array
    {
        $engines = $this->enginesFor($profile, $only);
        $brands = $this->brandsFor($profile);
        $limit = $limit ?? (int) config('services.ai_tracking.max_prompts', 50);
        $prompts = AiTrackedPrompt::where('tenant_id', $profile->tenant_id)->where('active', true)
            ->orderByDesc('volume')->orderBy('id')->limit($limit)->get();
        $today = Carbon::today();
        $weekStart = $today->copy()->startOfWeek();
        $stats = ['asked' => 0, 'skipped' => 0, 'failed' => 0];

        foreach ($prompts as $prompt) {
            foreach ($engines as $engine) {
                $exists = AiPromptRun::where('ai_tracked_prompt_id', $prompt->id)->where('engine', $engine)
                    ->where('status', 'ok')->whereBetween('run_on', [$weekStart->toDateString(), $today->toDateString()])->exists();
                if ($exists && ! $force) {
                    $stats['skipped']++;

                    continue;
                }
                $this->runOne($prompt, $engine, $brands, $today) ? $stats['asked']++ : $stats['failed']++;
                $progress && $progress($prompt, $engine);
            }
        }

        return $stats;
    }

    public function runOne(AiTrackedPrompt $prompt, string $engine, array $brands, ?Carbon $on = null): bool
    {
        $on ??= Carbon::today();
        $key = ['ai_tracked_prompt_id' => $prompt->id, 'engine' => $engine, 'run_on' => $on->toDateString()];
        try {
            $answer = $this->client->ask($engine, $prompt->prompt, $prompt->country);
            $a = $this->analyzer->analyze($answer['text'], $answer['sources'], $brands);
            AiPromptRun::updateOrCreate($key, [
                'tenant_id' => $prompt->tenant_id,
                'model' => $answer['model'],
                'status' => 'ok',
                'answer' => $answer['text'],
                'brand_mentioned' => $a['mentioned'],
                'brand_rank' => $a['rank'],
                'brand_cited' => $a['cited'],
                'brands' => $a['brands'],
                'sources' => $answer['sources'],
                'sentiment' => $a['mentioned'] ? $this->sentiment($answer['text'], $brands[0]['name']) : null,
                'error' => null,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('AI prompt tracking failed', ['tenant' => $prompt->tenant_id, 'prompt' => $prompt->id, 'engine' => $engine, 'error' => $e->getMessage()]);
            AiPromptRun::updateOrCreate($key, ['tenant_id' => $prompt->tenant_id, 'model' => AiEngineClient::model($engine), 'status' => 'error', 'error' => mb_substr($e->getMessage(), 0, 1000)]);

            return false;
        }
    }

    /** positive / neutral / negative for how the answer describes the brand; null when unavailable. */
    private function sentiment(string $text, string $brand): ?string
    {
        if (config('services.ai_tracking.sentiment') !== 'ollama' || ! filled(config('services.ollama.host'))) {
            return null;
        }
        $reply = app(OllamaService::class)->chat(
            'You rate how an AI answer describes one brand. Reply with exactly one word: positive, neutral or negative.',
            [['role' => 'user', 'content' => "Brand: {$brand}\n\nAnswer:\n".mb_substr($text, 0, 6000)]]
        );

        return preg_match('/\b(positive|neutral|negative)\b/i', $reply, $m) ? strtolower($m[1]) : null;
    }

    /**
     * Creates the profile if needed and copies the client's Semrush prompts in,
     * one per topic first (largest topics first), up to $limit active prompts.
     *
     * @return int prompts added
     */
    public function seedFromSemrush(int $tenantId, int $limit = 50): int
    {
        $report = SemrushReport::where('tenant_id', $tenantId)->where('is_client', true)->latest('report_month')->first();
        $profile = AiTrackingProfile::firstOrCreate(['tenant_id' => $tenantId], ['domain' => $report?->domain, 'engines' => array_keys(AiEngineClient::ENGINES)]);
        if (! $profile->domain && $report) {
            $profile->update(['domain' => $report->domain]);
        }
        if (! $report) {
            return 0;
        }

        $have = AiTrackedPrompt::where('tenant_id', $tenantId)->where('active', true)->count();
        $room = max(0, $limit - $have);
        $rows = collect($report->prompts ?? [])->filter(fn ($p) => filled($p['prompt'] ?? null))
            ->unique(fn ($p) => AiTrackedPrompt::hashOf($p['prompt']))
            ->sortByDesc(fn ($p) => (int) ($p['topic_volume'] ?? 0));
        // Spread across topics: the first prompt of every topic, then the rest.
        $firsts = $rows->unique(fn ($p) => $p['topic_name'] ?? $p['prompt']);
        $ordered = $firsts->concat($rows->diffKeys($firsts));

        $added = 0;
        foreach ($ordered as $p) {
            if ($added >= $room) {
                break;
            }
            $prompt = AiTrackedPrompt::firstOrCreate(
                ['tenant_id' => $tenantId, 'prompt_hash' => AiTrackedPrompt::hashOf($p['prompt'])],
                ['prompt' => trim($p['prompt']), 'topic' => $p['topic_name'] ?? null, 'volume' => $p['topic_volume'] ?? null,
                    'country' => strlen($p['country'] ?? '') === 2 ? strtolower($p['country']) : null, 'source' => 'semrush', 'active' => true]
            );
            $added += $prompt->wasRecentlyCreated ? 1 : 0;
        }

        return $added;
    }
}
