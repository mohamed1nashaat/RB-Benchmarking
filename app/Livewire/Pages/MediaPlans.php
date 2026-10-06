<?php

namespace App\Livewire\Pages;

use App\Models\MediaPlan;
use Livewire\Component;
use Livewire\WithPagination;

class MediaPlans extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function deletePlan(int $id): void
    {
        MediaPlan::findOrFail($id)->delete();
        session()->flash('success', __('media_planner.plan_deleted'));
    }

    public function render()
    {
        $plans = MediaPlan::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(20);

        $totalCount    = MediaPlan::count();
        $approvedCount = MediaPlan::where('status', 'approved')->count();
        $draftCount    = MediaPlan::where('status', 'draft')->count();

        return view('livewire.pages.media-plans', compact('plans', 'totalCount', 'approvedCount', 'draftCount'))
            ->layout('layouts.app');
    }
}
