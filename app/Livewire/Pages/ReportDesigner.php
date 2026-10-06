<?php

namespace App\Livewire\Pages;

use App\Models\AdAccount;
use App\Models\Report;
use App\Services\ReportWidgetDataService;
use Livewire\Component;
use Str;

class ReportDesigner extends Component
{
    public Report $report;

    public array  $layout          = ['blocks' => []];
    public ?string $selectedBlockId = null;
    public bool   $preview         = false;
    public bool   $saved           = false;

    // Config panel live-edit fields
    public string $cfgTitle    = '';
    public array  $cfgMetrics  = [];
    public string $cfgMetric   = 'spend';
    public string $cfgGrain    = 'day';
    public string $cfgContent  = '';
    public int    $cfgRows     = 10;

    protected ReportWidgetDataService $widgetData;

    public function boot(ReportWidgetDataService $widgetData): void
    {
        $this->widgetData = $widgetData;
    }

    public function mount(int $id): void
    {
        $this->report = Report::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $this->layout = $this->report->layout ?? ['blocks' => []];

        if (empty($this->layout['blocks'])) {
            $this->seedDefaultLayout();
        }
    }

    // ─── Widget Palette ───────────────────────────────────────────────────────

    public static function widgetTypes(): array
    {
        return [
            'overview' => [
                ['type' => 'kpi_row',        'label' => 'KPI Row',           'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z'],
                ['type' => 'platform_split', 'label' => 'Platform Split',     'icon' => 'M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z'],
            ],
            'charts' => [
                ['type' => 'chart_line',     'label' => 'Trend Line',         'icon' => 'M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941'],
                ['type' => 'chart_bar',      'label' => 'Bar Chart',          'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z'],
            ],
            'data' => [
                ['type' => 'benchmark_table','label' => 'Benchmarks',         'icon' => 'M9 4.5a.75.75 0 0 1 .721.544l.813 2.846a3.75 3.75 0 0 0 2.576 2.576l2.846.813a.75.75 0 0 1 0 1.442l-2.846.813a3.75 3.75 0 0 0-2.576 2.576l-.813 2.846a.75.75 0 0 1-1.442 0l-.813-2.846a3.75 3.75 0 0 0-2.576-2.576l-2.846-.813a.75.75 0 0 1 0-1.442l2.846-.813A3.75 3.75 0 0 0 7.466 7.89l.813-2.846A.75.75 0 0 1 9 4.5Z'],
                ['type' => 'campaign_table', 'label' => 'Campaign Table',     'icon' => 'M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h1.5C5.496 19.5 6 18.996 6 18.375m-3.75.125v-6c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v6m-3.75 0H6m0 0v-9.375c0-.621.504-1.125 1.125-1.125h6.75c.621 0 1.125.504 1.125 1.125V19.5m-8.25 0h8.25m0 0h1.5a1.125 1.125 0 0 0 1.125-1.125V14.25c0-.621-.504-1.125-1.125-1.125h-1.5m-7.5 5.25V9.375m0 0H6'],
            ],
            'content' => [
                ['type' => 'ai_summary',     'label' => 'AI Summary',         'icon' => 'M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z'],
                ['type' => 'text_heading',   'label' => 'Section Heading',    'icon' => 'M2.243 4.493v7.5m0 0v7.502m0-7.501h10.5m0-7.5v7.5m0 0v7.501m4.501-8.627 2.25-1.5v10.126m0 0h-2.25m2.25 0h2.25'],
                ['type' => 'divider',        'label' => 'Divider',            'icon' => 'M3.75 9h16.5m-16.5 6.75h16.5'],
            ],
        ];
    }

    // ─── Block Lifecycle ──────────────────────────────────────────────────────

    public function addBlock(string $type): void
    {
        $id = 'blk_' . Str::random(8);

        $defaults = match ($type) {
            'kpi_row'        => ['metrics' => ['spend', 'impressions', 'ctr', 'cpl'], 'title' => ''],
            'chart_line'     => ['metric' => 'spend', 'grain' => 'day', 'title' => 'Spend Over Time'],
            'chart_bar'      => ['metric' => 'spend', 'title' => 'Platform Comparison'],
            'benchmark_table'=> ['metrics' => ['ctr', 'cpc', 'cpm', 'cpl'], 'title' => 'Benchmark Comparison'],
            'campaign_table' => ['rows' => 10, 'title' => 'Top Campaigns'],
            'platform_split' => ['metric' => 'spend', 'title' => 'Budget by Platform'],
            'ai_summary'     => ['section' => 'executive_summary', 'title' => 'Executive Summary'],
            'text_heading'   => ['content' => 'Section Heading', 'level' => 'h2'],
            'divider'        => [],
            default          => [],
        };

        $this->layout['blocks'][] = [
            'id'     => $id,
            'type'   => $type,
            'config' => $defaults,
        ];

        $this->selectBlock($id);
        $this->autoSave();
    }

    public function removeBlock(string $id): void
    {
        $this->layout['blocks'] = array_values(
            array_filter($this->layout['blocks'], fn ($b) => $b['id'] !== $id)
        );
        if ($this->selectedBlockId === $id) {
            $this->selectedBlockId = null;
        }
        $this->autoSave();
    }

    public function selectBlock(string $id): void
    {
        $this->selectedBlockId = ($this->selectedBlockId === $id) ? null : $id;

        if ($this->selectedBlockId) {
            $block = $this->findBlock($id);
            $cfg   = $block['config'] ?? [];
            $this->cfgTitle   = $cfg['title'] ?? '';
            $this->cfgMetrics = $cfg['metrics'] ?? ['spend', 'impressions', 'ctr', 'cpl'];
            $this->cfgMetric  = $cfg['metric'] ?? 'spend';
            $this->cfgGrain   = $cfg['grain'] ?? 'day';
            $this->cfgContent = $cfg['content'] ?? '';
            $this->cfgRows    = $cfg['rows'] ?? 10;
        }
    }

    public function reorderBlocks(array $ids): void
    {
        $byId = collect($this->layout['blocks'])->keyBy('id');
        $this->layout['blocks'] = array_values(
            array_filter(array_map(fn ($id) => $byId[$id] ?? null, $ids))
        );
        $this->autoSave();
    }

    public function saveConfig(): void
    {
        if (!$this->selectedBlockId) return;

        $this->updateBlockConfig($this->selectedBlockId, [
            'title'   => $this->cfgTitle,
            'metrics' => $this->cfgMetrics,
            'metric'  => $this->cfgMetric,
            'grain'   => $this->cfgGrain,
            'content' => $this->cfgContent,
            'rows'    => $this->cfgRows,
        ]);
    }

    private function updateBlockConfig(string $id, array $config): void
    {
        foreach ($this->layout['blocks'] as &$block) {
            if ($block['id'] === $id) {
                $block['config'] = array_merge($block['config'] ?? [], $config);
                break;
            }
        }
        $this->autoSave();
    }

    private function findBlock(string $id): ?array
    {
        return collect($this->layout['blocks'])->firstWhere('id', $id);
    }

    // ─── Persistence ─────────────────────────────────────────────────────────

    private function autoSave(): void
    {
        $this->report->update(['layout' => $this->layout]);
    }

    public function save(): void
    {
        $this->report->update(['layout' => $this->layout]);
        $this->saved = true;
        $this->dispatch('saved');
    }

    public function togglePreview(): void
    {
        $this->preview = !$this->preview;
    }

    public function generateShareLink(): void
    {
        if (!$this->report->share_token) {
            $this->report->update(['share_token' => \Str::random(48)]);
            $this->report->refresh();
        }
        $this->dispatch('share-link-copied', url: route('report-share', $this->report->share_token));
    }

    // ─── Default Layout ───────────────────────────────────────────────────────

    private function seedDefaultLayout(): void
    {
        $this->layout = [
            'blocks' => [
                ['id' => 'blk_' . Str::random(8), 'type' => 'kpi_row',         'config' => ['metrics' => ['spend', 'impressions', 'ctr', 'cpl'], 'title' => '']],
                ['id' => 'blk_' . Str::random(8), 'type' => 'chart_line',      'config' => ['metric' => 'spend', 'grain' => 'day', 'title' => 'Spend Over Time']],
                ['id' => 'blk_' . Str::random(8), 'type' => 'benchmark_table', 'config' => ['metrics' => ['ctr', 'cpc', 'cpm', 'cpl'], 'title' => 'Benchmark Comparison']],
                ['id' => 'blk_' . Str::random(8), 'type' => 'campaign_table',  'config' => ['rows' => 10, 'title' => 'Top Campaigns by Spend']],
                ['id' => 'blk_' . Str::random(8), 'type' => 'ai_summary',      'config' => ['section' => 'executive_summary', 'title' => 'Executive Summary']],
            ],
        ];
        $this->autoSave();
    }

    // ─── Computed Data ────────────────────────────────────────────────────────

    public function getWidgetDataProperty(): array
    {
        return $this->widgetData->dataForReport($this->report);
    }

    public function render()
    {
        return view('livewire.pages.report-designer', [
            'widgetTypes' => self::widgetTypes(),
            'widgetData'  => $this->widgetData,
            'kpiLabels'   => self::kpiLabels(),
        ])->layout('layouts.app-fullwidth');
    }

    public static function kpiLabels(): array
    {
        return [
            'spend'       => ['label' => 'Total Spend',    'format' => 'currency', 'icon' => 'M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z'],
            'impressions' => ['label' => 'Impressions',    'format' => 'number',   'icon' => 'M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z'],
            'clicks'      => ['label' => 'Clicks',         'format' => 'number',   'icon' => 'M15.042 21.672 13.684 16.6m0 0-2.51 2.225.569-9.47 5.227 7.917-3.286-.672Zm-7.518-.267A8.25 8.25 0 1 1 20.25 10.5M8.288 14.212A5.25 5.25 0 1 1 17.25 10.5'],
            'ctr'         => ['label' => 'CTR',            'format' => 'percent',  'icon' => 'M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6'],
            'cpc'         => ['label' => 'CPC',            'format' => 'currency', 'icon' => 'M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
            'cpm'         => ['label' => 'CPM',            'format' => 'currency', 'icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z'],
            'cpl'         => ['label' => 'CPL',            'format' => 'currency', 'icon' => 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z'],
            'leads'       => ['label' => 'Leads',          'format' => 'number',   'icon' => 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z'],
            'campaigns'   => ['label' => 'Campaigns',      'format' => 'number',   'icon' => 'M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z'],
            'conversions' => ['label' => 'Conversions',    'format' => 'number',   'icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
            'reach'       => ['label' => 'Reach',          'format' => 'number',   'icon' => 'M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z'],
        ];
    }
}
