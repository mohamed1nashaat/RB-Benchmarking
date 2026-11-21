<?php

namespace App\Livewire\Pages;

use App\Models\Report;
use App\Services\ReportWidgetDataService;
use Livewire\Component;

class SharedReport extends Component
{
    public Report $report;

    protected ReportWidgetDataService $widgetData;

    public function boot(ReportWidgetDataService $widgetData): void
    {
        $this->widgetData = $widgetData;
    }

    public function mount(string $token): void
    {
        $this->report = Report::where('share_token', $token)->firstOrFail();
    }

    public function render()
    {
        return view('livewire.pages.shared-report', [
            'layout'     => $this->report->layout ?? ['blocks' => []],
            'widgetData' => $this->widgetData,
            'kpiLabels'  => ReportDesigner::kpiLabels(),
        ])->layout('layouts.app-fullwidth');
    }
}
