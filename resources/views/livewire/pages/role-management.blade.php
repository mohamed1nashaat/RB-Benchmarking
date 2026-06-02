<div class="py-6">
    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 p-4">
            <div class="flex">
                <x-heroicon-s-check-circle class="h-5 w-5 text-green-400" />
                <p class="ml-3 text-sm text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 p-4">
            <div class="flex">
                <x-heroicon-s-x-circle class="h-5 w-5 text-red-400" />
                <p class="ml-3 text-sm text-red-800">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('pages.roles.title') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('pages.roles.description') }}</p>
            </div>
            <button
                wire:click="openCreateModal"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
                <x-heroicon-o-plus class="w-5 h-5 mr-2" />
                {{ __('pages.roles.create_role') }}
            </button>
        </div>
    </div>

    {{-- Roles List --}}
    @if($this->roles->count() > 0)
        <div class="bg-white shadow rounded-lg divide-y divide-gray-200">
            @foreach($this->roles as $role)
                <div class="p-6 hover:bg-gray-50" wire:key="role-{{ $role->id }}">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-medium text-gray-900">{{ $role->display_name }}</h3>
                                @if($role->is_system)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ __('pages.roles.system_role') }}
                                    </span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-gray-500">{{ $role->description ?? __('pages.roles.no_description') }}</p>
                            <div class="mt-3 flex items-center gap-4 text-sm text-gray-500">
                                <span class="flex items-center gap-1">
                                    <x-heroicon-o-users class="h-4 w-4" />
                                    {{ $this->getUsersCountForRole($role->id) }} {{ __('pages.roles.users') }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <x-heroicon-o-key class="h-4 w-4" />
                                    {{ $role->permissions->count() }} {{ __('pages.roles.permissions') }}
                                </span>
                            </div>
                            {{-- Permission badges --}}
                            <div class="mt-3 flex flex-wrap gap-1">
                                @foreach($role->permissions->take(5) as $permission)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                                        {{ $permission->display_name }}
                                    </span>
                                @endforeach
                                @if($role->permissions->count() > 5)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">
                                        +{{ $role->permissions->count() - 5 }} {{ __('pages.roles.more') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 ml-4">
                            <button
                                wire:click="openEditModal({{ $role->id }})"
                                class="px-3 py-1.5 text-sm border border-gray-300 rounded-md hover:bg-gray-50"
                            >
                                {{ __('buttons.edit') }}
                            </button>
                            @if(!$role->is_system)
                                <button
                                    wire:click="confirmDeleteRole({{ $role->id }})"
                                    class="px-3 py-1.5 text-sm text-red-600 border border-red-300 rounded-md hover:bg-red-50"
                                >
                                    {{ __('buttons.delete') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- Empty State --}}
        <div class="text-center py-12 bg-white shadow rounded-lg">
            <x-heroicon-o-shield-check class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('pages.roles.no_roles') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ __('pages.roles.no_roles_description') }}</p>
            <div class="mt-6">
                <button
                    wire:click="openCreateModal"
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700"
                >
                    <x-heroicon-o-plus class="w-5 h-5 mr-2" />
                    {{ __('pages.roles.create_role') }}
                </button>
            </div>
        </div>
    @endif

    {{-- Create/Edit Role Modal --}}
    @if($showFormModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-trap.noscroll="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeFormModal"></div>
                <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full mx-auto p-6 text-left">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">
                        {{ $editingRoleId ? __('pages.roles.edit_role') : __('pages.roles.create_role') }}
                    </h3>

                    <div class="mt-4 space-y-4">
                        {{-- Role Name --}}
                        @if(!$isSystemRole)
                            <div>
                                <label for="role_display_name" class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ __('pages.roles.role_name') }} <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="role_display_name"
                                    wire:model="roleDisplayName"
                                    type="text"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    placeholder="{{ __('pages.roles.role_name_placeholder') }}"
                                />
                            </div>
                        @endif

                        {{-- System Role Notice --}}
                        @if($isSystemRole)
                            <div class="p-3 rounded-md bg-blue-50 border border-blue-200">
                                <p class="text-sm text-blue-700">{{ __('pages.roles.system_role_notice') }}</p>
                            </div>
                        @endif

                        {{-- Description --}}
                        @if(!$isSystemRole)
                            <div>
                                <label for="role_description" class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ __('pages.roles.description_label') }}
                                </label>
                                <textarea
                                    id="role_description"
                                    wire:model="roleDescription"
                                    rows="2"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    placeholder="{{ __('pages.roles.description_placeholder') }}"
                                ></textarea>
                            </div>
                        @endif

                        {{-- Permissions --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                {{ __('pages.roles.permissions') }} <span class="text-red-500">*</span>
                            </label>

                            {{-- Select All --}}
                            <div class="mb-3 flex items-center justify-between border-b border-gray-200 pb-3">
                                <span class="text-sm text-gray-600">{{ __('pages.roles.select_all') }}</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input
                                        type="checkbox"
                                        wire:click="toggleAll"
                                        @checked($this->allSelected)
                                        class="sr-only peer"
                                    />
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                                </label>
                            </div>

                            {{-- Permission Groups --}}
                            <div class="space-y-4 max-h-80 overflow-y-auto pr-2">
                                @foreach($this->groupedPermissions as $groupName => $groupPermissions)
                                    <div class="border border-gray-200 rounded-lg">
                                        {{-- Group Header --}}
                                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                                            <span class="font-medium text-sm text-gray-700 capitalize">{{ str_replace('_', ' ', $groupName) }}</span>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    wire:click="toggleGroup('{{ $groupName }}')"
                                                    @checked($this->isGroupSelected($groupName))
                                                    class="sr-only peer"
                                                />
                                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-600"></div>
                                            </label>
                                        </div>
                                        {{-- Group Permissions --}}
                                        <div class="p-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            @foreach($groupPermissions as $permission)
                                                <label class="flex items-start space-x-3 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                                    <input
                                                        type="checkbox"
                                                        wire:click="togglePermission({{ $permission['id'] }})"
                                                        @checked(in_array($permission['id'], $selectedPermissions))
                                                        class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded mt-0.5"
                                                    />
                                                    <div class="flex-1 min-w-0">
                                                        <span class="block text-sm font-medium text-gray-900">{{ $permission['display_name'] }}</span>
                                                        @if(!empty($permission['description']))
                                                            <span class="block text-xs text-gray-500 truncate">{{ $permission['description'] }}</span>
                                                        @endif
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <p class="mt-2 text-sm text-gray-500">
                                {{ count($selectedPermissions) }} {{ __('pages.roles.permissions_selected') }}
                            </p>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            wire:click="closeFormModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                        >
                            {{ __('common.cancel') }}
                        </button>
                        <button
                            wire:click="saveRole"
                            @if($saving || (empty($selectedPermissions)) || (!$isSystemRole && empty($roleDisplayName))) disabled @endif
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50"
                        >
                            @if($saving)
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('common.saving') }}
                            @else
                                {{ $editingRoleId ? __('buttons.save') : __('pages.roles.create_role') }}
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation --}}
    @if($showDeleteDialog)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-trap.noscroll="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeDeleteDialog"></div>
                <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-auto p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="flex-shrink-0 bg-red-100 rounded-full p-3">
                            <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-red-600" />
                        </div>
                        <div class="text-left">
                            <h3 class="text-lg font-medium text-gray-900">{{ __('pages.roles.delete_role') }}</h3>
                            <p class="text-sm text-gray-500">
                                @if($roleToDeleteUsersCount > 0)
                                    {{ __('pages.roles.delete_message_with_users', ['name' => $roleToDeleteName, 'count' => $roleToDeleteUsersCount]) }}
                                @else
                                    {{ __('pages.roles.delete_message', ['name' => $roleToDeleteName]) }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button
                            wire:click="closeDeleteDialog"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        >
                            {{ __('common.cancel') }}
                        </button>
                        <button
                            wire:click="deleteRole"
                            @if($deleteLoading) disabled @endif
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 disabled:opacity-50"
                        >
                            {{ $deleteLoading ? __('common.deleting') : __('buttons.delete') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
