<?php

namespace App\Livewire\Pages;

use App\Models\Industry;
use App\Models\SubIndustry;
use App\Models\CampaignCategory;
use Livewire\Component;
use Illuminate\Support\Str;

class IndustryManagement extends Component
{
    // Filters
    public string $searchQuery = '';
    public string $statusFilter = 'all';
    public string $sortBy = 'sort_order';

    // Industry modal
    public bool $showIndustryModal = false;
    public ?int $editingIndustryId = null;
    public string $industryDisplayName = '';
    public string $industryDescription = '';

    // Category modal
    public bool $showCategoryModal = false;
    public ?int $editingCategoryId = null;
    public ?int $categoryIndustryId = null;
    public string $categoryType = 'sub_industry'; // sub_industry or campaign_category
    public string $categoryDisplayName = '';
    public string $categoryDescription = '';

    // Delete modal
    public bool $showDeleteModal = false;
    public string $deleteType = ''; // industry, sub_industry, campaign_category
    public ?int $deleteId = null;
    public string $deleteName = '';

    public bool $saving = false;

    protected $listeners = ['refreshIndustries' => '$refresh'];

    public function getIndustriesProperty()
    {
        $query = Industry::with([
            'subIndustries' => fn($q) => $q->orderBy('sort_order')->orderBy('display_name'),
            'campaignCategories' => fn($q) => $q->orderBy('sort_order')->orderBy('display_name'),
        ]);

        if ($this->statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        if ($this->sortBy === 'name') {
            $query->orderBy('display_name');
        } elseif ($this->sortBy === 'categories') {
            $query->withCount('subIndustries')->orderByDesc('sub_industries_count');
        } else {
            $query->orderBy('sort_order')->orderBy('display_name');
        }

        return $query->get();
    }

    public function getFilteredIndustriesProperty()
    {
        if (empty($this->searchQuery)) {
            return $this->industries;
        }

        $q = strtolower($this->searchQuery);

        return $this->industries->filter(function ($industry) use ($q) {
            if (str_contains(strtolower($industry->display_name), $q)) return true;
            if (str_contains(strtolower($industry->name), $q)) return true;
            if ($industry->subIndustries->contains(fn($c) => str_contains(strtolower($c->display_name), $q))) return true;
            if ($industry->campaignCategories->contains(fn($c) => str_contains(strtolower($c->display_name), $q))) return true;
            return false;
        });
    }

    public function getTotalSubIndustriesProperty(): int
    {
        return $this->industries->sum(fn($ind) => $ind->subIndustries->count());
    }

    public function getTotalCampaignCategoriesProperty(): int
    {
        return $this->industries->sum(fn($ind) => $ind->campaignCategories->count());
    }

    // Industry CRUD
    public function openAddIndustryModal(): void
    {
        $this->resetIndustryForm();
        $this->showIndustryModal = true;
    }

    public function editIndustry(int $id): void
    {
        $industry = Industry::findOrFail($id);
        $this->editingIndustryId = $industry->id;
        $this->industryDisplayName = $industry->display_name;
        $this->industryDescription = $industry->description ?? '';
        $this->showIndustryModal = true;
    }

    public function saveIndustry(): void
    {
        if (empty($this->industryDisplayName)) return;

        $this->saving = true;

        try {
            $slug = Str::slug($this->industryDisplayName, '_');

            if ($this->editingIndustryId) {
                $industry = Industry::findOrFail($this->editingIndustryId);
                $industry->update([
                    'name' => $slug,
                    'display_name' => $this->industryDisplayName,
                    'description' => $this->industryDescription ?: null,
                ]);
                session()->flash('success', __('pages.industry_management.industry_updated'));
            } else {
                Industry::create([
                    'name' => $slug,
                    'display_name' => $this->industryDisplayName,
                    'description' => $this->industryDescription ?: null,
                    'sort_order' => Industry::max('sort_order') + 1,
                    'is_active' => true,
                ]);
                session()->flash('success', __('pages.industry_management.industry_created'));
            }

            $this->closeIndustryModal();
        } catch (\Exception $e) {
            session()->flash('error', __('pages.industry_management.save_failed') . ': ' . $e->getMessage());
        } finally {
            $this->saving = false;
        }
    }

    public function closeIndustryModal(): void
    {
        $this->showIndustryModal = false;
        $this->resetIndustryForm();
    }

    private function resetIndustryForm(): void
    {
        $this->editingIndustryId = null;
        $this->industryDisplayName = '';
        $this->industryDescription = '';
    }

    // Category CRUD
    public function openAddCategoryModal(int $industryId, string $type): void
    {
        $this->resetCategoryForm();
        $this->categoryIndustryId = $industryId;
        $this->categoryType = $type;
        $this->showCategoryModal = true;
    }

    public function editCategory(int $id, string $type): void
    {
        $this->categoryType = $type;

        if ($type === 'sub_industry') {
            $cat = SubIndustry::findOrFail($id);
        } else {
            $cat = CampaignCategory::findOrFail($id);
        }

        $this->editingCategoryId = $cat->id;
        $this->categoryDisplayName = $cat->display_name;
        $this->categoryDescription = $cat->description ?? '';
        $this->showCategoryModal = true;
    }

    public function saveCategory(): void
    {
        if (empty($this->categoryDisplayName)) return;

        $this->saving = true;

        try {
            $slug = Str::slug($this->categoryDisplayName, '_');

            if ($this->editingCategoryId) {
                if ($this->categoryType === 'sub_industry') {
                    $cat = SubIndustry::findOrFail($this->editingCategoryId);
                } else {
                    $cat = CampaignCategory::findOrFail($this->editingCategoryId);
                }

                $cat->update([
                    'name' => $slug,
                    'display_name' => $this->categoryDisplayName,
                    'description' => $this->categoryDescription ?: null,
                ]);
            } else {
                $data = [
                    'industry_id' => $this->categoryIndustryId,
                    'name' => $slug,
                    'display_name' => $this->categoryDisplayName,
                    'description' => $this->categoryDescription ?: null,
                    'sort_order' => 0,
                    'is_active' => true,
                ];

                if ($this->categoryType === 'sub_industry') {
                    $data['sort_order'] = SubIndustry::where('industry_id', $this->categoryIndustryId)->max('sort_order') + 1;
                    SubIndustry::create($data);
                } else {
                    $data['sort_order'] = CampaignCategory::where('industry_id', $this->categoryIndustryId)->max('sort_order') + 1;
                    CampaignCategory::create($data);
                }
            }

            $this->closeCategoryModal();
            session()->flash('success', __('pages.industry_management.category_saved'));
        } catch (\Exception $e) {
            session()->flash('error', __('pages.industry_management.save_failed') . ': ' . $e->getMessage());
        } finally {
            $this->saving = false;
        }
    }

    public function closeCategoryModal(): void
    {
        $this->showCategoryModal = false;
        $this->resetCategoryForm();
    }

    private function resetCategoryForm(): void
    {
        $this->editingCategoryId = null;
        $this->categoryIndustryId = null;
        $this->categoryDisplayName = '';
        $this->categoryDescription = '';
    }

    // Delete
    public function confirmDeleteIndustry(int $id): void
    {
        $industry = Industry::findOrFail($id);
        $this->deleteType = 'industry';
        $this->deleteId = $id;
        $this->deleteName = $industry->display_name;
        $this->showDeleteModal = true;
    }

    public function confirmDeleteCategory(int $id, string $type): void
    {
        if ($type === 'sub_industry') {
            $cat = SubIndustry::findOrFail($id);
        } else {
            $cat = CampaignCategory::findOrFail($id);
        }

        $this->deleteType = $type;
        $this->deleteId = $id;
        $this->deleteName = $cat->display_name;
        $this->showDeleteModal = true;
    }

    public function executeDelete(): void
    {
        $this->saving = true;

        try {
            if ($this->deleteType === 'industry') {
                $industry = Industry::findOrFail($this->deleteId);
                $industry->subIndustries()->delete();
                $industry->campaignCategories()->delete();
                $industry->delete();
            } elseif ($this->deleteType === 'sub_industry') {
                SubIndustry::findOrFail($this->deleteId)->delete();
            } else {
                CampaignCategory::findOrFail($this->deleteId)->delete();
            }

            $this->showDeleteModal = false;
            session()->flash('success', __('pages.industry_management.deleted'));
        } catch (\Exception $e) {
            session()->flash('error', __('pages.industry_management.delete_failed') . ': ' . $e->getMessage());
        } finally {
            $this->saving = false;
        }
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deleteType = '';
        $this->deleteId = null;
        $this->deleteName = '';
    }

    // Sort order update
    public function updateIndustryOrder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            Industry::where('id', $id)->update(['sort_order' => $index]);
        }
    }

    public function render()
    {
        return view('livewire.pages.industry-management')
            ->layout('layouts.app', ['title' => __('pages.industry_management.title')]);
    }
}
