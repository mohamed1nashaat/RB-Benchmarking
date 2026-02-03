<template>
  <div class="py-6">
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Industry Management</h1>
          <p class="mt-1 text-sm text-gray-500">Manage industries, account categories, and campaign categories</p>
        </div>
        <div class="flex gap-3">
          <button
            @click="expandAll"
            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          >
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
            </svg>
            Expand All
          </button>
          <button
            @click="collapseAll"
            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          >
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25" />
            </svg>
            Collapse All
          </button>
          <button
            @click="showAddModal = true"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700"
          >
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Industry
          </button>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
      <!-- Total Industries -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-primary-100 rounded-md p-3">
            <svg class="h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5" />
            </svg>
          </div>
          <div class="ml-5 w-0 flex-1">
            <dl>
              <dt class="text-sm font-medium text-gray-500 truncate">Total Industries</dt>
              <dd class="text-2xl font-semibold text-gray-900">{{ industries.length }}</dd>
            </dl>
          </div>
        </div>
      </div>

      <!-- Account Categories -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-primary-100 rounded-md p-3">
            <svg class="h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
          </div>
          <div class="ml-5 w-0 flex-1">
            <dl>
              <dt class="text-sm font-medium text-gray-500 truncate">Account Categories</dt>
              <dd class="text-2xl font-semibold text-gray-900">{{ totalSubIndustries }}</dd>
            </dl>
          </div>
        </div>
      </div>

      <!-- Campaign Categories -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-secondary-100 rounded-md p-3">
            <svg class="h-6 w-6 text-secondary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
          </div>
          <div class="ml-5 w-0 flex-1">
            <dl>
              <dt class="text-sm font-medium text-gray-500 truncate">Campaign Categories</dt>
              <dd class="text-2xl font-semibold text-gray-900">{{ totalCampaignCategories }}</dd>
            </dl>
          </div>
        </div>
      </div>

      <!-- Total Categories -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-gray-100 rounded-md p-3">
            <svg class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
          </div>
          <div class="ml-5 w-0 flex-1">
            <dl>
              <dt class="text-sm font-medium text-gray-500 truncate">Total Categories</dt>
              <dd class="text-2xl font-semibold text-gray-900">{{ totalSubIndustries + totalCampaignCategories }}</dd>
            </dl>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Search -->
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              v-model="searchQuery"
              type="text"
              class="w-full pl-10 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              placeholder="Search industries or categories..."
            />
          </div>
        </div>

        <!-- Status Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select
            v-model="statusFilter"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option value="all">All</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>

        <!-- Sort By -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
          <select
            v-model="sortBy"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option value="sort_order">Custom Order</option>
            <option value="name">Name</option>
            <option value="categories">Most Categories</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
    </div>

    <!-- Industries List -->
    <draggable
      v-else-if="filteredIndustries.length > 0"
      v-model="industries"
      item-key="id"
      handle=".drag-handle"
      ghost-class="opacity-50"
      @end="onDragEnd"
      class="space-y-4"
    >
      <template #item="{ element: industry }">
        <div
          v-show="matchesFilters(industry)"
          class="bg-white rounded-lg shadow overflow-hidden"
        >
          <!-- Industry Header -->
          <div
            class="flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-gray-50"
            @click="toggleCollapse(industry.id)"
          >
            <div class="flex items-center gap-4">
              <!-- Drag Handle -->
              <div class="drag-handle cursor-grab active:cursor-grabbing p-1 text-gray-400 hover:text-gray-600" @click.stop>
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M8 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM8 12a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM8 18a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM14 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM14 12a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM14 18a2 2 0 1 1-4 0 2 2 0 0 1 4 0z" />
                </svg>
              </div>

              <!-- Collapse Arrow -->
              <svg
                class="w-5 h-5 text-gray-400 transition-transform duration-200"
                :class="{ '-rotate-90': collapsedIndustries.has(industry.id) }"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>

              <!-- Industry Icon -->
              <div class="flex-shrink-0 bg-primary-100 rounded-md p-3">
                <span class="text-lg font-bold text-primary-600">{{ industry.display_name.charAt(0) }}</span>
              </div>

              <!-- Industry Info -->
              <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ industry.display_name }}</h3>
                <p class="text-sm text-gray-500">{{ industry.name }}</p>
              </div>
            </div>

            <div class="flex items-center gap-4" @click.stop>
              <!-- Category Counts -->
              <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-50 text-primary-700">
                  {{ industry.sub_industries?.length || 0 }} account
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-secondary-50 text-secondary-700">
                  {{ industry.campaign_categories?.length || 0 }} campaign
                </span>
              </div>

              <!-- Actions -->
              <div class="flex items-center gap-2">
                <button
                  @click="editIndustry(industry)"
                  class="p-2 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded-md"
                  title="Edit"
                >
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button
                  @click="confirmDeleteIndustry(industry)"
                  class="p-2 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded-md"
                  title="Delete"
                >
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Categories Content -->
          <Transition name="collapse">
            <div v-show="!collapsedIndustries.has(industry.id)" class="border-t border-gray-200">
              <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-gray-200">
                <!-- Account Categories -->
                <div class="p-6">
                  <div class="flex items-center justify-between mb-4">
                    <h4 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                      <div class="w-2 h-2 bg-primary-500 rounded-full"></div>
                      Account Categories
                    </h4>
                    <button
                      @click="showAddCategoryModal(industry, 'sub_industry')"
                      class="text-sm text-primary-600 hover:text-primary-700 font-medium"
                    >
                      + Add
                    </button>
                  </div>

                  <draggable
                    v-model="industry.sub_industries"
                    item-key="id"
                    handle=".cat-drag"
                    ghost-class="opacity-50"
                    @end="onCategoryDragEnd(industry, 'sub_industries')"
                    class="space-y-2"
                  >
                    <template #item="{ element: cat }">
                      <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-md hover:bg-gray-100 group">
                        <div class="flex items-center gap-2">
                          <div class="cat-drag cursor-grab text-gray-300 hover:text-gray-500" @click.stop>
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                              <path d="M8 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM8 12a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM8 18a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM14 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM14 12a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM14 18a2 2 0 1 1-4 0 2 2 0 0 1 4 0z" />
                            </svg>
                          </div>
                          <span class="text-sm text-gray-700">{{ cat.display_name }}</span>
                        </div>
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100">
                          <button @click="editCategory(cat, 'sub_industry')" class="p-1 text-gray-400 hover:text-primary-600 rounded">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                          </button>
                          <button @click="confirmDeleteCategory(cat, 'sub_industry')" class="p-1 text-gray-400 hover:text-primary-600 rounded">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                          </button>
                        </div>
                      </div>
                    </template>
                  </draggable>

                  <p v-if="!industry.sub_industries?.length" class="text-sm text-gray-400 italic py-4 text-center">
                    No account categories yet
                  </p>
                </div>

                <!-- Campaign Categories -->
                <div class="p-6">
                  <div class="flex items-center justify-between mb-4">
                    <h4 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                      <div class="w-2 h-2 bg-secondary-500 rounded-full"></div>
                      Campaign Categories
                    </h4>
                    <button
                      @click="showAddCategoryModal(industry, 'campaign_category')"
                      class="text-sm text-secondary-600 hover:text-secondary-700 font-medium"
                    >
                      + Add
                    </button>
                  </div>

                  <draggable
                    v-model="industry.campaign_categories"
                    item-key="id"
                    handle=".cat-drag"
                    ghost-class="opacity-50"
                    @end="onCategoryDragEnd(industry, 'campaign_categories')"
                    class="space-y-2"
                  >
                    <template #item="{ element: cat }">
                      <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-md hover:bg-gray-100 group">
                        <div class="flex items-center gap-2">
                          <div class="cat-drag cursor-grab text-gray-300 hover:text-gray-500" @click.stop>
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                              <path d="M8 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM8 12a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM8 18a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM14 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM14 12a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM14 18a2 2 0 1 1-4 0 2 2 0 0 1 4 0z" />
                            </svg>
                          </div>
                          <span class="text-sm text-gray-700">{{ cat.display_name }}</span>
                        </div>
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100">
                          <button @click="editCategory(cat, 'campaign_category')" class="p-1 text-gray-400 hover:text-secondary-600 rounded">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                          </button>
                          <button @click="confirmDeleteCategory(cat, 'campaign_category')" class="p-1 text-gray-400 hover:text-primary-600 rounded">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                          </button>
                        </div>
                      </div>
                    </template>
                  </draggable>

                  <p v-if="!industry.campaign_categories?.length" class="text-sm text-gray-400 italic py-4 text-center">
                    No campaign categories yet
                  </p>
                </div>
              </div>
            </div>
          </Transition>
        </div>
      </template>
    </draggable>

    <!-- Empty State -->
    <div v-else-if="!loading" class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">No industries found</h3>
      <p class="mt-1 text-sm text-gray-500">
        {{ searchQuery ? 'Try adjusting your search filters' : 'Get started by adding your first industry' }}
      </p>
      <div class="mt-6">
        <button
          @click="showAddModal = true"
          class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700"
        >
          <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Add Industry
        </button>
      </div>
    </div>

    <!-- Add/Edit Industry Modal -->
    <Transition name="modal">
      <div v-if="showAddModal || editingIndustry" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeIndustryModal"></div>
          <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-auto p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
              {{ editingIndustry ? 'Edit Industry' : 'Add Industry' }}
            </h3>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Display Name</label>
                <input
                  v-model="industryForm.display_name"
                  type="text"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                  placeholder="e.g. Real Estate"
                  @keyup.enter="saveIndustry"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description (Optional)</label>
                <textarea
                  v-model="industryForm.description"
                  rows="2"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                  placeholder="Brief description..."
                ></textarea>
              </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
              <button
                @click="closeIndustryModal"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                @click="saveIndustry"
                :disabled="!industryForm.display_name || saving"
                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 disabled:opacity-50"
              >
                {{ saving ? 'Saving...' : (editingIndustry ? 'Save Changes' : 'Add Industry') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Add/Edit Category Modal -->
    <Transition name="modal">
      <div v-if="showCategoryModal" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeCategoryModal"></div>
          <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-auto p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
              {{ editingCategory ? 'Edit Category' : 'Add Category' }}
            </h3>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Display Name</label>
                <input
                  v-model="categoryForm.display_name"
                  type="text"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                  placeholder="e.g. Commercial Properties"
                  @keyup.enter="saveCategory"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description (Optional)</label>
                <textarea
                  v-model="categoryForm.description"
                  rows="2"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                  placeholder="Brief description..."
                ></textarea>
              </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
              <button
                @click="closeCategoryModal"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                @click="saveCategory"
                :disabled="!categoryForm.display_name || saving"
                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 disabled:opacity-50"
              >
                {{ saving ? 'Saving...' : (editingCategory ? 'Save Changes' : 'Add Category') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Delete Confirmation Modal -->
    <Transition name="modal">
      <div v-if="deleteModal.show" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="deleteModal.show = false"></div>
          <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-auto p-6">
            <div class="flex items-center gap-4 mb-4">
              <div class="flex-shrink-0 bg-primary-100 rounded-full p-3">
                <svg class="h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
              </div>
              <div class="text-left">
                <h3 class="text-lg font-medium text-gray-900">Delete {{ deleteModal.type === 'industry' ? 'Industry' : 'Category' }}</h3>
                <p class="text-sm text-gray-500">Are you sure you want to delete "{{ deleteModal.name }}"?</p>
              </div>
            </div>
            <div class="flex justify-end gap-3">
              <button
                @click="deleteModal.show = false"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                @click="executeDelete"
                :disabled="saving"
                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 disabled:opacity-50"
              >
                {{ saving ? 'Deleting...' : 'Delete' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import draggable from 'vuedraggable'
import axios from 'axios'

interface Industry {
  id: number
  name: string
  display_name: string
  description?: string
  sort_order: number
  is_active: boolean
  sub_industries?: SubIndustry[]
  campaign_categories?: CampaignCategory[]
}

interface SubIndustry {
  id: number
  industry_id: number
  name: string
  display_name: string
  description?: string
  sort_order: number
}

interface CampaignCategory {
  id: number
  industry_id: number
  name: string
  display_name: string
  description?: string
  sort_order: number
}

const loading = ref(false)
const saving = ref(false)
const industries = ref<Industry[]>([])
const searchQuery = ref('')
const statusFilter = ref('all')
const sortBy = ref('sort_order')
const collapsedIndustries = ref<Set<number>>(new Set())

// Modals
const showAddModal = ref(false)
const editingIndustry = ref<Industry | null>(null)
const industryForm = reactive({
  display_name: '',
  description: ''
})

const showCategoryModal = ref(false)
const editingCategory = ref<SubIndustry | CampaignCategory | null>(null)
const categoryIndustry = ref<Industry | null>(null)
const categoryType = ref<'sub_industry' | 'campaign_category'>('sub_industry')
const categoryForm = reactive({
  display_name: '',
  description: ''
})

const deleteModal = reactive({
  show: false,
  type: '' as 'industry' | 'sub_industry' | 'campaign_category',
  id: 0,
  name: ''
})

// Computed
const totalSubIndustries = computed(() => {
  return industries.value.reduce((sum, ind) => sum + (ind.sub_industries?.length || 0), 0)
})

const totalCampaignCategories = computed(() => {
  return industries.value.reduce((sum, ind) => sum + (ind.campaign_categories?.length || 0), 0)
})

const filteredIndustries = computed(() => {
  return industries.value.filter(ind => matchesFilters(ind))
})

// Filter matching
const matchesFilters = (industry: Industry): boolean => {
  // Status filter
  if (statusFilter.value === 'active' && !industry.is_active) return false
  if (statusFilter.value === 'inactive' && industry.is_active) return false

  // Search filter
  if (!searchQuery.value) return true
  const q = searchQuery.value.toLowerCase()
  if (industry.display_name.toLowerCase().includes(q)) return true
  if (industry.name.toLowerCase().includes(q)) return true
  if (industry.sub_industries?.some(c => c.display_name.toLowerCase().includes(q))) return true
  if (industry.campaign_categories?.some(c => c.display_name.toLowerCase().includes(q))) return true
  return false
}

// Collapse/Expand
const toggleCollapse = (id: number) => {
  if (collapsedIndustries.value.has(id)) {
    collapsedIndustries.value.delete(id)
  } else {
    collapsedIndustries.value.add(id)
  }
}

const expandAll = () => {
  collapsedIndustries.value.clear()
}

const collapseAll = () => {
  industries.value.forEach(ind => collapsedIndustries.value.add(ind.id))
}

// Fetch
const fetchIndustries = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/industries')
    industries.value = response.data.data
  } catch (error: any) {
    console.error('Error fetching industries:', error)
    alert('Failed to load industries')
  } finally {
    loading.value = false
  }
}

// Drag handlers
const onDragEnd = async () => {
  for (let i = 0; i < industries.value.length; i++) {
    const industry = industries.value[i]
    if (industry.sort_order !== i) {
      try {
        await axios.put(`/api/industries/${industry.id}`, {
          name: industry.name,
          display_name: industry.display_name,
          sort_order: i
        })
      } catch (error) {
        console.error('Failed to update sort order:', error)
      }
    }
  }
}

const onCategoryDragEnd = async (industry: Industry, type: 'sub_industries' | 'campaign_categories') => {
  const items = industry[type] || []
  for (let i = 0; i < items.length; i++) {
    const item = items[i]
    if (item.sort_order !== i) {
      try {
        const endpoint = type === 'sub_industries'
          ? `/api/industries/sub-industries/${item.id}`
          : `/api/campaign-categories/${item.id}`
        await axios.put(endpoint, {
          name: item.name,
          display_name: item.display_name,
          sort_order: i
        })
      } catch (error) {
        console.error('Failed to update sort order:', error)
      }
    }
  }
}

// Industry CRUD
const editIndustry = (industry: Industry) => {
  editingIndustry.value = industry
  industryForm.display_name = industry.display_name
  industryForm.description = industry.description || ''
}

const closeIndustryModal = () => {
  showAddModal.value = false
  editingIndustry.value = null
  industryForm.display_name = ''
  industryForm.description = ''
}

const saveIndustry = async () => {
  if (!industryForm.display_name) return
  saving.value = true
  try {
    const slug = industryForm.display_name.toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '')
    if (editingIndustry.value) {
      await axios.put(`/api/industries/${editingIndustry.value.id}`, {
        name: slug,
        display_name: industryForm.display_name,
        description: industryForm.description
      })
    } else {
      await axios.post('/api/industries', {
        name: slug,
        display_name: industryForm.display_name,
        description: industryForm.description
      })
    }
    closeIndustryModal()
    await fetchIndustries()
  } catch (error: any) {
    alert('Failed to save: ' + (error.response?.data?.message || error.message))
  } finally {
    saving.value = false
  }
}

const confirmDeleteIndustry = (industry: Industry) => {
  deleteModal.show = true
  deleteModal.type = 'industry'
  deleteModal.id = industry.id
  deleteModal.name = industry.display_name
}

// Category CRUD
const showAddCategoryModal = (industry: Industry, type: 'sub_industry' | 'campaign_category') => {
  categoryIndustry.value = industry
  categoryType.value = type
  showCategoryModal.value = true
}

const editCategory = (cat: SubIndustry | CampaignCategory, type: 'sub_industry' | 'campaign_category') => {
  editingCategory.value = cat
  categoryType.value = type
  categoryForm.display_name = cat.display_name
  categoryForm.description = cat.description || ''
  showCategoryModal.value = true
}

const closeCategoryModal = () => {
  showCategoryModal.value = false
  editingCategory.value = null
  categoryIndustry.value = null
  categoryForm.display_name = ''
  categoryForm.description = ''
}

const saveCategory = async () => {
  if (!categoryForm.display_name) return
  saving.value = true
  try {
    const slug = categoryForm.display_name.toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '')

    if (editingCategory.value) {
      const endpoint = categoryType.value === 'sub_industry'
        ? `/api/industries/sub-industries/${editingCategory.value.id}`
        : `/api/campaign-categories/${editingCategory.value.id}`
      await axios.put(endpoint, {
        name: slug,
        display_name: categoryForm.display_name,
        description: categoryForm.description
      })
    } else if (categoryIndustry.value) {
      const endpoint = categoryType.value === 'sub_industry'
        ? `/api/industries/${categoryIndustry.value.id}/sub-industries`
        : `/api/industries/${categoryIndustry.value.id}/campaign-categories`
      await axios.post(endpoint, {
        name: slug,
        display_name: categoryForm.display_name,
        description: categoryForm.description
      })
    }
    closeCategoryModal()
    await fetchIndustries()
  } catch (error: any) {
    alert('Failed to save: ' + (error.response?.data?.message || error.message))
  } finally {
    saving.value = false
  }
}

const confirmDeleteCategory = (cat: SubIndustry | CampaignCategory, type: 'sub_industry' | 'campaign_category') => {
  deleteModal.show = true
  deleteModal.type = type
  deleteModal.id = cat.id
  deleteModal.name = cat.display_name
}

// Delete execution
const executeDelete = async () => {
  saving.value = true
  try {
    let endpoint = ''
    if (deleteModal.type === 'industry') {
      endpoint = `/api/industries/${deleteModal.id}`
    } else if (deleteModal.type === 'sub_industry') {
      endpoint = `/api/industries/sub-industries/${deleteModal.id}`
    } else {
      endpoint = `/api/campaign-categories/${deleteModal.id}`
    }
    await axios.delete(endpoint)
    deleteModal.show = false
    await fetchIndustries()
  } catch (error: any) {
    alert('Failed to delete: ' + (error.response?.data?.message || error.message))
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchIndustries()
})
</script>

<style scoped>
.collapse-enter-active,
.collapse-leave-active {
  transition: all 0.2s ease;
  overflow: hidden;
}

.collapse-enter-from,
.collapse-leave-to {
  opacity: 0;
  max-height: 0;
}

.collapse-enter-to,
.collapse-leave-from {
  opacity: 1;
  max-height: 1000px;
}

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
