<?php

namespace Tests\Feature;

use App\Models\Competitor;
use App\Models\SemrushReport;
use App\Models\Tenant;
use App\Services\AiImportInspector;
use App\Services\SemrushImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AiMonthlyImportTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().'/ai-import-'.uniqid();
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir.'/*'));
        rmdir($this->dir);
        parent::tearDown();
    }

    private function csv(string $name, array $headers, array $rows): array
    {
        $path = $this->dir.'/'.$name;
        $h = fopen($path, 'w');
        fputcsv($h, $headers, ',', '"', '');
        foreach ($rows as $r) {
            fputcsv($h, $r, ',', '"', '');
        }
        fclose($h);

        return ['path' => $path, 'name' => $name];
    }

    private function topics(string $domain, int $visibility = 40): array
    {
        return $this->csv("brand_topics_{$domain}_worldwide.csv", AiImportInspector::TOPIC_COLUMNS,
            [['عسل '.$domain, 'sa', (string) $visibility, '20', '3', '1000', '1;2;3', 'informational:100']]);
    }

    private function prompts(string $domain): array
    {
        return $this->csv("prompts_{$domain}_worldwide.csv", AiImportInspector::PROMPT_COLUMNS,
            [['best honey for '.$domain.'?', 'sa', 'chatgpt', 'mentions_target;cites_target', '16761296487254763338', 'عسل', '1000', 'informational:100', '40', '20', 'Answer', '3', '2']]);
    }

    private function tenant(): Tenant
    {
        $t = Tenant::factory()->create();
        Competitor::create(['tenant_id' => $t->id, 'name' => 'Rival', 'website' => 'https://www.rival.com/ar']);

        return $t;
    }

    public function test_valid_batch_passes_and_imports_every_brand_together(): void
    {
        $t = $this->tenant();
        $files = [$this->topics('client_com'), $this->prompts('client_com'), $this->topics('rival_com'), $this->prompts('rival_com')];
        $inspector = new AiImportInspector;
        $report = $inspector->inspect($files, $t->id, 'https://store.client.com', '2026-11-10');

        $this->assertTrue($report['ok'], json_encode($report));
        $this->assertSame('client', $report['files'][0]['role']);
        $this->assertSame('competitor', $report['files'][2]['role']);

        $groups = [];
        foreach ($inspector->payloads as $p) {
            $groups[$p['domain']][$p['type']] = $p['rows'];
        }
        DB::transaction(fn () => (new SemrushImporter)->import($t->id, 'client.com', '2026-11-10', $groups));
        $this->assertSame(2, SemrushReport::where('tenant_id', $t->id)->where('report_month', '2026-11')->count());
        $this->assertTrue(SemrushReport::where('domain', 'client.com')->value('is_client'));

        // A second upload for the same month is allowed but flagged as a replacement.
        $again = (new AiImportInspector)->inspect($files, $t->id, 'client.com', '2026-11-12');
        $this->assertStringContainsString('Replaces the November 2026 data', implode(' ', $again['files'][0]['warnings']));
    }

    public function test_bad_batches_are_blocked_with_clear_reasons(): void
    {
        $t = $this->tenant();
        $renamed = $this->prompts('client_com');
        rename($renamed['path'], $this->dir.'/brand_topics_client_com_worldwide.csv');
        $files = [
            ['path' => $this->dir.'/brand_topics_client_com_worldwide.csv', 'name' => 'brand_topics_client_com_worldwide.csv'],
            $this->topics('stranger_com'),
            $this->csv('notes.csv', ['a', 'b'], [['1', '2']]),
            $this->topics('rival_com', 150),
        ];
        $report = (new AiImportInspector)->inspect($files, $t->id, 'client.com', '2026-11-10');
        $all = implode(' | ', array_merge($report['errors'], ...array_column($report['files'], 'errors')));

        $this->assertFalse($report['ok']);
        $this->assertStringContainsString('file name says Brand topics but the columns are a Prompts export', $all);
        $this->assertStringContainsString('stranger.com is not this client', $all);
        $this->assertStringContainsString('Not a Semrush AI Visibility export', $all);
        $this->assertStringContainsString('visibility above 100', $all);
        $this->assertStringContainsString('rival.com: the Prompts export is missing', $all);
    }

    public function test_missing_domain_or_future_date_blocks_the_batch(): void
    {
        $t = $this->tenant();
        $report = (new AiImportInspector)->inspect([$this->topics('client_com'), $this->prompts('client_com')], $t->id, '', now()->addDay()->toDateString());
        $this->assertFalse($report['ok']);
        $this->assertCount(2, $report['errors']);
    }
}
