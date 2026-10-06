<?php

namespace App\Livewire\Pages;

use App\Models\Report;
use Livewire\Component;
use Livewire\WithPagination;

class Reports extends Component
{
    use WithPagination;

    public string $search      = '';
    public string $statusFilter = '';

    public function updatedSearch(): void    { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }

    public function deleteReport(int $id): void
    {
        Report::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($id)
            ->delete();
    }

    public function render()
    {
        $query = Report::with('adAccount')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->latest();

        return view('livewire.pages.reports', [
            'reports'     => $query->paginate(20),
            'totalCount'  => Report::where('tenant_id', auth()->user()->tenant_id)->count(),
            'readyCount'  => Report::where('tenant_id', auth()->user()->tenant_id)->where('status', 'ready')->count(),
        ])->layout('layouts.app');
    }
}
