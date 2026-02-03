<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use App\Models\SubIndustry;
use App\Models\CampaignCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IndustryController extends Controller
{
    /**
     * Get all industries with their sub-industries
     */
    public function index()
    {
        try {
            Log::info('Industries API called', [
                'user_id' => auth()->id(),
                'tenant_id' => request()->header('X-Tenant-ID'),
                'authenticated' => auth()->check(),
            ]);

            // Load ALL industries with ALL sub-industries and campaign categories for management purposes
            $industries = Industry::with([
                    'subIndustries' => function ($query) {
                        $query->orderBy('sort_order')->orderBy('display_name');
                    },
                    'campaignCategories' => function ($query) {
                        $query->orderBy('sort_order')->orderBy('display_name');
                    }
                ])
                ->orderBy('sort_order')
                ->orderBy('display_name')
                ->get();

            Log::info('Industries fetched successfully', [
                'count' => $industries->count(),
                'total_sub_industries' => $industries->sum(function($industry) {
                    return $industry->subIndustries->count();
                }),
                'total_campaign_categories' => $industries->sum(function($industry) {
                    return $industry->campaignCategories->count();
                }),
            ]);

            return response()->json([
                'data' => $industries,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching industries', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch industries',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new industry
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:industries,name',
                'display_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'sort_order' => 'nullable|integer',
            ]);

            $industry = Industry::create([
                'name' => $request->name,
                'display_name' => $request->display_name,
                'description' => $request->description,
                'sort_order' => $request->sort_order ?? 0,
                'is_active' => true,
            ]);

            return response()->json([
                'message' => 'Industry created successfully',
                'data' => $industry,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating industry', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Failed to create industry',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an industry
     */
    public function update(Request $request, Industry $industry)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:industries,name,' . $industry->id,
                'display_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'sort_order' => 'nullable|integer',
                'is_active' => 'nullable|boolean',
            ]);

            $industry->update($request->only([
                'name', 'display_name', 'description', 'sort_order', 'is_active'
            ]));

            return response()->json([
                'message' => 'Industry updated successfully',
                'data' => $industry,
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating industry', [
                'industry_id' => $industry->id,
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Failed to update industry',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an industry
     */
    public function destroy(Industry $industry)
    {
        try {
            // Check if industry has ad accounts
            if ($industry->adAccounts()->count() > 0) {
                return response()->json([
                    'error' => 'Cannot delete industry that has associated ad accounts',
                ], 422);
            }

            $industry->delete();

            return response()->json([
                'message' => 'Industry deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting industry', [
                'industry_id' => $industry->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to delete industry',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sub-industries for a specific industry
     */
    public function subIndustries(Request $request, Industry $industry)
    {
        try {
            // Load ALL sub-industries for management purposes (including inactive)
            $subIndustries = $industry->subIndustries()
                ->orderBy('sort_order')
                ->orderBy('display_name')
                ->get();

            return response()->json([
                'data' => $subIndustries,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching sub-industries', [
                'industry_id' => $industry->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch sub-industries',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new sub-industry
     */
    public function storeSubIndustry(Request $request, Industry $industry)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'display_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'sort_order' => 'nullable|integer',
            ]);

            // Check for unique name within the industry
            $exists = SubIndustry::where('industry_id', $industry->id)
                ->where('name', $request->name)
                ->exists();

            if ($exists) {
                return response()->json([
                    'error' => 'Sub-industry name already exists for this industry',
                ], 422);
            }

            $subIndustry = SubIndustry::create([
                'industry_id' => $industry->id,
                'name' => $request->name,
                'display_name' => $request->display_name,
                'description' => $request->description,
                'sort_order' => $request->sort_order ?? 0,
                'is_active' => true,
            ]);

            return response()->json([
                'message' => 'Sub-industry created successfully',
                'data' => $subIndustry,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating sub-industry', [
                'industry_id' => $industry->id,
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Failed to create sub-industry',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a sub-industry
     */
    public function updateSubIndustry(Request $request, SubIndustry $subIndustry)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'display_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'sort_order' => 'nullable|integer',
                'is_active' => 'nullable|boolean',
            ]);

            // Check for unique name within the industry (excluding current record)
            $exists = SubIndustry::where('industry_id', $subIndustry->industry_id)
                ->where('name', $request->name)
                ->where('id', '!=', $subIndustry->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'error' => 'Sub-industry name already exists for this industry',
                ], 422);
            }

            $subIndustry->update($request->only([
                'name', 'display_name', 'description', 'sort_order', 'is_active'
            ]));

            return response()->json([
                'message' => 'Sub-industry updated successfully',
                'data' => $subIndustry,
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating sub-industry', [
                'sub_industry_id' => $subIndustry->id,
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Failed to update sub-industry',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a sub-industry
     */
    public function destroySubIndustry(SubIndustry $subIndustry)
    {
        try {
            // Check if sub-industry has ad accounts
            if ($subIndustry->adAccounts()->count() > 0) {
                return response()->json([
                    'error' => 'Cannot delete sub-industry that has associated ad accounts',
                ], 422);
            }

            $subIndustry->delete();

            return response()->json([
                'message' => 'Sub-industry deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting sub-industry', [
                'sub_industry_id' => $subIndustry->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to delete sub-industry',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all campaign categories (with industry info)
     */
    public function campaignCategories()
    {
        try {
            $categories = CampaignCategory::with('industry')
                ->orderBy('industry_id')
                ->orderBy('sort_order')
                ->orderBy('display_name')
                ->get();

            return response()->json([
                'data' => $categories,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching campaign categories', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch campaign categories',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new campaign category for an industry
     */
    public function storeCampaignCategory(Request $request, Industry $industry)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'display_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'sort_order' => 'nullable|integer',
            ]);

            // Check for unique name within the industry
            $exists = CampaignCategory::where('industry_id', $industry->id)
                ->where('name', $request->name)
                ->exists();

            if ($exists) {
                return response()->json([
                    'error' => 'Campaign category name already exists for this industry',
                ], 422);
            }

            $category = CampaignCategory::create([
                'industry_id' => $industry->id,
                'name' => $request->name,
                'display_name' => $request->display_name,
                'description' => $request->description,
                'sort_order' => $request->sort_order ?? 0,
                'is_active' => true,
            ]);

            return response()->json([
                'message' => 'Campaign category created successfully',
                'data' => $category,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating campaign category', [
                'industry_id' => $industry->id,
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Failed to create campaign category',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a campaign category
     */
    public function updateCampaignCategory(Request $request, CampaignCategory $campaignCategory)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'display_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'sort_order' => 'nullable|integer',
                'is_active' => 'nullable|boolean',
            ]);

            // Check for unique name within the industry (excluding current record)
            $exists = CampaignCategory::where('industry_id', $campaignCategory->industry_id)
                ->where('name', $request->name)
                ->where('id', '!=', $campaignCategory->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'error' => 'Campaign category name already exists for this industry',
                ], 422);
            }

            $campaignCategory->update($request->only([
                'name', 'display_name', 'description', 'sort_order', 'is_active'
            ]));

            return response()->json([
                'message' => 'Campaign category updated successfully',
                'data' => $campaignCategory,
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating campaign category', [
                'category_id' => $campaignCategory->id,
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Failed to update campaign category',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a campaign category
     */
    public function destroyCampaignCategory(CampaignCategory $campaignCategory)
    {
        try {
            $campaignCategory->delete();

            return response()->json([
                'message' => 'Campaign category deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting campaign category', [
                'category_id' => $campaignCategory->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to delete campaign category',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}