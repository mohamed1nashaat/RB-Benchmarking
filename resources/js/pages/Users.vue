<template>
  <div class="py-6">
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('pages.users.title') }}</h1>
          <p class="mt-1 text-sm text-gray-500">
            {{ $t('pages.users.description') }}
          </p>
        </div>
        <button
          @click="showInviteModal = true"
          class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
        >
          <UserPlusIcon class="w-5 h-5 mr-2" />
          {{ $t('pages.users.invite_user') }}
        </button>
      </div>
    </div>

    <!-- Tabs: Users / Pending Invitations -->
    <div class="border-b border-gray-200 mb-6">
      <nav class="-mb-px flex space-x-8">
        <button
          @click="activeTab = 'users'"
          :class="[
            'py-2 px-1 border-b-2 font-medium text-sm',
            activeTab === 'users'
              ? 'border-primary-500 text-primary-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
        >
          {{ $t('pages.users.tabs.users') }}
          <span
            v-if="pagination"
            class="ml-2 bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-xs"
          >
            {{ pagination.total }}
          </span>
        </button>
        <button
          @click="activeTab = 'invitations'"
          :class="[
            'py-2 px-1 border-b-2 font-medium text-sm',
            activeTab === 'invitations'
              ? 'border-primary-500 text-primary-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
        >
          {{ $t('pages.users.tabs.invitations') }}
          <span
            v-if="invitations.length > 0"
            class="ml-2 bg-yellow-100 text-yellow-600 py-0.5 px-2 rounded-full text-xs"
          >
            {{ invitations.length }}
          </span>
        </button>
      </nav>
    </div>

    <!-- Users Tab Content -->
    <div v-if="activeTab === 'users'">
      <!-- Filters -->
      <div class="bg-white shadow rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Search -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.search') }}</label>
            <input
              v-model="filters.search"
              type="text"
              :placeholder="$t('pages.users.search_placeholder')"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              @input="debouncedSearch"
            />
          </div>

          <!-- Role Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.role') }}</label>
            <select
              v-model="filters.role"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              @change="fetchUsers"
            >
              <option value="">{{ $t('filters.all_roles') }}</option>
              <option value="admin">{{ $t('roles.admin') }}</option>
              <option value="viewer">{{ $t('roles.viewer') }}</option>
            </select>
          </div>

          <!-- Per Page -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.show') }}</label>
            <select
              v-model="filters.per_page"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              @change="handlePerPageChange"
            >
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Loading Skeleton -->
      <div v-if="loading" class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-100">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                {{ $t('labels.user') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                {{ $t('labels.role') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                {{ $t('labels.joined') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                {{ $t('labels.last_login') }}
              </th>
              <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                {{ $t('labels.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="i in 5" :key="i" class="animate-pulse">
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <div class="h-10 w-10 bg-gray-200 rounded-full"></div>
                  <div class="ml-4">
                    <div class="h-4 bg-gray-200 rounded w-32 mb-2"></div>
                    <div class="h-3 bg-gray-200 rounded w-40"></div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4"><div class="h-6 bg-gray-200 rounded-full w-16"></div></td>
              <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-24"></div></td>
              <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-24"></div></td>
              <td class="px-6 py-4"><div class="h-8 bg-gray-200 rounded w-20 ml-auto"></div></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Users Table -->
      <div v-if="!loading && users.length > 0" class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-100">
            <tr>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-200"
                @click="handleSort('name')"
              >
                <div class="flex items-center gap-1">
                  {{ $t('labels.user') }}
                  <ChevronUpIcon v-if="filters.sort_by === 'name' && filters.sort_order === 'asc'" class="h-4 w-4 text-primary-600" />
                  <ChevronDownIcon v-else-if="filters.sort_by === 'name' && filters.sort_order === 'desc'" class="h-4 w-4 text-primary-600" />
                  <ChevronUpDownIcon v-else class="h-4 w-4 text-gray-400" />
                </div>
              </th>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-200"
                @click="handleSort('role')"
              >
                <div class="flex items-center gap-1">
                  {{ $t('labels.role') }}
                  <ChevronUpIcon v-if="filters.sort_by === 'role' && filters.sort_order === 'asc'" class="h-4 w-4 text-primary-600" />
                  <ChevronDownIcon v-else-if="filters.sort_by === 'role' && filters.sort_order === 'desc'" class="h-4 w-4 text-primary-600" />
                  <ChevronUpDownIcon v-else class="h-4 w-4 text-gray-400" />
                </div>
              </th>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-200"
                @click="handleSort('joined_at')"
              >
                <div class="flex items-center gap-1">
                  {{ $t('labels.joined') }}
                  <ChevronUpIcon v-if="filters.sort_by === 'joined_at' && filters.sort_order === 'asc'" class="h-4 w-4 text-primary-600" />
                  <ChevronDownIcon v-else-if="filters.sort_by === 'joined_at' && filters.sort_order === 'desc'" class="h-4 w-4 text-primary-600" />
                  <ChevronUpDownIcon v-else class="h-4 w-4 text-gray-400" />
                </div>
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                {{ $t('labels.last_login') }}
              </th>
              <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                {{ $t('labels.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <img
                      v-if="user.avatar_url"
                      :src="user.avatar_url"
                      :alt="user.name"
                      class="h-10 w-10 rounded-full object-cover"
                    />
                    <div
                      v-else
                      class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center"
                    >
                      <span class="text-primary-700 font-medium text-sm">
                        {{ getInitials(user.name) }}
                      </span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">
                      {{ user.name }}
                      <span
                        v-if="user.is_current_user"
                        class="ml-2 text-xs text-gray-500"
                      >({{ $t('pages.users.you') }})</span>
                    </div>
                    <div class="text-sm text-gray-500">{{ user.email }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <UserRoleDropdown
                  :user="user"
                  :disabled="user.is_current_user"
                  @change="handleRoleChange(user, $event)"
                />
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ user.joined_at ? formatDate(user.joined_at) : '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ user.last_login_at ? formatRelativeDate(user.last_login_at) : $t('pages.users.never') }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button
                  v-if="!user.is_current_user"
                  @click="confirmRemoveUser(user)"
                  class="text-red-600 hover:text-red-900"
                >
                  {{ $t('pages.users.remove') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-if="!loading && users.length === 0" class="text-center py-12 bg-white shadow rounded-lg">
        <UsersIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('pages.users.no_users') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ $t('pages.users.no_users_description') }}</p>
        <div class="mt-6">
          <button
            @click="showInviteModal = true"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700"
          >
            <UserPlusIcon class="w-5 h-5 mr-2" />
            {{ $t('pages.users.invite_user') }}
          </button>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination && pagination.last_page > 1" class="mt-6 flex items-center justify-between">
        <div class="text-sm text-gray-700">
          {{ $t('pages.users.showing', { from: pagination.from, to: pagination.to, total: pagination.total }) }}
        </div>
        <div class="flex space-x-2">
          <button
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="px-3 py-2 text-sm border border-gray-300 rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
          >
            {{ $t('buttons.previous') }}
          </button>
          <button
            v-for="page in visiblePages"
            :key="page"
            @click="changePage(page)"
            :class="[
              'px-3 py-2 text-sm border rounded-md',
              page === pagination.current_page
                ? 'bg-primary-600 text-white border-primary-600'
                : 'border-gray-300 hover:bg-gray-50'
            ]"
          >
            {{ page }}
          </button>
          <button
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="px-3 py-2 text-sm border border-gray-300 rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
          >
            {{ $t('buttons.next') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Invitations Tab Content -->
    <div v-if="activeTab === 'invitations'">
      <!-- Invitations Loading -->
      <div v-if="invitationsLoading" class="bg-white shadow rounded-lg p-6">
        <div class="animate-pulse space-y-4">
          <div v-for="i in 3" :key="i" class="flex items-center justify-between">
            <div class="flex-1">
              <div class="h-4 bg-gray-200 rounded w-1/3 mb-2"></div>
              <div class="h-3 bg-gray-200 rounded w-1/4"></div>
            </div>
            <div class="h-8 bg-gray-200 rounded w-24"></div>
          </div>
        </div>
      </div>

      <!-- Invitations List -->
      <div v-else-if="invitations.length > 0" class="bg-white shadow rounded-lg divide-y divide-gray-200">
        <div
          v-for="invitation in invitations"
          :key="invitation.id"
          class="p-4 flex items-center justify-between"
        >
          <div>
            <div class="text-sm font-medium text-gray-900">{{ invitation.email }}</div>
            <div class="text-sm text-gray-500">
              {{ $t('pages.users.invited_as') }}
              <span class="font-medium" :class="invitation.role === 'admin' ? 'text-primary-600' : 'text-gray-600'">
                {{ $t(`roles.${invitation.role}`) }}
              </span>
              {{ $t('pages.users.by') }} {{ invitation.invited_by }}
            </div>
            <div class="text-xs text-gray-400 mt-1">
              {{ $t('pages.users.expires') }} {{ formatRelativeDate(invitation.expires_at) }}
            </div>
          </div>
          <div class="flex items-center space-x-2">
            <button
              @click="resendInvitation(invitation)"
              :disabled="resendingId === invitation.id"
              class="px-3 py-1.5 text-sm border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50"
            >
              {{ resendingId === invitation.id ? $t('common.sending') : $t('pages.users.resend') }}
            </button>
            <button
              @click="cancelInvitation(invitation)"
              :disabled="cancellingId === invitation.id"
              class="px-3 py-1.5 text-sm text-red-600 border border-red-300 rounded-md hover:bg-red-50 disabled:opacity-50"
            >
              {{ cancellingId === invitation.id ? $t('common.cancelling') : $t('common.cancel') }}
            </button>
          </div>
        </div>
      </div>

      <!-- No Invitations -->
      <div v-else class="text-center py-12 bg-white shadow rounded-lg">
        <EnvelopeIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('pages.users.no_invitations') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ $t('pages.users.no_invitations_description') }}</p>
      </div>
    </div>

    <!-- Invite User Modal -->
    <InviteUserModal
      :open="showInviteModal"
      @close="showInviteModal = false"
      @invited="handleInvited"
    />

    <!-- Remove User Confirmation -->
    <ConfirmDialog
      :open="showRemoveDialog"
      :title="$t('pages.users.remove_user')"
      :message="removeMessage"
      :confirm-text="$t('pages.users.remove')"
      :loading="removeLoading"
      variant="danger"
      @close="showRemoveDialog = false"
      @confirm="handleRemoveUser"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import {
  UserPlusIcon,
  UsersIcon,
  EnvelopeIcon,
  ChevronUpIcon,
  ChevronDownIcon,
  ChevronUpDownIcon
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import InviteUserModal from '@/components/InviteUserModal.vue'
import UserRoleDropdown from '@/components/UserRoleDropdown.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import type {
  TenantUser,
  UserInvitation,
  UserFilters,
  PaginatedUsers
} from '@/types/user'

const authStore = useAuthStore()

const activeTab = ref<'users' | 'invitations'>('users')
const users = ref<TenantUser[]>([])
const invitations = ref<UserInvitation[]>([])
const loading = ref(false)
const invitationsLoading = ref(false)
const pagination = ref<PaginatedUsers<TenantUser> | null>(null)

const showInviteModal = ref(false)
const showRemoveDialog = ref(false)
const userToRemove = ref<TenantUser | null>(null)
const removeLoading = ref(false)

const resendingId = ref<number | null>(null)
const cancellingId = ref<number | null>(null)

const filters = ref<UserFilters>({
  search: '',
  role: '',
  sort_by: 'name',
  sort_order: 'asc',
  page: 1,
  per_page: 10,
})

let debounceTimer: NodeJS.Timeout | null = null

const currentTenantId = computed(() => authStore.currentTenant?.id)

const removeMessage = computed(() => {
  if (!userToRemove.value) return ''
  return `Are you sure you want to remove ${userToRemove.value.name} from this tenant? They will lose access to all resources in this tenant.`
})

const visiblePages = computed(() => {
  if (!pagination.value) return []
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const pages: number[] = []

  let start = Math.max(1, current - 2)
  let end = Math.min(last, start + 4)

  if (end - start < 4) {
    start = Math.max(1, end - 4)
  }

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }

  return pages
})

const debouncedSearch = () => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    filters.value.page = 1
    fetchUsers()
  }, 500)
}

const handleSort = (field: string) => {
  if (filters.value.sort_by === field) {
    filters.value.sort_order = filters.value.sort_order === 'asc' ? 'desc' : 'asc'
  } else {
    filters.value.sort_by = field
    filters.value.sort_order = 'asc'
  }
  filters.value.page = 1
  fetchUsers()
}

const handlePerPageChange = () => {
  filters.value.page = 1
  fetchUsers()
}

const changePage = (page: number) => {
  filters.value.page = page
  fetchUsers()
}

const fetchUsers = async () => {
  if (!currentTenantId.value) return

  loading.value = true
  try {
    const params = new URLSearchParams()
    Object.entries(filters.value).forEach(([key, value]) => {
      if (value) params.append(key, value.toString())
    })

    const response = await window.axios.get(
      `/api/tenants/${currentTenantId.value}/users?${params.toString()}`
    )

    users.value = response.data.data
    pagination.value = {
      current_page: response.data.current_page,
      last_page: response.data.last_page,
      per_page: response.data.per_page,
      total: response.data.total,
      from: response.data.from,
      to: response.data.to,
      data: response.data.data,
    }
  } catch (error) {
    console.error('Error fetching users:', error)
  } finally {
    loading.value = false
  }
}

const fetchInvitations = async () => {
  if (!currentTenantId.value) return

  invitationsLoading.value = true
  try {
    const response = await window.axios.get(
      `/api/tenants/${currentTenantId.value}/users/invitations`
    )
    invitations.value = response.data.data
  } catch (error) {
    console.error('Error fetching invitations:', error)
  } finally {
    invitationsLoading.value = false
  }
}

const handleRoleChange = async (user: TenantUser, newRole: string, roleId?: number) => {
  if (!currentTenantId.value) return

  try {
    const payload: { role: string; tenant_role_id?: number } = { role: newRole }
    if (roleId) {
      payload.tenant_role_id = roleId
    }

    await window.axios.put(
      `/api/tenants/${currentTenantId.value}/users/${user.id}/role`,
      payload
    )
    user.role = newRole as 'admin' | 'viewer'
    if (roleId) {
      user.tenant_role_id = roleId
    }
  } catch (error: any) {
    console.error('Error changing role:', error)
    alert(error.response?.data?.message || 'Failed to change role')
    fetchUsers() // Refresh to restore original state
  }
}

const confirmRemoveUser = (user: TenantUser) => {
  userToRemove.value = user
  showRemoveDialog.value = true
}

const handleRemoveUser = async () => {
  if (!currentTenantId.value || !userToRemove.value) return

  removeLoading.value = true
  try {
    await window.axios.delete(
      `/api/tenants/${currentTenantId.value}/users/${userToRemove.value.id}`
    )
    showRemoveDialog.value = false
    userToRemove.value = null
    fetchUsers()
  } catch (error: any) {
    console.error('Error removing user:', error)
    alert(error.response?.data?.message || 'Failed to remove user')
  } finally {
    removeLoading.value = false
  }
}

const handleInvited = () => {
  showInviteModal.value = false
  fetchInvitations()
}

const resendInvitation = async (invitation: UserInvitation) => {
  if (!currentTenantId.value) return

  resendingId.value = invitation.id
  try {
    await window.axios.post(
      `/api/tenants/${currentTenantId.value}/users/invitations/${invitation.id}/resend`
    )
    fetchInvitations()
  } catch (error: any) {
    console.error('Error resending invitation:', error)
    alert(error.response?.data?.message || 'Failed to resend invitation')
  } finally {
    resendingId.value = null
  }
}

const cancelInvitation = async (invitation: UserInvitation) => {
  if (!currentTenantId.value) return

  cancellingId.value = invitation.id
  try {
    await window.axios.delete(
      `/api/tenants/${currentTenantId.value}/users/invitations/${invitation.id}`
    )
    fetchInvitations()
  } catch (error: any) {
    console.error('Error cancelling invitation:', error)
    alert(error.response?.data?.message || 'Failed to cancel invitation')
  } finally {
    cancellingId.value = null
  }
}

const getInitials = (name: string): string => {
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const formatRelativeDate = (dateString: string): string => {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffSeconds = Math.floor(diffMs / 1000)
  const diffMinutes = Math.floor(diffSeconds / 60)
  const diffHours = Math.floor(diffMinutes / 60)
  const diffDays = Math.floor(diffHours / 24)

  // Past dates
  if (diffMs >= 0) {
    if (diffMinutes < 1) return 'just now'
    if (diffMinutes < 60) return `${diffMinutes} minutes ago`
    if (diffHours < 24) return `${diffHours} hours ago`
    if (diffDays === 1) return 'yesterday'
    if (diffDays < 7) return `${diffDays} days ago`
    if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`
    return formatDate(dateString)
  } else {
    // Future dates
    const absDays = Math.abs(diffDays)
    if (absDays === 0) return 'today'
    if (absDays === 1) return 'tomorrow'
    if (absDays < 7) return `in ${absDays} days`
    return formatDate(dateString)
  }
}

// Watch for tenant changes
watch(currentTenantId, (newVal) => {
  if (newVal) {
    fetchUsers()
    fetchInvitations()
  }
})

// Watch for tab changes
watch(activeTab, (newVal) => {
  if (newVal === 'invitations' && invitations.value.length === 0) {
    fetchInvitations()
  }
})

onMounted(() => {
  if (currentTenantId.value) {
    fetchUsers()
    fetchInvitations()
  }
})
</script>
