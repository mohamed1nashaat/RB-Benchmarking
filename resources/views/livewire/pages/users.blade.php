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
                @if($isSuperAdmin)
                    <h1 class="text-2xl font-bold text-gray-900">{{ __('pages.admin_users.title') }}</h1>
                    <p class="mt-1 text-sm text-gray-500">{{ __('pages.admin_users.description') }}</p>
                @else
                    <h1 class="text-2xl font-bold text-gray-900">{{ __('pages.users.title') }}</h1>
                    <p class="mt-1 text-sm text-gray-500">{{ __('pages.users.description') }}</p>
                @endif
            </div>
            @if($isSuperAdmin)
                <button
                    wire:click="$set('showCreateModal', true)"
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                    <x-heroicon-o-user-plus class="w-5 h-5 mr-2" />
                    {{ __('pages.admin_users.create_user') }}
                </button>
            @else
                <button
                    wire:click="openInviteModal"
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                    <x-heroicon-o-user-plus class="w-5 h-5 mr-2" />
                    {{ __('pages.users.invite_user') }}
                </button>
            @endif
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8">
            <button
                wire:click="$set('activeTab', 'users')"
                class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'users' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
            >
                {{ __('pages.users.tabs.users') }}
                @if($usersPaginated->total() > 0)
                    <span class="ml-2 bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-xs">
                        {{ $usersPaginated->total() }}
                    </span>
                @endif
            </button>
            <button
                wire:click="$set('activeTab', 'invitations')"
                class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'invitations' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
            >
                {{ __('pages.users.tabs.invitations') }}
                @if($invitationsList->count() > 0)
                    <span class="ml-2 bg-yellow-100 text-yellow-600 py-0.5 px-2 rounded-full text-xs">
                        {{ $invitationsList->count() }}
                    </span>
                @endif
            </button>
        </nav>
    </div>

    {{-- Users Tab --}}
    @if($activeTab === 'users')
        {{-- Filters --}}
        <div class="bg-white shadow rounded-lg p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-{{ $isSuperAdmin ? '4' : '3' }} gap-4">
                {{-- Search --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('labels.search') }}</label>
                    <input
                        wire:model.live.debounce.500ms="search"
                        type="text"
                        placeholder="{{ __('pages.users.search_placeholder') }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    />
                </div>

                {{-- Tenant Filter (super admin only) --}}
                @if($isSuperAdmin)
                    <div>
                        <x-select2
                            wire-model="tenantFilter"
                            :label="__('labels.tenant')"
                            :placeholder="__('filters.all_tenants')"
                            :options="collect($tenants)->mapWithKeys(fn($t) => [$t->id => $t->name])->all()"
                        />
                    </div>
                @endif

                {{-- Role Filter --}}
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
        @if($usersPaginated->count() > 0)
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <x-table-scroll max-height="600px">
                <table class="min-w-full divide-y divide-gray-200">
                    @if($isSuperAdmin)
                        {{-- Super Admin Table --}}
                        <thead class="bg-gray-100">
                            <tr>
                                <th
                                    wire:click="handleSort('name')"
                                    class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-200 bg-gray-100 sticky left-0 z-10 border-r border-gray-200"
                                >
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
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    {{ __('labels.role') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    {{ __('labels.tenants') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    {{ __('labels.last_login') }}
                                </th>
                                <th
                                    wire:click="handleSort('created_at')"
                                    class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-200"
                                >
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
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    {{ __('labels.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($usersPaginated as $user)
                                <tr class="hover:bg-gray-50" wire:key="user-{{ $user->id }}">
                                    <td class="px-6 py-4 whitespace-nowrap sticky left-0 z-10 sticky-col border-r border-gray-200">
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
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $user->name }}
                                                    @if($user->id === 1 || $user->email === 'technical@redbananas.com')
                                                        <span class="ml-2 px-1.5 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 rounded">Super Admin</span>
                                                    @endif
                                                </div>
                                                <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-wrap gap-1">
                                            @if($user->id === 1 || $user->email === 'technical@redbananas.com')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    Super Admin
                                                </span>
                                            @endif
                                            @php
                                                $distinctRoles = $user->tenants->pluck('pivot.role')->unique()->sort();
                                            @endphp
                                            @foreach($distinctRoles as $role)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $role === 'admin' ? 'bg-primary-100 text-primary-800' : 'bg-gray-100 text-gray-800' }}">
                                                    {{ __('roles.' . $role) }}
                                                </span>
                                            @endforeach
                                            @if(!($user->id === 1 || $user->email === 'technical@redbananas.com') && $distinctRoles->isEmpty())
                                                <span class="text-sm text-gray-400">—</span>
                                            @endif
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
                                            @if($tenants->count() > $user->tenants->count())
                                                <button
                                                    type="button"
                                                    wire:click="showAssignTenant({{ $user->id }})"
                                                    class="inline-flex items-center justify-center w-5 h-5 text-gray-400 hover:text-primary-600 rounded hover:bg-primary-50 transition-colors"
                                                    title="{{ __('pages.admin_users.assign_tenant') }}"
                                                >
                                                    <x-heroicon-s-plus-circle class="h-4 w-4" />
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : __('pages.users.never') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $user->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-2">
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
                    @else
                        {{-- Regular Admin Table --}}
                        <thead class="bg-gray-100">
                            <tr>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-200 bg-gray-100 sticky left-0 z-10 border-r border-gray-200"
                                    wire:click="handleSort('name')"
                                >
                                    <div class="flex items-center gap-1">
                                        {{ __('labels.user') }}
                                        @if($sortBy === 'name')
                                            @if($sortOrder === 'asc')
                                                <x-heroicon-s-chevron-up class="h-4 w-4 text-primary-600" />
                                            @else
                                                <x-heroicon-s-chevron-down class="h-4 w-4 text-primary-600" />
                                            @endif
                                        @else
                                            <x-heroicon-o-chevron-up-down class="h-4 w-4 text-gray-400" />
                                        @endif
                                    </div>
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-200"
                                    wire:click="handleSort('role')"
                                >
                                    <div class="flex items-center gap-1">
                                        {{ __('labels.role') }}
                                        @if($sortBy === 'role')
                                            @if($sortOrder === 'asc')
                                                <x-heroicon-s-chevron-up class="h-4 w-4 text-primary-600" />
                                            @else
                                                <x-heroicon-s-chevron-down class="h-4 w-4 text-primary-600" />
                                            @endif
                                        @else
                                            <x-heroicon-o-chevron-up-down class="h-4 w-4 text-gray-400" />
                                        @endif
                                    </div>
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-200"
                                    wire:click="handleSort('joined_at')"
                                >
                                    <div class="flex items-center gap-1">
                                        {{ __('labels.joined') }}
                                        @if($sortBy === 'joined_at')
                                            @if($sortOrder === 'asc')
                                                <x-heroicon-s-chevron-up class="h-4 w-4 text-primary-600" />
                                            @else
                                                <x-heroicon-s-chevron-down class="h-4 w-4 text-primary-600" />
                                            @endif
                                        @else
                                            <x-heroicon-o-chevron-up-down class="h-4 w-4 text-gray-400" />
                                        @endif
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    {{ __('labels.last_login') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    {{ __('labels.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($usersPaginated as $user)
                                <tr class="hover:bg-gray-50" wire:key="user-{{ $user->id }}">
                                    <td class="px-6 py-4 whitespace-nowrap sticky left-0 z-10 sticky-col border-r border-gray-200">
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
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $user->name }}
                                                    @if($user->id === auth()->id())
                                                        <span class="ml-2 text-xs text-gray-500">({{ __('pages.users.you') }})</span>
                                                    @endif
                                                </div>
                                                <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($user->id === auth()->id())
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $user->pivot->role === 'admin' ? 'bg-primary-100 text-primary-800' : 'bg-gray-100 text-gray-800' }}">
                                                {{ __('roles.' . $user->pivot->role) }}
                                            </span>
                                        @else
                                            <select
                                                wire:change="changeUserRole({{ $user->id }}, $event.target.value)"
                                                class="text-sm rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                            >
                                                <option value="viewer" @selected($user->pivot->role === 'viewer')>{{ __('roles.viewer') }}</option>
                                                <option value="admin" @selected($user->pivot->role === 'admin')>{{ __('roles.admin') }}</option>
                                            </select>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $user->pivot->joined_at ? \Carbon\Carbon::parse($user->pivot->joined_at)->format('M d, Y') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : __('pages.users.never') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        @if($user->id !== auth()->id())
                                            <button
                                                wire:click="confirmRemoveUser({{ $user->id }})"
                                                class="text-red-600 hover:text-red-900"
                                            >
                                                {{ __('pages.users.remove') }}
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    @endif
                </table>
                </x-table-scroll>
            </div>

            {{-- Pagination --}}
            <x-pagination-wrapper :paginator="$usersPaginated" :per-page-options="[10, 25, 50]" />
        @else
            {{-- Empty State --}}
            <div class="text-center py-12 bg-white shadow rounded-lg">
                <x-heroicon-o-users class="mx-auto h-12 w-12 text-gray-400" />
                @if($isSuperAdmin)
                    <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('pages.admin_users.no_users') }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ __('pages.admin_users.no_users_description') }}</p>
                @else
                    <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('pages.users.no_users') }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ __('pages.users.no_users_description') }}</p>
                    <div class="mt-6">
                        <button
                            wire:click="openInviteModal"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700"
                        >
                            <x-heroicon-o-user-plus class="w-5 h-5 mr-2" />
                            {{ __('pages.users.invite_user') }}
                        </button>
                    </div>
                @endif
            </div>
        @endif
    @endif

    {{-- Invitations Tab --}}
    @if($activeTab === 'invitations')
        @if($invitationsList->count() > 0)
            <div class="bg-white shadow rounded-lg divide-y divide-gray-200">
                @foreach($invitationsList as $invitation)
                    <div class="p-4 flex items-center justify-between" wire:key="invitation-{{ $invitation->id }}">
                        <div>
                            <div class="text-sm font-medium text-gray-900">{{ $invitation->email }}</div>
                            <div class="text-sm text-gray-500">
                                @if($isSuperAdmin && $invitation->tenant)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 mr-1">
                                        {{ $invitation->tenant->name }}
                                    </span>
                                @endif
                                {{ __('pages.users.invited_as') }}
                                <span class="font-medium {{ $invitation->role === 'admin' ? 'text-primary-600' : 'text-gray-600' }}">
                                    {{ __('roles.' . $invitation->role) }}
                                </span>
                                {{ __('pages.users.by') }} {{ $invitation->inviter?->name ?? __('pages.users.unknown') }}
                            </div>
                            <div class="text-xs text-gray-400 mt-1">
                                {{ __('pages.users.expires') }} {{ $invitation->expires_at->diffForHumans() }}
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button
                                wire:click="resendInvitation({{ $invitation->id }})"
                                @if($resendingId === $invitation->id) disabled @endif
                                class="px-3 py-1.5 text-sm border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50"
                            >
                                {{ $resendingId === $invitation->id ? __('common.sending') : __('pages.users.resend') }}
                            </button>
                            <button
                                wire:click="cancelInvitation({{ $invitation->id }})"
                                @if($cancellingId === $invitation->id) disabled @endif
                                class="px-3 py-1.5 text-sm text-red-600 border border-red-300 rounded-md hover:bg-red-50 disabled:opacity-50"
                            >
                                {{ $cancellingId === $invitation->id ? __('common.cancelling') : __('common.cancel') }}
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 bg-white shadow rounded-lg">
                <x-heroicon-o-envelope class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('pages.users.no_invitations') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('pages.users.no_invitations_description') }}</p>
            </div>
        @endif
    @endif

    {{-- ═══ Regular Admin Modals ═══ --}}

    @if(!$isSuperAdmin)
        {{-- Invite User Modal --}}
        @if($showInviteModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-trap.noscroll="true">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeInviteModal"></div>
                    <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full mx-auto p-6 text-left">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">
                            {{ __('pages.users.invite_user') }}
                        </h3>

                        <div class="mt-4 space-y-4">
                            {{-- Email --}}
                            <div>
                                <label for="invite_email" class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ __('labels.email') }} <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="invite_email"
                                    wire:model="inviteEmail"
                                    type="email"
                                    required
                                    placeholder="{{ __('placeholders.invite_email') }}"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('inviteEmail') border-red-300 @enderror"
                                />
                                @error('inviteEmail')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Role --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('labels.role') }} <span class="text-red-500">*</span>
                                </label>
                                <div class="space-y-2">
                                    <label
                                        class="flex items-start p-3 border rounded-lg cursor-pointer transition-colors {{ $inviteRole === 'viewer' ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:bg-gray-50' }}"
                                    >
                                        <input
                                            type="radio"
                                            wire:model="inviteRole"
                                            value="viewer"
                                            class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 mt-0.5"
                                        />
                                        <div class="ml-3">
                                            <span class="block text-sm font-medium text-gray-900">{{ __('roles.viewer') }}</span>
                                            <span class="block text-sm text-gray-500">{{ __('pages.users.role_descriptions.viewer') }}</span>
                                        </div>
                                    </label>
                                    <label
                                        class="flex items-start p-3 border rounded-lg cursor-pointer transition-colors {{ $inviteRole === 'admin' ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:bg-gray-50' }}"
                                    >
                                        <input
                                            type="radio"
                                            wire:model="inviteRole"
                                            value="admin"
                                            class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 mt-0.5"
                                        />
                                        <div class="ml-3">
                                            <span class="block text-sm font-medium text-gray-900">{{ __('roles.admin') }}</span>
                                            <span class="block text-sm text-gray-500">{{ __('pages.users.role_descriptions.admin') }}</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            {{-- Error Message --}}
                            @if($inviteError)
                                <div class="p-3 rounded-md bg-red-50 border border-red-200">
                                    <p class="text-sm text-red-600">{{ $inviteError }}</p>
                                </div>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="mt-6 flex justify-end space-x-3">
                            <button
                                wire:click="closeInviteModal"
                                @if($inviteSending) disabled @endif
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50"
                            >
                                {{ __('common.cancel') }}
                            </button>
                            <button
                                wire:click="sendInvitation"
                                @if($inviteSending || empty($inviteEmail)) disabled @endif
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50"
                            >
                                @if($inviteSending)
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ __('common.sending') }}
                                @else
                                    {{ __('pages.users.send_invitation') }}
                                @endif
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Remove User Confirmation --}}
        @if($showRemoveDialog)
            <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-trap.noscroll="true">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeRemoveDialog"></div>
                    <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-auto p-6">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="flex-shrink-0 bg-red-100 rounded-full p-3">
                                <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-red-600" />
                            </div>
                            <div class="text-left">
                                <h3 class="text-lg font-medium text-gray-900">{{ __('pages.users.remove_user') }}</h3>
                                <p class="text-sm text-gray-500">
                                    {{ __('pages.users.remove_confirm', ['name' => $userToRemoveName]) }}
                                </p>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button
                                wire:click="closeRemoveDialog"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                            >
                                {{ __('common.cancel') }}
                            </button>
                            <button
                                wire:click="removeUser"
                                @if($removeLoading) disabled @endif
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 disabled:opacity-50"
                            >
                                {{ $removeLoading ? __('common.removing') : __('pages.users.remove') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- ═══ Super Admin Modals ═══ --}}

    @if($isSuperAdmin)
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
                            <div class="flex justify-end space-x-3 pt-4">
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
                            {{-- Organizations section --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('labels.tenants') }}</label>
                                <div class="space-y-2 max-h-48 overflow-y-auto">
                                    @php
                                        $editingUser = \App\Models\User::with('tenants')->find($editUserId);
                                    @endphp

                                    {{-- Existing organization rows --}}
                                    @if($editingUser)
                                        @foreach($editingUser->tenants as $tenant)
                                            <div class="flex items-center justify-between rounded-md border border-gray-200 px-3 py-2 gap-2">
                                                <span class="text-sm text-gray-700 truncate flex-1">{{ $tenant->name }}</span>
                                                <x-select2
                                                    wire-model="editUserTenantRoles.{{ $tenant->id }}"
                                                    :options="['admin' => __('roles.admin'), 'viewer' => __('roles.viewer')]"
                                                    :allow-clear="false"
                                                    :searchable="false"
                                                />
                                                <button
                                                    type="button"
                                                    wire:click="removeTenantFromEditUser({{ $tenant->id }})"
                                                    class="p-1 text-red-400 hover:text-red-600 rounded hover:bg-red-50 transition-colors"
                                                    title="{{ __('common.remove') }}"
                                                >
                                                    <x-heroicon-s-x-mark class="h-4 w-4" />
                                                </button>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                            <div class="flex justify-end space-x-3 pt-4">
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
            @php
                $assignUser = $assignUserId ? \App\Models\User::with('tenants:id')->find($assignUserId) : null;
                $assignedTenantIds = $assignUser ? $assignUser->tenants->pluck('id')->toArray() : [];
                $availableTenants = $tenants->whereNotIn('id', $assignedTenantIds);
            @endphp
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
                                    :options="collect($availableTenants)->mapWithKeys(fn($t) => [$t->id => $t->name])->all()"
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
                            <div class="flex justify-end space-x-3 pt-4">
                                <button type="button" wire:click="$set('showAssignModal', false)" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">{{ __('common.cancel') }}</button>
                                <button type="submit" class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">{{ __('common.assign') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- Delete User Confirmation --}}
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
                        <div class="mt-5 flex justify-end space-x-3">
                            <button wire:click="$set('showDeleteDialog', false)" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">{{ __('common.cancel') }}</button>
                            <button wire:click="deleteUser" class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-red-600 hover:bg-red-700">{{ __('common.delete') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
