<div class="py-6">
    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="mb-4 rounded-xl bg-green-50 border border-green-200 p-4">
            <div class="flex">
                <x-heroicon-s-check-circle class="h-5 w-5 text-green-400" />
                <p class="ms-3 text-sm text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-4 rounded-xl bg-red-50 border border-red-200 p-4">
            <div class="flex">
                <x-heroicon-s-x-circle class="h-5 w-5 text-red-400" />
                <p class="ms-3 text-sm text-red-800">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="section-title">{{ __('pages.roles.title') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('pages.roles.description') }}</p>
            </div>
            <button
                wire:click="openCreateModal"
                class="btn-primary"
            >
                <x-heroicon-o-plus class="w-5 h-5" />
                {{ __('pages.roles.create_role') }}
            </button>
        </div>
    </div>

    {{-- Roles List --}}
    @if($this->roles->count() > 0)
        <div class="card divide-y divide-white/8">
            @foreach($this->roles as $role)
                <div class="p-6 transition-colors hover:bg-gray-950" wire:key="role-{{ $role->id }}">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-semibold tracking-tight text-white">{{ $role->display_name }}</h3>
                                @if($role->is_system)
                                    <span class="inline-flex items-center rounded-full bg-[#E1F3FE] px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-[#1F6C9F]">
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
                                    <span class="inline-flex items-center rounded-full bg-[#F1F1EF] px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-[#6B6B66]">
                                        {{ $permission->display_name }}
                                    </span>
                                @endforeach
                                @if($role->permissions->count() > 5)
                                    <span class="inline-flex items-center rounded-full bg-[#F1F1EF] px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-[#6B6B66]">
                                        +{{ $role->permissions->count() - 5 }} {{ __('pages.roles.more') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-x-2 ms-4">
                            <button
                                wire:click="openEditModal({{ $role->id }})"
                                class="btn-secondary"
                            >
                                {{ __('buttons.edit') }}
                            </button>
                            @if(!$role->is_system)
                                <button
                                    wire:click="confirmDeleteRole({{ $role->id }})"
                                    class="btn-danger"
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
        <div class="card">
            <div class="empty-state">
                <x-heroicon-o-shield-check class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="empty-title mt-4">{{ __('pages.roles.no_roles') }}</h3>
                <p class="empty-message">{{ __('pages.roles.no_roles_description') }}</p>
                <div class="mt-6">
                    <button
                        wire:click="openCreateModal"
                        class="btn-primary"
                    >
                        <x-heroicon-o-plus class="w-5 h-5" />
                        {{ __('pages.roles.create_role') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Create/Edit Role Modal --}}
    @if($showFormModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-trap.noscroll="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeFormModal"></div>
                <div class="relative card max-w-2xl w-full mx-auto p-6 text-start">
                    <h3 class="text-lg font-semibold tracking-tight text-white">
                        {{ $editingRoleId ? __('pages.roles.edit_role') : __('pages.roles.create_role') }}
                    </h3>

                    <div class="mt-4 space-y-4">
                        {{-- Role Name --}}
                        @if(!$isSystemRole)
                            <div>
                                <label for="role_display_name" class="micro-label mb-1.5 block">
                                    {{ __('pages.roles.role_name') }} <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="role_display_name"
                                    wire:model="roleDisplayName"
                                    type="text"
                                    class="input-field"
                                    placeholder="{{ __('pages.roles.role_name_placeholder') }}"
                                />
                            </div>
                        @endif

                        {{-- System Role Notice --}}
                        @if($isSystemRole)
                            <div class="rounded-lg border border-yellow-200 bg-[#FBF3DB] p-3">
                                <p class="text-sm text-[#956400]">{{ __('pages.roles.system_role_notice') }}</p>
                            </div>
                        @endif

                        {{-- Description --}}
                        @if(!$isSystemRole)
                            <div>
                                <label for="role_description" class="micro-label mb-1.5 block">
                                    {{ __('pages.roles.description_label') }}
                                </label>
                                <textarea
                                    id="role_description"
                                    wire:model="roleDescription"
                                    rows="2"
                                    class="input-field"
                                    placeholder="{{ __('pages.roles.description_placeholder') }}"
                                ></textarea>
                            </div>
                        @endif

                        {{-- Permissions --}}
                        <div>
                            <label class="micro-label mb-3 block">
                                {{ __('pages.roles.permissions') }} <span class="text-red-500">*</span>
                            </label>

                            {{-- Select All --}}
                            <div class="mb-3 flex items-center justify-between border-b border-white/10 pb-3">
                                <span class="text-sm text-gray-300">{{ __('pages.roles.select_all') }}</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input
                                        type="checkbox"
                                        wire:click="toggleAll"
                                        @checked($this->allSelected)
                                        class="sr-only peer"
                                    />
                                    <div class="w-11 h-6 bg-white/8 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-gray-900/15 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:rtl:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-gray-900 after:border-white/15 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-900"></div>
                                </label>
                            </div>

                            {{-- Permission Groups --}}
                            <div class="space-y-4 max-h-80 overflow-y-auto pe-2">
                                @foreach($this->groupedPermissions as $groupName => $groupPermissions)
                                    <div class="card">
                                        {{-- Group Header --}}
                                        <div class="bg-gray-950 px-4 py-2 border-b border-white/10 flex items-center justify-between">
                                            <span class="text-sm font-semibold text-gray-200 capitalize">{{ str_replace('_', ' ', $groupName) }}</span>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    wire:click="toggleGroup('{{ $groupName }}')"
                                                    @checked($this->isGroupSelected($groupName))
                                                    class="sr-only peer"
                                                />
                                                <div class="w-9 h-5 bg-white/8 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-gray-900/15 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:rtl:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-gray-900 after:border-white/15 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-gray-900"></div>
                                            </label>
                                        </div>
                                        {{-- Group Permissions --}}
                                        <div class="p-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            @foreach($groupPermissions as $permission)
                                                <label class="flex items-start gap-x-3 p-2 rounded hover:bg-gray-950 cursor-pointer">
                                                    <input
                                                        type="checkbox"
                                                        wire:click="togglePermission({{ $permission['id'] }})"
                                                        @checked(in_array($permission['id'], $selectedPermissions))
                                                        class="mt-0.5 h-4 w-4 rounded border-white/15 text-white focus:ring-gray-900/15"
                                                    />
                                                    <div class="flex-1 min-w-0">
                                                        <span class="block text-sm font-medium text-white">{{ $permission['display_name'] }}</span>
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
                    <div class="mt-6 flex justify-end gap-x-3">
                        <button
                            wire:click="closeFormModal"
                            class="btn-secondary"
                        >
                            {{ __('common.cancel') }}
                        </button>
                        <button
                            wire:click="saveRole"
                            @if($saving || (empty($selectedPermissions)) || (!$isSystemRole && empty($roleDisplayName))) disabled @endif
                            class="btn-primary"
                        >
                            @if($saving)
                                <svg class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
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
                <div class="relative card max-w-md w-full mx-auto p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="flex-shrink-0 bg-red-100 rounded-full p-3">
                            <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-red-600" />
                        </div>
                        <div class="text-start">
                            <h3 class="text-lg font-semibold tracking-tight text-white">{{ __('pages.roles.delete_role') }}</h3>
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
                            class="btn-secondary"
                        >
                            {{ __('common.cancel') }}
                        </button>
                        <button
                            wire:click="deleteRole"
                            @if($deleteLoading) disabled @endif
                            class="btn-danger disabled:opacity-50"
                        >
                            {{ $deleteLoading ? __('common.deleting') : __('buttons.delete') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
