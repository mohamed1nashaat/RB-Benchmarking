<div class="py-6">
    {{-- Flash Messages --}}
    @if(session()->has('message'))
        <div class="mb-4 bg-green-50 border border-green-200 rounded-lg p-4" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)">
            <div class="flex">
                <x-heroicon-s-check-circle class="h-5 w-5 text-green-400" />
                <p class="ms-3 text-sm font-medium text-green-800">{{ session('message') }}</p>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('pages.admin_users.title') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('pages.admin_users.description') }}</p>
            </div>
            @if($activeTab === 'users')
                <button wire:click="$set('showCreateModal', true)" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <x-heroicon-o-user-plus class="w-5 h-5 me-2" />
                    {{ __('pages.admin_users.create_user') }}
                </button>
            @endif
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex gap-x-8">
            <button wire:click="$set('activeTab', 'users')" class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'users' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                <x-heroicon-o-users class="w-5 h-5 inline-block me-1 -mt-0.5" />
                {{ __('navigation.users') }}
                @if($users->total())
                    <span class="ms-2 bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-xs">{{ $users->total() }}</span>
                @endif
            </button>
            <button wire:click="$set('activeTab', 'roles')" class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'roles' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                <x-heroicon-o-shield-check class="w-5 h-5 inline-block me-1 -mt-0.5" />
                {{ __('navigation.roles') }}
            </button>
        </nav>
    </div>

    {{-- Users Tab --}}
    @if($activeTab === 'users')
        {{-- Filters --}}
        <div class="bg-white shadow rounded-lg p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('labels.search') }}</label>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('pages.admin_users.search_placeholder') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                </div>
                <div>
                    <x-select2
                        wire-model="tenantFilter"
                        :label="__('labels.tenant')"
                        :placeholder="__('filters.all_tenants')"
                        :options="collect($tenants)->mapWithKeys(fn($t) => [$t->id => $t->name])->all()"
                    />
                </div>
                <div>
                    <x-select2
                        wire-model="roleFilter"
                        :label="__('labels.role')"
                        :placeholder="__('filters.all_roles')"
                        :options="['admin' => __('roles.admin'), 'viewer' => __('roles.viewer')]"
                    />
                </div>
            </div>
        </div>

        {{-- Users Table --}}
        @if($users->count() > 0)
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <x-table-scroll max-height="600px">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th wire:click="handleSort('name')" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:bg-gray-200 bg-gray-100 sticky start-0 z-10 border-e border-gray-200">
                                <div class="flex items-center gap-1">
                                    {{ __('labels.user') }}
                                    @if($sortBy === 'name')
                                        @if($sortOrder === 'asc')
                                            <x-heroicon-s-chevron-up class="h-4 w-4 text-primary-600" />
                                        @else
                                            <x-heroicon-s-chevron-down class="h-4 w-4 text-primary-600" />
                                        @endif
                                    @endif
                                </div>
                            </th>
                            <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ __('labels.tenants') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ __('labels.last_login') }}</th>
                            <th wire:click="handleSort('created_at')" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:bg-gray-200">
                                <div class="flex items-center gap-1">
                                    {{ __('labels.created') }}
                                    @if($sortBy === 'created_at')
                                        @if($sortOrder === 'asc')
                                            <x-heroicon-s-chevron-up class="h-4 w-4 text-primary-600" />
                                        @else
                                            <x-heroicon-s-chevron-down class="h-4 w-4 text-primary-600" />
                                        @endif
                                    @endif
                                </div>
                            </th>
                            <th class="px-6 py-3 text-end text-xs font-semibold text-gray-500 uppercase">{{ __('labels.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($users as $user)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap sticky start-0 z-10 sticky-col border-e border-gray-200">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            @if($user->avatar_url)
                                                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="h-10 w-10 rounded-full object-cover" />
                                            @else
                                                <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center">
                                                    <span class="text-primary-700 font-medium text-sm">{{ $this->getInitials($user->name) }}</span>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="ms-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $user->name }}
                                                @if($user->id === 1 || $user->email === 'technical@redbananas.com')
                                                    <span class="ms-2 px-1.5 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 rounded">{{ __('common.super_admin') }}</span>
                                                @endif
                                            </div>
                                            <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($user->tenants->take(3) as $tenant)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $tenant->pivot->role === 'admin' ? 'bg-primary-100 text-primary-800' : 'bg-gray-100 text-gray-800' }}" title="{{ $tenant->name }} ({{ $tenant->pivot->role }})">
                                                {{ $tenant->name }}
                                            </span>
                                        @endforeach
                                        @if($user->tenants->count() > 3)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                                +{{ $user->tenants->count() - 3 }}
                                            </span>
                                        @endif
                                        @if($user->tenants->count() === 0)
                                            <span class="text-sm text-gray-400">{{ __('pages.admin_users.no_tenants') }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : __('pages.users.never') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $user->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm font-medium">
                                    <div class="flex items-center justify-end gap-x-2">
                                        <button wire:click="showAssignTenant({{ $user->id }})" class="text-primary-600 hover:text-primary-900" title="{{ __('pages.admin_users.assign_tenant') }}">
                                            <x-heroicon-o-building-office class="h-5 w-5" />
                                        </button>
                                        <button wire:click="editUser({{ $user->id }})" class="text-gray-600 hover:text-gray-900" title="{{ __('common.edit') }}">
                                            <x-heroicon-o-pencil class="h-5 w-5" />
                                        </button>
                                        @if(!($user->id === 1 || $user->email === 'technical@redbananas.com'))
                                            <button wire:click="confirmDeleteUser({{ $user->id }})" class="text-red-600 hover:text-red-900" title="{{ __('common.delete') }}">
                                                <x-heroicon-o-trash class="h-5 w-5" />
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </x-table-scroll>
            </div>

            {{-- Pagination --}}
            <x-pagination-wrapper :paginator="$users" :per-page-options="[10, 25, 50]" />
        @else
            {{-- Empty State --}}
            <div class="text-center py-12 bg-white shadow rounded-lg">
                <x-heroicon-o-users class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('pages.admin_users.no_users') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('pages.admin_users.no_users_description') }}</p>
            </div>
        @endif
    @endif

    {{-- Roles Tab --}}
    @if($activeTab === 'roles')
        @if(count($globalRoles) > 0)
            <div class="bg-white shadow rounded-lg divide-y divide-gray-200">
                @foreach($globalRoles as $role)
                    <div class="hover:bg-gray-50">
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        @if($role['users_count'] > 0)
                                            <button wire:click="toggleGlobalRoleExpand('{{ $role['name'] }}')" class="p-1 rounded hover:bg-gray-200 transition-colors">
                                                <x-heroicon-o-chevron-down class="h-5 w-5 text-gray-500 transition-transform {{ in_array($role['name'], $expandedGlobalRoles) ? 'rotate-180' : '' }}" />
                                            </button>
                                        @else
                                            <div class="w-7"></div>
                                        @endif
                                        <h3 class="text-lg font-medium text-gray-900">{{ $role['display_name'] }}</h3>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ __('pages.roles.system_role') }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500 ms-8">{{ $role['description'] }}</p>
                                    <div class="mt-3 flex items-center gap-4 text-sm text-gray-500 ms-8">
                                        <span class="flex items-center gap-1">
                                            <x-heroicon-o-users class="h-4 w-4" />
                                            {{ $role['users_count'] }} {{ __('pages.roles.users') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 bg-white shadow rounded-lg">
                <x-heroicon-o-shield-check class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('pages.roles.no_roles') }}</h3>
            </div>
        @endif
    @endif

    {{-- Create User Modal --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data @keydown.escape.window="$wire.set('showCreateModal', false)">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black bg-opacity-25" wire:click="$set('showCreateModal', false)"></div>
                <div class="relative w-full max-w-md bg-white rounded-2xl p-6 shadow-xl">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">{{ __('pages.admin_users.create_user') }}</h3>
                    <form wire:submit="createUser" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('labels.name') }} <span class="text-red-500">*</span></label>
                            <input wire:model="newUserName" type="text" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                            @error('newUserName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('labels.email') }} <span class="text-red-500">*</span></label>
                            <input wire:model="newUserEmail" type="email" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                            @error('newUserEmail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('labels.password') }} <span class="text-red-500">*</span></label>
                            <input wire:model="newUserPassword" type="password" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                            @error('newUserPassword') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <x-select2
                                wire-model="newUserTenantId"
                                :label="__('labels.tenant')"
                                :placeholder="__('No tenant')"
                                :options="collect($tenants)->mapWithKeys(fn($t) => [$t->id => $t->name])->all()"
                            />
                        </div>
                        <div>
                            <x-select2
                                wire-model="newUserRole"
                                :label="__('labels.role')"
                                :options="['viewer' => __('roles.viewer'), 'admin' => __('roles.admin')]"
                                :allow-clear="false"
                                :searchable="false"
                            />
                        </div>
                        <div class="flex justify-end gap-x-3 pt-4">
                            <button type="button" wire:click="$set('showCreateModal', false)" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">{{ __('common.cancel') }}</button>
                            <button type="submit" class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">{{ __('common.create') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Edit User Modal --}}
    @if($showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data @keydown.escape.window="$wire.set('showEditModal', false)">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black bg-opacity-25" wire:click="$set('showEditModal', false)"></div>
                <div class="relative w-full max-w-md bg-white rounded-2xl p-6 shadow-xl">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">{{ __('pages.admin_users.edit_user') }}</h3>
                    <form wire:submit="updateUser" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('labels.name') }} <span class="text-red-500">*</span></label>
                            <input wire:model="editUserName" type="text" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                            @error('editUserName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('labels.email') }} <span class="text-red-500">*</span></label>
                            <input wire:model="editUserEmail" type="email" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                            @error('editUserEmail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('labels.password') }} <span class="text-gray-400">({{ __('leave blank to keep current') }})</span></label>
                            <input wire:model="editUserPassword" type="password" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                            @error('editUserPassword') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex justify-end gap-x-3 pt-4">
                            <button type="button" wire:click="$set('showEditModal', false)" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">{{ __('common.cancel') }}</button>
                            <button type="submit" class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">{{ __('common.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Assign Tenant Modal --}}
    @if($showAssignModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data @keydown.escape.window="$wire.set('showAssignModal', false)">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black bg-opacity-25" wire:click="$set('showAssignModal', false)"></div>
                <div class="relative w-full max-w-md bg-white rounded-2xl p-6 shadow-xl">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">{{ __('pages.admin_users.assign_tenant') }}</h3>
                    <form wire:submit="assignTenant" class="mt-4 space-y-4">
                        <div>
                            <x-select2
                                wire-model="assignTenantId"
                                :label="__('labels.tenant')"
                                :placeholder="__('Select a tenant')"
                                :options="collect($tenants)->mapWithKeys(fn($t) => [$t->id => $t->name])->all()"
                            />
                            @error('assignTenantId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <x-select2
                                wire-model="assignRole"
                                :label="__('labels.role')"
                                :options="['viewer' => __('roles.viewer'), 'admin' => __('roles.admin')]"
                                :allow-clear="false"
                                :searchable="false"
                            />
                        </div>
                        <div class="flex justify-end gap-x-3 pt-4">
                            <button type="button" wire:click="$set('showAssignModal', false)" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">{{ __('common.cancel') }}</button>
                            <button type="submit" class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">{{ __('common.assign') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation --}}
    @if($showDeleteDialog)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data @keydown.escape.window="$wire.set('showDeleteDialog', false)">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black bg-opacity-25" wire:click="$set('showDeleteDialog', false)"></div>
                <div class="relative w-full max-w-md bg-white rounded-2xl p-6 shadow-xl">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                        <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-red-600" />
                    </div>
                    <div class="mt-3 text-center">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('pages.admin_users.delete_user') }}</h3>
                        <p class="mt-2 text-sm text-gray-500">
                            {{ __('Are you sure you want to delete') }} <strong>{{ $deleteUserName }}</strong>? {{ __('This action cannot be undone.') }}
                        </p>
                    </div>
                    <div class="mt-5 flex justify-end gap-x-3">
                        <button wire:click="$set('showDeleteDialog', false)" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">{{ __('common.cancel') }}</button>
                        <button wire:click="deleteUser" class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-red-600 hover:bg-red-700">{{ __('common.delete') }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
