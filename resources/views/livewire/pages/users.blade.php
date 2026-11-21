<div class="py-6">
    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="mb-4 rounded-xl bg-green-500/15 border border-green-500/30 p-4">
            <div class="flex">
                <x-heroicon-s-check-circle class="h-5 w-5 text-green-400" />
                <p class="ms-3 text-sm text-green-400">{{ session('success') }}</p>
            </div>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-4 rounded-xl bg-red-500/15 border border-red-500/30 p-4">
            <div class="flex">
                <x-heroicon-s-x-circle class="h-5 w-5 text-red-400" />
                <p class="ms-3 text-sm text-red-400">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                @if($isSuperAdmin)
                    <h1 class="section-title">{{ __('pages.admin_users.title') }}</h1>
                    <p class="mt-1 text-sm text-gray-500">{{ __('pages.admin_users.description') }}</p>
                @else
                    <h1 class="section-title">{{ __('pages.users.title') }}</h1>
                    <p class="mt-1 text-sm text-gray-500">{{ __('pages.users.description') }}</p>
                @endif
            </div>
            @if($isSuperAdmin)
                <button
                    wire:click="$set('showCreateModal', true)"
                    class="btn-primary shrink-0"
                >
                    <x-heroicon-o-user-plus class="w-5 h-5 me-2" />
                    {{ __('pages.admin_users.create_user') }}
                </button>
            @elseif($this->canInviteUsers())
                <button
                    wire:click="openInviteModal"
                    class="btn-primary shrink-0"
                >
                    <x-heroicon-o-user-plus class="w-5 h-5 me-2" />
                    {{ __('pages.users.invite_user') }}
                </button>
            @endif
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-white/10 mb-6">
        <nav class="-mb-px flex gap-x-8">
            <button
                wire:click="$set('activeTab', 'users')"
                class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'users' ? 'border-brand-600 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-200 hover:border-white/15' }}"
            >
                {{ __('pages.users.tabs.users') }}
                @if($usersPaginated->total() > 0)
                    <span class="ms-2 badge-neutral py-0.5 px-2 rounded-full text-xs font-semibold">
                        {{ $usersPaginated->total() }}
                    </span>
                @endif
            </button>
            <button
                wire:click="$set('activeTab', 'invitations')"
                class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'invitations' ? 'border-brand-600 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-200 hover:border-white/15' }}"
            >
                {{ __('pages.users.tabs.invitations') }}
                @if($invitationsList->count() > 0)
                    <span class="ms-2 bg-amber-500/15 text-amber-400 py-0.5 px-2 rounded-full text-xs font-semibold">
                        {{ $invitationsList->count() }}
                    </span>
                @endif
            </button>
        </nav>
    </div>

    {{-- Users Tab --}}
    @if($activeTab === 'users')
        {{-- Filters --}}
        <div class="filter-bar mb-6">
            <div class="grid grid-cols-1 md:grid-cols-{{ $isSuperAdmin ? '4' : '3' }} gap-4 w-full">
                {{-- Search --}}
                <div>
                    <label class="micro-label block mb-1">{{ __('labels.search') }}</label>
                    <input
                        wire:model.live.debounce.500ms="search"
                        type="text"
                        placeholder="{{ __('pages.users.search_placeholder') }}"
                        class="input-field"
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
                        :options="$roleOptions"
                    />
                </div>

            </div>
        </div>

        {{-- Users Table --}}
        @if($usersPaginated->count() > 0)
            <div class="card overflow-hidden">
                <x-table-scroll max-height="600px">
                <table class="data-table">
                    @if($isSuperAdmin)
                        {{-- Super Admin Table --}}
                        <thead>
                            <tr>
                                <th
                                    wire:click="handleSort('name')"
                                    class="sticky start-0 z-10 cursor-pointer hover:bg-white/5"
                                >
                                    <div class="flex items-center gap-1">
                                        {{ __('labels.user') }}
                                        @if($sortBy === 'name')
                                            @if($sortOrder === 'asc')
                                                <x-heroicon-s-chevron-up class="h-4 w-4 text-white" />
                                            @else
                                                <x-heroicon-s-chevron-down class="h-4 w-4 text-white" />
                                            @endif
                                        @endif
                                    </div>
                                </th>
                                <th>
                                    {{ __('labels.role') }}
                                </th>
                                <th>
                                    {{ __('labels.tenants') }}
                                </th>
                                <th>
                                    {{ __('labels.last_login') }}
                                </th>
                                <th
                                    wire:click="handleSort('created_at')"
                                    class="cursor-pointer hover:bg-white/5"
                                >
                                    <div class="flex items-center gap-1">
                                        {{ __('labels.created') }}
                                        @if($sortBy === 'created_at')
                                            @if($sortOrder === 'asc')
                                                <x-heroicon-s-chevron-up class="h-4 w-4 text-white" />
                                            @else
                                                <x-heroicon-s-chevron-down class="h-4 w-4 text-white" />
                                            @endif
                                        @endif
                                    </div>
                                </th>
                                <th class="!text-end">
                                    {{ __('labels.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($usersPaginated as $user)
                                <tr wire:key="user-{{ $user->id }}">
                                    <td class="whitespace-nowrap sticky start-0 z-10 sticky-col border-e border-white/10">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                @if($user->avatar_url)
                                                    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="h-10 w-10 rounded-full object-cover" />
                                                @else
                                                    <div class="h-10 w-10 rounded-full bg-white/5 flex items-center justify-center">
                                                        <span class="text-gray-300 font-medium text-sm">{{ $this->getInitials($user->name) }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="ms-4">
                                                <div class="text-sm font-medium text-white">
                                                    {{ $user->name }}
                                                    @if($user->id === 1 || $user->email === 'technical@redbananas.com')
                                                        <span class="ms-2 bg-amber-500/15 text-amber-400 rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5">{{ __('common.super_admin') }}</span>
                                                    @endif
                                                </div>
                                                <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div class="flex flex-wrap gap-1">
                                            @if($user->id === 1 || $user->email === 'technical@redbananas.com')
                                                <span class="bg-amber-500/15 text-amber-400 rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5">
                                                    {{ __('common.super_admin') }}
                                                </span>
                                            @endif
                                            @php
                                                $distinctRoleIds = $user->tenants->pluck('pivot.tenant_role_id')->filter()->unique();
                                            @endphp
                                            @foreach($distinctRoleIds as $roleId)
                                                @php
                                                    $roleForBadge = $this->getTenantRoleById($roleId);
                                                @endphp
                                                <span class="{{ $roleForBadge?->isAdmin() ? 'bg-brand-500/15 text-brand-400' : 'badge-neutral' }} rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5">
                                                    {{ $roleForBadge?->display_name ?? __('pages.roles.no_role_assigned') }}
                                                </span>
                                            @endforeach
                                            @if(!($user->id === 1 || $user->email === 'technical@redbananas.com') && $distinctRoleIds->isEmpty())
                                                <span class="text-sm text-gray-400">—</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($user->tenants->take(3) as $tenant)
                                                @php
                                                    $tenantRoleForBadge = $this->getTenantRoleById($tenant->pivot->tenant_role_id);
                                                @endphp
                                                <span class="{{ $tenantRoleForBadge?->isAdmin() ? 'bg-brand-500/15 text-brand-400' : 'badge-neutral' }} rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5" title="{{ $tenant->name }} ({{ $tenantRoleForBadge?->display_name ?? $tenant->pivot->role }})">
                                                    {{ $tenant->name }}
                                                </span>
                                            @endforeach
                                            @if($user->tenants->count() > 3)
                                                <span class="badge-neutral rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5">
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
                                                    class="inline-flex items-center justify-center w-5 h-5 text-gray-400 hover:text-white rounded hover:bg-white/5 transition-colors"
                                                    title="{{ __('pages.admin_users.assign_tenant') }}"
                                                >
                                                    <x-heroicon-s-plus-circle class="h-4 w-4" />
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap text-sm text-gray-500">
                                        {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : __('pages.users.never') }}
                                    </td>
                                    <td class="whitespace-nowrap text-sm text-gray-500">
                                        {{ $user->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="whitespace-nowrap text-end text-sm font-medium">
                                        <div class="flex items-center justify-end gap-x-2">
                                            <button wire:click="editUser({{ $user->id }})" class="text-gray-300 hover:text-white" title="{{ __('common.edit') }}">
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
                        <thead>
                            <tr>
                                <th
                                    scope="col"
                                    class="sticky start-0 z-10 cursor-pointer hover:bg-white/5"
                                    wire:click="handleSort('name')"
                                >
                                    <div class="flex items-center gap-1">
                                        {{ __('labels.user') }}
                                        @if($sortBy === 'name')
                                            @if($sortOrder === 'asc')
                                                <x-heroicon-s-chevron-up class="h-4 w-4 text-white" />
                                            @else
                                                <x-heroicon-s-chevron-down class="h-4 w-4 text-white" />
                                            @endif
                                        @else
                                            <x-heroicon-o-chevron-up-down class="h-4 w-4 text-gray-400" />
                                        @endif
                                    </div>
                                </th>
                                <th
                                    scope="col"
                                    class="cursor-pointer hover:bg-white/5"
                                    wire:click="handleSort('role')"
                                >
                                    <div class="flex items-center gap-1">
                                        {{ __('labels.role') }}
                                        @if($sortBy === 'role')
                                            @if($sortOrder === 'asc')
                                                <x-heroicon-s-chevron-up class="h-4 w-4 text-white" />
                                            @else
                                                <x-heroicon-s-chevron-down class="h-4 w-4 text-white" />
                                            @endif
                                        @else
                                            <x-heroicon-o-chevron-up-down class="h-4 w-4 text-gray-400" />
                                        @endif
                                    </div>
                                </th>
                                <th
                                    scope="col"
                                    class="cursor-pointer hover:bg-white/5"
                                    wire:click="handleSort('joined_at')"
                                >
                                    <div class="flex items-center gap-1">
                                        {{ __('labels.joined') }}
                                        @if($sortBy === 'joined_at')
                                            @if($sortOrder === 'asc')
                                                <x-heroicon-s-chevron-up class="h-4 w-4 text-white" />
                                            @else
                                                <x-heroicon-s-chevron-down class="h-4 w-4 text-white" />
                                            @endif
                                        @else
                                            <x-heroicon-o-chevron-up-down class="h-4 w-4 text-gray-400" />
                                        @endif
                                    </div>
                                </th>
                                <th scope="col">
                                    {{ __('labels.last_login') }}
                                </th>
                                <th scope="col" class="!text-end">
                                    {{ __('labels.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($usersPaginated as $user)
                                <tr wire:key="user-{{ $user->id }}">
                                    <td class="whitespace-nowrap sticky start-0 z-10 sticky-col border-e border-white/10">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                @if($user->avatar_url)
                                                    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="h-10 w-10 rounded-full object-cover" />
                                                @else
                                                    <div class="h-10 w-10 rounded-full bg-white/5 flex items-center justify-center">
                                                        <span class="text-gray-300 font-medium text-sm">{{ $this->getInitials($user->name) }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="ms-4">
                                                <div class="text-sm font-medium text-white">
                                                    {{ $user->name }}
                                                    @if($user->id === auth()->id())
                                                        <span class="ms-2 text-xs text-gray-500">({{ __('pages.users.you') }})</span>
                                                    @endif
                                                </div>
                                                <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        @php
                                            $userRole = $roles->firstWhere('id', $user->pivot->tenant_role_id);
                                        @endphp
                                        @if($user->id === auth()->id() || !$this->canManageUsers())
                                            <span class="{{ $userRole?->isAdmin() ? 'bg-brand-500/15 text-brand-400' : 'badge-neutral' }} rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5">
                                                {{ $userRole?->display_name ?? __('roles.' . $user->pivot->role) }}
                                            </span>
                                        @else
                                            <select
                                                wire:change="changeUserRole({{ $user->id }}, $event.target.value)"
                                                class="input-field !w-auto"
                                            >
                                                @foreach($roles as $roleOption)
                                                    <option value="{{ $roleOption->id }}" @selected($user->pivot->tenant_role_id == $roleOption->id)>{{ $roleOption->display_name }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap text-sm text-gray-500">
                                        {{ $user->pivot->joined_at ? \Carbon\Carbon::parse($user->pivot->joined_at)->format('M d, Y') : '-' }}
                                    </td>
                                    <td class="whitespace-nowrap text-sm text-gray-500">
                                        {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : __('pages.users.never') }}
                                    </td>
                                    <td class="whitespace-nowrap text-end text-sm font-medium">
                                        @if($user->id !== auth()->id() && $this->canManageUsers())
                                            <button
                                                wire:click="confirmRemoveUser({{ $user->id }})"
                                                class="text-red-600 hover:text-red-900 text-sm font-medium"
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
            <div class="card empty-state">
                <x-heroicon-o-users class="h-12 w-12 text-gray-300 mb-3" />
                @if($isSuperAdmin)
                    <h3 class="empty-title">{{ __('pages.admin_users.no_users') }}</h3>
                    <p class="empty-message">{{ __('pages.admin_users.no_users_description') }}</p>
                @else
                    <h3 class="empty-title">{{ __('pages.users.no_users') }}</h3>
                    <p class="empty-message">{{ __('pages.users.no_users_description') }}</p>
                    @if($this->canInviteUsers())
                        <div class="mt-6">
                            <button
                                wire:click="openInviteModal"
                                class="btn-primary"
                            >
                                <x-heroicon-o-user-plus class="w-5 h-5 me-2" />
                                {{ __('pages.users.invite_user') }}
                            </button>
                        </div>
                    @endif
                @endif
            </div>
        @endif
    @endif

    {{-- Invitations Tab --}}
    @if($activeTab === 'invitations')
        @if($invitationsList->count() > 0)
            <div class="card divide-y divide-white/10">
                @foreach($invitationsList as $invitation)
                    <div class="p-4 flex items-center justify-between gap-4" wire:key="invitation-{{ $invitation->id }}">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-white">{{ $invitation->email }}</div>
                            <div class="text-sm text-gray-500">
                                @if($isSuperAdmin && $invitation->tenant)
                                    <span class="badge-neutral rounded-full text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 me-1">
                                        {{ $invitation->tenant->name }}
                                    </span>
                                @endif
                                {{ __('pages.users.invited_as') }}
                                <span class="font-medium {{ $invitation->tenantRole?->isAdmin() ? 'text-white' : 'text-gray-500' }}">
                                    {{ $invitation->tenantRole?->display_name ?? __('roles.' . $invitation->role) }}
                                </span>
                                {{ __('pages.users.by') }} {{ $invitation->inviter?->name ?? __('pages.users.unknown') }}
                            </div>
                            <div class="text-xs text-gray-400 mt-1">
                                {{ __('pages.users.expires') }} {{ $invitation->expires_at->diffForHumans() }}
                            </div>
                        </div>
                        <div class="flex items-center gap-x-2 shrink-0">
                            @if($this->canInviteUsers())
                                <button
                                    wire:click="resendInvitation({{ $invitation->id }})"
                                    @if($resendingId === $invitation->id) disabled @endif
                                    class="btn-secondary !py-1.5 !px-3 disabled:opacity-50"
                                >
                                    {{ $resendingId === $invitation->id ? __('common.sending') : __('pages.users.resend') }}
                                </button>
                                <button
                                    wire:click="cancelInvitation({{ $invitation->id }})"
                                    @if($cancellingId === $invitation->id) disabled @endif
                                    class="btn-secondary !py-1.5 !px-3 !text-red-600 hover:!bg-red-500/10 disabled:opacity-50"
                                >
                                    {{ $cancellingId === $invitation->id ? __('common.cancelling') : __('common.cancel') }}
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="card empty-state">
                <x-heroicon-o-envelope class="h-12 w-12 text-gray-300 mb-3" />
                <h3 class="empty-title">{{ __('pages.users.no_invitations') }}</h3>
                <p class="empty-message">{{ __('pages.users.no_invitations_description') }}</p>
            </div>
        @endif
    @endif

    {{-- ═══ Regular Admin Modals ═══ --}}

    @if(!$isSuperAdmin)
        {{-- Invite User Modal --}}
        @if($showInviteModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-trap.noscroll="true">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <div class="fixed inset-0 bg-black/60 transition-opacity" wire:click="closeInviteModal"></div>
                    <div class="relative bg-gray-900 border border-white/10 rounded-xl max-w-md w-full mx-auto p-6 text-start">
                        <h3 class="text-lg font-semibold tracking-tight text-white">
                            {{ __('pages.users.invite_user') }}
                        </h3>

                        <div class="mt-4 space-y-4">
                            {{-- Email --}}
                            <div>
                                <label for="invite_email" class="micro-label block mb-1">
                                    {{ __('labels.email') }} <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="invite_email"
                                    wire:model="inviteEmail"
                                    type="email"
                                    required
                                    placeholder="{{ __('placeholders.invite_email') }}"
                                    class="input-field @error('inviteEmail') !border-red-300 @enderror"
                                />
                                @error('inviteEmail')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Role --}}
                            <div>
                                <label class="micro-label block mb-2">
                                    {{ __('labels.role') }} <span class="text-red-500">*</span>
                                </label>
                                <div class="space-y-2">
                                    @foreach($roles as $roleOption)
                                        <label
                                            class="flex items-start p-3 border rounded-lg cursor-pointer transition-colors {{ $inviteRoleId == $roleOption->id ? 'border-gray-900 bg-gray-950' : 'border-white/10 hover:bg-gray-950' }}"
                                        >
                                            <input
                                                type="radio"
                                                wire:model="inviteRoleId"
                                                value="{{ $roleOption->id }}"
                                                class="h-4 w-4 text-white focus:ring-brand-500 border-white/15 mt-0.5"
                                            />
                                            <div class="ms-3">
                                                <span class="block text-sm font-medium text-white">{{ $roleOption->display_name }}</span>
                                                @if($roleOption->description)
                                                    <span class="block text-sm text-gray-500">{{ $roleOption->description }}</span>
                                                @endif
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Error Message --}}
                            @if($inviteError)
                                <div class="p-3 rounded-lg bg-red-500/15 border border-red-500/30">
                                    <p class="text-sm text-red-400">{{ $inviteError }}</p>
                                </div>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="mt-6 flex justify-end gap-x-3">
                            <button
                                wire:click="closeInviteModal"
                                @if($inviteSending) disabled @endif
                                class="btn-secondary disabled:opacity-50"
                            >
                                {{ __('common.cancel') }}
                            </button>
                            <button
                                wire:click="sendInvitation"
                                @if($inviteSending || empty($inviteEmail)) disabled @endif
                                class="btn-primary disabled:opacity-50"
                            >
                                @if($inviteSending)
                                    <svg class="animate-spin -ms-1 me-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
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
                    <div class="fixed inset-0 bg-black/60 transition-opacity" wire:click="closeRemoveDialog"></div>
                    <div class="relative bg-gray-900 border border-white/10 rounded-xl max-w-md w-full mx-auto p-6">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="flex-shrink-0 bg-red-500/15 rounded-full p-3">
                                <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-red-600" />
                            </div>
                            <div class="text-start">
                                <h3 class="text-lg font-semibold tracking-tight text-white">{{ __('pages.users.remove_user') }}</h3>
                                <p class="text-sm text-gray-500">
                                    {{ __('pages.users.remove_confirm', ['name' => $userToRemoveName]) }}
                                </p>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button
                                wire:click="closeRemoveDialog"
                                class="btn-secondary"
                            >
                                {{ __('common.cancel') }}
                            </button>
                            <button
                                wire:click="removeUser"
                                @if($removeLoading) disabled @endif
                                class="btn-danger disabled:opacity-50"
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
                    <div class="relative w-full max-w-md bg-gray-900 border border-white/10 rounded-xl p-6">
                        <h3 class="text-lg font-semibold tracking-tight text-white">{{ __('pages.admin_users.create_user') }}</h3>
                        <form wire:submit="createUser" class="mt-4 space-y-4">
                            <div>
                                <label class="micro-label block mb-1">{{ __('labels.name') }} <span class="text-red-500">*</span></label>
                                <input wire:model="newUserName" type="text" required class="input-field" />
                                @error('newUserName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="micro-label block mb-1">{{ __('labels.email') }} <span class="text-red-500">*</span></label>
                                <input wire:model="newUserEmail" type="email" required class="input-field" />
                                @error('newUserEmail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="micro-label block mb-1">{{ __('labels.password') }} <span class="text-red-500">*</span></label>
                                <input wire:model="newUserPassword" type="password" required class="input-field" />
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
                                @php
                                    $createRoleOptions = $newUserTenantId ? $this->getTenantRoleOptions($newUserTenantId) : [];
                                @endphp
                                <x-select2
                                    wire-model="newUserRoleId"
                                    :label="__('labels.role')"
                                    :options="$createRoleOptions"
                                    :allow-clear="false"
                                    :searchable="false"
                                />
                            </div>
                            <div class="flex justify-end gap-x-3 pt-4">
                                <button type="button" wire:click="$set('showCreateModal', false)" class="btn-secondary">{{ __('common.cancel') }}</button>
                                <button type="submit" class="btn-primary">{{ __('common.create') }}</button>
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
                    <div class="relative w-full max-w-md bg-gray-900 border border-white/10 rounded-xl p-6">
                        <h3 class="text-lg font-semibold tracking-tight text-white">{{ __('pages.admin_users.edit_user') }}</h3>
                        <form wire:submit="updateUser" class="mt-4 space-y-4">
                            <div>
                                <label class="micro-label block mb-1">{{ __('labels.name') }} <span class="text-red-500">*</span></label>
                                <input wire:model="editUserName" type="text" required class="input-field" />
                                @error('editUserName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="micro-label block mb-1">{{ __('labels.email') }} <span class="text-red-500">*</span></label>
                                <input wire:model="editUserEmail" type="email" required class="input-field" />
                                @error('editUserEmail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="micro-label block mb-1">{{ __('labels.password') }} <span class="text-gray-400">({{ __('leave blank to keep current') }})</span></label>
                                <input wire:model="editUserPassword" type="password" class="input-field" />
                                @error('editUserPassword') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            {{-- Organizations section --}}
                            <div>
                                <label class="micro-label block mb-2">{{ __('labels.tenants') }}</label>
                                <div class="space-y-2 max-h-48 overflow-y-auto">
                                    @php
                                        $editingUser = \App\Models\User::with('tenants')->find($editUserId);
                                    @endphp

                                    {{-- Existing organization rows --}}
                                    @if($editingUser)
                                        @foreach($editingUser->tenants as $tenant)
                                            @php
                                                $editRoleOptions = $this->getTenantRoleOptions($tenant->id);
                                            @endphp
                                            <div class="flex items-center justify-between rounded-lg border border-white/10 px-3 py-2 gap-2">
                                                <span class="text-sm text-gray-200 truncate flex-1">{{ $tenant->name }}</span>
                                                <x-select2
                                                    wire-model="editUserTenantRoles.{{ $tenant->id }}"
                                                    :options="$editRoleOptions"
                                                    :allow-clear="false"
                                                    :searchable="false"
                                                />
                                                <button
                                                    type="button"
                                                    wire:click="removeTenantFromEditUser({{ $tenant->id }})"
                                                    class="p-1 text-red-600 hover:text-red-900 rounded hover:bg-red-500/10 transition-colors"
                                                    title="{{ __('common.remove') }}"
                                                >
                                                    <x-heroicon-s-x-mark class="h-4 w-4" />
                                                </button>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                            <div class="flex justify-end gap-x-3 pt-4">
                                <button type="button" wire:click="$set('showEditModal', false)" class="btn-secondary">{{ __('common.cancel') }}</button>
                                <button type="submit" class="btn-primary">{{ __('common.save') }}</button>
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
                    <div class="relative w-full max-w-md bg-gray-900 border border-white/10 rounded-xl p-6">
                        <h3 class="text-lg font-semibold tracking-tight text-white">{{ __('pages.admin_users.assign_tenant') }}</h3>
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
                                @php
                                    $assignRoleOptions = $assignTenantId ? $this->getTenantRoleOptions($assignTenantId) : [];
                                @endphp
                                <x-select2
                                    wire-model="assignRoleId"
                                    :label="__('labels.role')"
                                    :options="$assignRoleOptions"
                                    :allow-clear="false"
                                    :searchable="false"
                                />
                            </div>
                            <div class="flex justify-end gap-x-3 pt-4">
                                <button type="button" wire:click="$set('showAssignModal', false)" class="btn-secondary">{{ __('common.cancel') }}</button>
                                <button type="submit" class="btn-primary">{{ __('common.assign') }}</button>
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
                    <div class="relative w-full max-w-md bg-gray-900 border border-white/10 rounded-xl p-6">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-500/15">
                            <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-red-600" />
                        </div>
                        <div class="mt-3 text-center">
                            <h3 class="text-lg font-semibold tracking-tight text-white">{{ __('pages.admin_users.delete_user') }}</h3>
                            <p class="mt-2 text-sm text-gray-500">
                                {{ __('pages.admin_users.delete_user_confirm', ['name' => $deleteUserName]) }}
                            </p>
                        </div>
                        <div class="mt-5 flex justify-end gap-x-3">
                            <button wire:click="$set('showDeleteDialog', false)" class="btn-secondary">{{ __('common.cancel') }}</button>
                            <button wire:click="deleteUser" class="btn-danger">{{ __('common.delete') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
