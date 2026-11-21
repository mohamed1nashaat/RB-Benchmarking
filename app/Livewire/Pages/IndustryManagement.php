<?php

namespace App\Livewire\Pages;

use App\Models\Industry;
use App\Models\SubIndustry;
use App\Models\CampaignCategory;
use App\Models\AccountCategory;
use App\Models\AccountSubCategory;
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

    // Category modal (sub_industry / campaign_category)
    public bool $showCategoryModal = false;
    public ?int $editingCategoryId = null;
    public ?int $categoryIndustryId = null;
    public string $categoryType = 'sub_industry'; // sub_industry or campaign_category
    public string $categoryDisplayName = '';
    public string $categoryDescription = '';

    // Account Category modal
    public bool $showAccountCategoryModal = false;
    public ?int $editingAccountCategoryId = null;
    public ?int $accountCategoryIndustryId = null;
    public string $accountCategoryDisplayName = '';
    public string $accountCategoryDescription = '';

    // Account Sub-Category modal
    public bool $showAccountSubCategoryModal = false;
    public ?int $editingAccountSubCategoryId = null;
    public ?int $accountSubCategoryCategoryId = null;
    public string $accountSubCategoryDisplayName = '';
    public string $accountSubCategoryDescription = '';

    // Delete modal
    public bool $showDeleteModal = false;
    public string $deleteType = ''; // industry, sub_industry, campaign_category, account_category, account_sub_category
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

    public function getAccountCategoriesProperty()
    {
        return AccountCategory::with(['subCategories' => fn($q) => $q->ordered()])
            ->ordered()
            ->get();
    }

    public function getTotalSubIndustriesProperty(): int
    {
        return $this->industries->sum(fn($ind) => $ind->subIndustries->count());
    }

    public function getTotalCampaignCategoriesProperty(): int
    {
        return $this->industries->sum(fn($ind) => $ind->campaignCategories->count());
    }

    public function getTotalAccountCategoriesProperty(): int
    {
        return AccountCategory::count();
    }

    public function getTotalAccountSubCategoriesProperty(): int
    {
        return AccountSubCategory::count();
    }

    /**
     * Build a 3-level hierarchy: Industry → Account Category → Sub-Category.
     * The Industry→AccountCategory relationship is explicit via account_categories.industry_id.
     */
    public function getHierarchyTreeProperty()
    {
        $q = strtolower($this->searchQuery);

        $industries = Industry::with([
            'accountCategories' => fn($q) => $q->ordered(),
            'accountCategories.subCategories' => fn($q) => $q->ordered(),
        ])
            ->when($this->statusFilter === 'active', fn($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn($q) => $q->where('is_active', false))
            ->ordered()
            ->get();

        $tree = [];

        foreach ($industries as $industry) {
            $cats = $industry->accountCategories;

            if ($q !== '') {
                $industryMatch = str_contains(strtolower($industry->display_name), $q)
                    || str_contains(strtolower($industry->name), $q);

                $filteredCats = $cats->filter(function ($cat) use ($q, $industryMatch) {
                    $catMatch = str_contains(strtolower($cat->display_name), $q)
                        || str_contains(strtolower($cat->name), $q);

                    $filteredSubs = $cat->subCategories->filter(fn($sub) =>
                        str_contains(strtolower($sub->display_name), $q)
                        || str_contains(strtolower($sub->name), $q)
                    );

                    if ($industryMatch || $catMatch || $filteredSubs->isNotEmpty()) {
                        $cat->setRelation('subCategories', $catMatch || $industryMatch ? $cat->subCategories : $filteredSubs);
                        return true;
                    }

                    return false;
                });

                if (! $industryMatch && $filteredCats->isEmpty()) {
                    continue;
                }

                $cats = $filteredCats;
            }

            $tree[] = [
                'industry' => $industry,
                'accountCategories' => $cats->values(),
            ];
        }

        return collect($tree);
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

    // Category CRUD (sub_industry / campaign_category)
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

    // Account Category CRUD
    public function openAddAccountCategoryModal(?int $industryId = null): void
    {
        $this->resetAccountCategoryForm();
        $this->accountCategoryIndustryId = $industryId;
        $this->showAccountCategoryModal = true;
    }

    public function editAccountCategory(int $id): void
    {
        $cat = AccountCategory::findOrFail($id);
        $this->editingAccountCategoryId = $cat->id;
        $this->accountCategoryIndustryId = $cat->industry_id;
        $this->accountCategoryDisplayName = $cat->display_name;
        $this->accountCategoryDescription = $cat->description ?? '';
        $this->showAccountCategoryModal = true;
    }

    public function saveAccountCategory(): void
    {
        if (empty($this->accountCategoryDisplayName)) return;

        $this->saving = true;

        try {
            $slug = Str::slug($this->accountCategoryDisplayName, '_');

            if (empty($this->accountCategoryIndustryId)) {
                session()->flash('error', __('pages.industry_management.industry_required'));
                return;
            }

            if ($this->editingAccountCategoryId) {
                $cat = AccountCategory::findOrFail($this->editingAccountCategoryId);
                $cat->update([
                    'industry_id' => $this->accountCategoryIndustryId,
                    'name' => $slug,
                    'display_name' => $this->accountCategoryDisplayName,
                    'description' => $this->accountCategoryDescription ?: null,
                ]);
                session()->flash('success', __('pages.industry_management.account_category_updated'));
            } else {
                AccountCategory::create([
                    'industry_id' => $this->accountCategoryIndustryId,
                    'name' => $slug,
                    'display_name' => $this->accountCategoryDisplayName,
                    'description' => $this->accountCategoryDescription ?: null,
                    'sort_order' => AccountCategory::where('industry_id', $this->accountCategoryIndustryId)->max('sort_order') + 1,
                    'is_active' => true,
                ]);
                session()->flash('success', __('pages.industry_management.account_category_created'));
            }

            $this->closeAccountCategoryModal();
        } catch (\Exception $e) {
            session()->flash('error', __('pages.industry_management.save_failed') . ': ' . $e->getMessage());
        } finally {
            $this->saving = false;
        }
    }

    public function closeAccountCategoryModal(): void
    {
        $this->showAccountCategoryModal = false;
        $this->resetAccountCategoryForm();
    }

    private function resetAccountCategoryForm(): void
    {
        $this->editingAccountCategoryId = null;
        $this->accountCategoryIndustryId = null;
        $this->accountCategoryDisplayName = '';
        $this->accountCategoryDescription = '';
    }

    // Account Sub-Category CRUD
    public function openAddAccountSubCategoryModal(int $categoryId): void
    {
        $this->resetAccountSubCategoryForm();
        $this->accountSubCategoryCategoryId = $categoryId;
        $this->showAccountSubCategoryModal = true;
    }

    public function editAccountSubCategory(int $id): void
    {
        $sub = AccountSubCategory::findOrFail($id);
        $this->editingAccountSubCategoryId = $sub->id;
        $this->accountSubCategoryCategoryId = $sub->account_category_id;
        $this->accountSubCategoryDisplayName = $sub->display_name;
        $this->accountSubCategoryDescription = $sub->description ?? '';
        $this->showAccountSubCategoryModal = true;
    }

    public function saveAccountSubCategory(): void
    {
        if (empty($this->accountSubCategoryDisplayName) || empty($this->accountSubCategoryCategoryId)) return;

        $this->saving = true;

        try {
            $slug = Str::slug($this->accountSubCategoryDisplayName, '_');

            if ($this->editingAccountSubCategoryId) {
                $sub = AccountSubCategory::findOrFail($this->editingAccountSubCategoryId);
                $sub->update([
                    'account_category_id' => $this->accountSubCategoryCategoryId,
                    'name' => $slug,
                    'display_name' => $this->accountSubCategoryDisplayName,
                    'description' => $this->accountSubCategoryDescription ?: null,
                ]);
                session()->flash('success', __('pages.industry_management.account_sub_category_updated'));
            } else {
                AccountSubCategory::create([
                    'account_category_id' => $this->accountSubCategoryCategoryId,
                    'name' => $slug,
                    'display_name' => $this->accountSubCategoryDisplayName,
                    'description' => $this->accountSubCategoryDescription ?: null,
                    'sort_order' => AccountSubCategory::where('account_category_id', $this->accountSubCategoryCategoryId)->max('sort_order') + 1,
                    'is_active' => true,
                ]);
                session()->flash('success', __('pages.industry_management.account_sub_category_created'));
            }

            $this->closeAccountSubCategoryModal();
        } catch (\Exception $e) {
            session()->flash('error', __('pages.industry_management.save_failed') . ': ' . $e->getMessage());
        } finally {
            $this->saving = false;
        }
    }

    public function closeAccountSubCategoryModal(): void
    {
        $this->showAccountSubCategoryModal = false;
        $this->resetAccountSubCategoryForm();
    }

    private function resetAccountSubCategoryForm(): void
    {
        $this->editingAccountSubCategoryId = null;
        $this->accountSubCategoryCategoryId = null;
        $this->accountSubCategoryDisplayName = '';
        $this->accountSubCategoryDescription = '';
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

    public function confirmDeleteAccountCategory(int $id): void
    {
        $cat = AccountCategory::findOrFail($id);
        $this->deleteType = 'account_category';
        $this->deleteId = $id;
        $this->deleteName = $cat->display_name;
        $this->showDeleteModal = true;
    }

    public function confirmDeleteAccountSubCategory(int $id): void
    {
        $sub = AccountSubCategory::findOrFail($id);
        $this->deleteType = 'account_sub_category';
        $this->deleteId = $id;
        $this->deleteName = $sub->display_name;
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
            } elseif ($this->deleteType === 'campaign_category') {
                CampaignCategory::findOrFail($this->deleteId)->delete();
            } elseif ($this->deleteType === 'account_category') {
                AccountCategory::findOrFail($this->deleteId)->delete();
            } elseif ($this->deleteType === 'account_sub_category') {
                AccountSubCategory::findOrFail($this->deleteId)->delete();
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
