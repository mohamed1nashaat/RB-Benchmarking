<template>
  <div class="py-6">
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('pages.admin_users.title') }}</h1>
          <p class="mt-1 text-sm text-gray-500">
            {{ $t('pages.admin_users.description') }}
          </p>
        </div>
        <button
          v-if="activeTab === 'users'"
          @click="showCreateModal = true"
          class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
        >
          <UserPlusIcon class="w-5 h-5 mr-2" />
          {{ $t('pages.admin_users.create_user') }}
        </button>
      </div>
    </div>

    <!-- Tabs: Users / Roles -->
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
          <UsersIcon class="w-5 h-5 inline-block mr-1 -mt-0.5" />
          {{ $t('navigation.users') }}
          <span
            v-if="pagination"
            class="ml-2 bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-xs"
          >
            {{ pagination.total }}
          </span>
        </button>
        <button
          @click="activeTab = 'roles'"
          :class="[
            'py-2 px-1 border-b-2 font-medium text-sm',
            activeTab === 'roles'
              ? 'border-primary-500 text-primary-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
        >
          <ShieldCheckIcon class="w-5 h-5 inline-block mr-1 -mt-0.5" />
          {{ $t('navigation.roles') }}
        </button>
      </nav>
    </div>

    <!-- Users Tab Content -->
    <div v-if="activeTab === 'users'">
      <!-- Filters -->
      <div class="bg-white shadow rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Search -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.search') }}</label>
            <input
              v-model="filters.search"
              type="text"
              :placeholder="$t('pages.admin_users.search_placeholder')"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              @input="debouncedSearch"
            />
          </div>

          <!-- Tenant Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('labels.tenant') }}</label>
            <select
              v-model="filters.tenant_id"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              @change="fetchUsers"
            >
              <option :value="null">{{ $t('filters.all_tenants') }}</option>
              <option v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">
                {{ tenant.name }}
              </option>
            </select>
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
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ $t('labels.user') }}</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ $t('labels.tenants') }}</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ $t('labels.last_login') }}</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ $t('labels.created') }}</th>
              <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ $t('labels.actions') }}</th>
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
              <td class="px-6 py-4"><div class="h-6 bg-gray-200 rounded w-24"></div></td>
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
                class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:bg-gray-200"
                @click="handleSort('name')"
              >
                <div class="flex items-center gap-1">
                  {{ $t('labels.user') }}
                  <ChevronUpIcon v-if="filters.sort_by === 'name' && filters.sort_order === 'asc'" class="h-4 w-4 text-primary-600" />
                  <ChevronDownIcon v-else-if="filters.sort_by === 'name' && filters.sort_order === 'desc'" class="h-4 w-4 text-primary-600" />
                  <ChevronUpDownIcon v-else class="h-4 w-4 text-gray-400" />
                </div>
              </th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                {{ $t('labels.tenants') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                {{ $t('labels.last_login') }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:bg-gray-200"
                @click="handleSort('created_at')"
              >
                <div class="flex items-center gap-1">
                  {{ $t('labels.created') }}
                  <ChevronUpIcon v-if="filters.sort_by === 'created_at' && filters.sort_order === 'asc'" class="h-4 w-4 text-primary-600" />
                  <ChevronDownIcon v-else-if="filters.sort_by === 'created_at' && filters.sort_order === 'desc'" class="h-4 w-4 text-primary-600" />
                  <ChevronUpDownIcon v-else class="h-4 w-4 text-gray-400" />
                </div>
              </th>
              <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">
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
                        v-if="user.is_super_admin"
                        class="ml-2 px-1.5 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 rounded"
                      >
                        Super Admin
                      </span>
                    </div>
                    <div class="text-sm text-gray-500">{{ user.email }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="tenant in user.tenants.slice(0, 3)"
                    :key="tenant.id"
                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                    :class="tenant.role === 'admin' ? 'bg-primary-100 text-primary-800' : 'bg-gray-100 text-gray-800'"
                    :title="`${tenant.name} (${tenant.role})`"
                  >
                    {{ tenant.name }}
                  </span>
                  <span
                    v-if="user.tenants.length > 3"
                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600"
                  >
                    +{{ user.tenants.length - 3 }}
                  </span>
                  <span
                    v-if="user.tenants.length === 0"
                    class="text-sm text-gray-400"
                  >
                    {{ $t('pages.admin_users.no_tenants') }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ user.last_login_at ? formatRelativeDate(user.last_login_at) : $t('pages.users.never') }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(user.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end space-x-2">
                  <button
                    @click="showAssignTenant(user)"
                    class="text-primary-600 hover:text-primary-900"
                    :title="$t('pages.admin_users.assign_tenant')"
                  >
                    <BuildingOfficeIcon class="h-5 w-5" />
                  </button>
                  <button
                    @click="editUser(user)"
                    class="text-gray-600 hover:text-gray-900"
                    :title="$t('common.edit')"
                  >
                    <PencilIcon class="h-5 w-5" />
                  </button>
                  <button
                    v-if="!user.is_super_admin"
                    @click="confirmDeleteUser(user)"
                    class="text-red-600 hover:text-red-900"
                    :title="$t('common.delete')"
                  >
                    <TrashIcon class="h-5 w-5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-if="!loading && users.length === 0" class="text-center py-12 bg-white shadow rounded-lg">
        <UsersIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('pages.admin_users.no_users') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ $t('pages.admin_users.no_users_description') }}</p>
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

    <!-- Roles Tab Content -->
    <div v-if="activeTab === 'roles'">
      <!-- Roles Loading -->
      <div v-if="globalRolesLoading" class="bg-white shadow rounded-lg p-6">
        <div class="animate-pulse space-y-4">
          <div v-for="i in 2" :key="i" class="flex items-center justify-between border-b border-gray-100 pb-4">
            <div class="flex-1">
              <div class="h-5 bg-gray-200 rounded w-1/4 mb-2"></div>
              <div class="h-4 bg-gray-200 rounded w-1/3"></div>
            </div>
            <div class="flex space-x-2">
              <div class="h-8 bg-gray-200 rounded w-16"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Global Roles List -->
      <div v-else-if="globalRoles.length > 0" class="bg-white shadow rounded-lg divide-y divide-gray-200">
        <div
          v-for="role in globalRoles"
          :key="role.name"
          class="hover:bg-gray-50"
        >
          <div class="p-6">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <!-- Expand/Collapse button -->
                  <button
                    v-if="role.users_count > 0"
                    @click="toggleGlobalRoleExpand(role.name)"
                    class="p-1 rounded hover:bg-gray-200 transition-colors"
                  >
                    <ChevronDownIcon
                      class="h-5 w-5 text-gray-500 transition-transform"
                      :class="{ 'rotate-180': expandedGlobalRoles.has(role.name) }"
                    />
                  </button>
                  <div v-else class="w-7"></div>
                  <h3 class="text-lg font-medium text-gray-900">{{ role.display_name }}</h3>
                  <span
                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800"
                  >
                    {{ $t('pages.roles.system_role') }}
                  </span>
                </div>
                <p class="mt-1 text-sm text-gray-500 ml-8">{{ role.description || $t('pages.roles.no_description') }}</p>
                <div class="mt-3 flex items-center gap-4 text-sm text-gray-500 ml-8">
                  <span class="flex items-center gap-1">
                    <UsersIcon class="h-4 w-4" />
                    {{ role.users_count }} {{ $t('pages.roles.users') }}
                  </span>
                  <span class="flex items-center gap-1">
                    <KeyIcon class="h-4 w-4" />
                    {{ role.permissions.length }} {{ $t('pages.roles.permissions') }}
                  </span>
                </div>
                <!-- Permission badges -->
                <div class="mt-3 flex flex-wrap gap-1 ml-8">
                  <span
                    v-for="permission in role.permissions.slice(0, 5)"
                    :key="permission.id"
                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700"
                  >
                    {{ permission.display_name }}
                  </span>
                  <span
                    v-if="role.permissions.length > 5"
                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500"
                  >
                    +{{ role.permissions.length - 5 }} {{ $t('pages.roles.more') }}
                  </span>
                </div>
              </div>
              <div class="flex items-center space-x-2 ml-4">
                <button
                  @click="openEditGlobalRoleModal(role)"
                  class="px-3 py-1.5 text-sm border border-gray-300 rounded-md hover:bg-gray-50"
                >
                  {{ $t('buttons.edit') }}
                </button>
              </div>
            </div>
          </div>

          <!-- Expanded Users List with Tenants -->
          <div
            v-if="expandedGlobalRoles.has(role.name)"
            class="border-t border-gray-100 bg-gray-50 px-6 py-4"
          >
            <h4 class="text-sm font-medium text-gray-700 mb-3">{{ $t('pages.roles.users_with_role') }}</h4>
            <div v-if="globalRoleUsersLoading[role.name]" class="flex items-center justify-center py-4">
              <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary-600"></div>
            </div>
            <div v-else-if="globalRoleUsers[role.name]?.length > 0" class="space-y-3">
              <div
                v-for="user in globalRoleUsers[role.name]"
                :key="user.id"
                class="flex items-center justify-between bg-white rounded-lg p-3 shadow-sm"
              >
                <div class="flex items-center flex-1">
                  <div class="flex-shrink-0 h-8 w-8">
                    <img
                      v-if="user.avatar_url"
                      :src="user.avatar_url"
                      :alt="user.name"
                      class="h-8 w-8 rounded-full object-cover"
                    />
                    <div
                      v-else
                      class="h-8 w-8 rounded-full bg-primary-100 flex items-center justify-center"
                    >
                      <span class="text-primary-700 font-medium text-xs">
                        {{ getInitials(user.name) }}
                      </span>
                    </div>
                  </div>
                  <div class="ml-3 flex-1">
                    <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                    <div class="text-xs text-gray-500">{{ user.email }}</div>
                  </div>
                  <div class="flex flex-wrap gap-1 ml-4">
                    <span
                      v-for="tenant in user.tenants"
                      :key="tenant.id"
                      class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800"
                    >
                      {{ tenant.name }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-4 text-sm text-gray-500">
              {{ $t('pages.roles.no_users_in_role') }}
            </div>
          </div>
        </div>
      </div>

      <!-- No Roles Empty State -->
      <div v-else class="text-center py-12 bg-white shadow rounded-lg">
        <ShieldCheckIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('pages.roles.no_roles') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ $t('pages.roles.no_roles_description') }}</p>
      </div>
    </div>

    <!-- Remove User from Tenant Confirmation -->
    <ConfirmDialog
      :open="showRemoveUserDialog"
      :title="$t('pages.roles.remove_from_tenant')"
      :message="removeUserMessage"
      :confirm-text="$t('buttons.remove')"
      :loading="removeUserLoading"
      variant="danger"
      @close="showRemoveUserDialog = false"
      @confirm="handleRemoveUserFromTenant"
    />

    <!-- Create User Modal -->
    <CreateUserModal
      :open="showCreateModal"
      :tenants="tenants"
      @close="showCreateModal = false"
      @created="handleUserCreated"
    />

    <!-- Edit User Modal -->
    <EditUserModal
      :open="showEditModal"
      :user="userToEdit"
      @close="showEditModal = false"
      @updated="handleUserUpdated"
    />

    <!-- Assign Tenant Modal -->
    <AssignTenantModal
      :open="showAssignModal"
      :user="userForTenant"
      :tenants="tenants"
      @close="showAssignModal = false"
      @assigned="handleTenantAssigned"
    />

    <!-- Delete User Confirmation -->
    <ConfirmDialog
      :open="showDeleteDialog"
      :title="$t('pages.admin_users.delete_user')"
      :message="deleteMessage"
      :confirm-text="$t('common.delete')"
      :loading="deleteLoading"
      variant="danger"
      @close="showDeleteDialog = false"
      @confirm="handleDeleteUser"
    />

    <!-- Role Form Modal -->
    <RoleFormModal
      :open="showRoleFormModal"
      :role="selectedRole"
      :permissions="groupedPermissions"
      :tenants="tenants"
      :preselected-tenant-id="selectedTenantForRoles"
      :is-global-role="isEditingGlobalRole"
      :global-role-name="selectedGlobalRoleName"
      @close="closeRoleFormModal"
      @saved="handleRoleSaved"
    />

    <!-- Delete Role Confirmation -->
    <ConfirmDialog
      :open="showDeleteRoleDialog"
      :title="$t('pages.roles.delete_role')"
      :message="deleteRoleMessage"
      :confirm-text="$t('buttons.delete')"
      :loading="deleteRoleLoading"
      variant="danger"
      @close="showDeleteRoleDialog = false"
      @confirm="handleDeleteRole"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import {
  UserPlusIcon,
  UsersIcon,
  BuildingOfficeIcon,
  PencilIcon,
  TrashIcon,
  ChevronUpIcon,
  ChevronDownIcon,
  ChevronUpDownIcon,
  ShieldCheckIcon,
  KeyIcon
} from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'
import CreateUserModal from '@/components/CreateUserModal.vue'
import EditUserModal from '@/components/EditUserModal.vue'
import AssignTenantModal from '@/components/AssignTenantModal.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import RoleFormModal from '@/components/RoleFormModal.vue'
import type {
  AdminUser,
  AdminUserFilters,
  PaginatedUsers,
  TenantOption,
  TenantRole,
  GroupedPermissions
} from '@/types/user'

const { t } = useI18n()

const activeTab = ref<'users' | 'roles'>('users')

// Users state
const users = ref<AdminUser[]>([])
const tenants = ref<TenantOption[]>([])
const loading = ref(false)
const pagination = ref<PaginatedUsers<AdminUser> | null>(null)

const showCreateModal = ref(false)
const showEditModal = ref(false)
const showAssignModal = ref(false)
const showDeleteDialog = ref(false)

const userToEdit = ref<AdminUser | null>(null)
const userForTenant = ref<AdminUser | null>(null)
const userToDelete = ref<AdminUser | null>(null)
const deleteLoading = ref(false)

const filters = ref<AdminUserFilters>({
  search: '',
  role: '',
  tenant_id: null,
  sort_by: 'name',
  sort_order: 'asc',
  page: 1,
  per_page: 10,
})

// Roles state (legacy - kept for compatibility)
const selectedTenantForRoles = ref<number | null>(null)
const roles = ref<TenantRole[]>([])
const groupedPermissions = ref<GroupedPermissions>({})
const rolesLoading = ref(false)
const showRoleFormModal = ref(false)
const selectedRole = ref<TenantRole | null>(null)
const showDeleteRoleDialog = ref(false)
const roleToDelete = ref<TenantRole | null>(null)
const deleteRoleLoading = ref(false)

// Global roles state
interface GlobalRole {
  name: string
  display_name: string
  description: string | null
  is_system: boolean
  permissions: Array<{ id: number; name: string; display_name: string }>
  users_count: number
  tenants_count: number
  tenants: Array<{ id: number; name: string }>
}
interface GlobalRoleUser {
  id: number
  name: string
  email: string
  avatar_url: string | null
  tenants: Array<{ id: number; name: string }>
}
const globalRoles = ref<GlobalRole[]>([])
const globalRolesLoading = ref(false)
const expandedGlobalRoles = ref<Set<string>>(new Set())
const globalRoleUsers = ref<Record<string, GlobalRoleUser[]>>({})
const globalRoleUsersLoading = ref<Record<string, boolean>>({})
const isEditingGlobalRole = ref(false)
const selectedGlobalRoleName = ref<string | null>(null)

// Role view state (roles or users view)
const roleView = ref<'roles' | 'users'>('roles')
const expandedRoles = ref<Set<number>>(new Set())
const roleUsers = ref<Record<number, Array<{ id: number; name: string; email: string; avatar_url: string | null }>>>({})
const roleUsersLoading = ref<Record<number, boolean>>({})

// Tenant users state (for users view)
interface TenantUser {
  id: number
  name: string
  email: string
  avatar_url: string | null
  role: string
  tenant_role_id: number | null
  is_current_user: boolean
}
const tenantUsers = ref<TenantUser[]>([])
const tenantUsersLoading = ref(false)
const updatingUserRole = ref<number | null>(null)

// Remove user from tenant state
const showRemoveUserDialog = ref(false)
const userToRemove = ref<TenantUser | null>(null)
const removeUserLoading = ref(false)

let debounceTimer: NodeJS.Timeout | null = null

const deleteMessage = computed(() => {
  if (!userToDelete.value) return ''
  return `Are you sure you want to delete ${userToDelete.value.name}? This will permanently remove the user and all their data. This action cannot be undone.`
})

const deleteRoleMessage = computed(() => {
  if (!roleToDelete.value) return ''
  const usersCount = roleToDelete.value.users_count
  if (usersCount > 0) {
    return t('pages.roles.delete_message_with_users', { name: roleToDelete.value.display_name, count: usersCount })
  }
  return t('pages.roles.delete_message', { name: roleToDelete.value.display_name })
})

const removeUserMessage = computed(() => {
  if (!userToRemove.value) return ''
  return t('pages.roles.remove_user_confirm', { name: userToRemove.value.name })
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
  loading.value = true
  try {
    const params = new URLSearchParams()
    Object.entries(filters.value).forEach(([key, value]) => {
      if (value !== null && value !== '') params.append(key, value.toString())
    })

    const response = await window.axios.get(`/api/admin/users?${params.toString()}`)

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

const fetchTenants = async () => {
  try {
    const response = await window.axios.get('/api/admin/users/tenants')
    tenants.value = response.data.data
  } catch (error) {
    console.error('Error fetching tenants:', error)
  }
}

const editUser = (user: AdminUser) => {
  userToEdit.value = user
  showEditModal.value = true
}

const showAssignTenant = (user: AdminUser) => {
  userForTenant.value = user
  showAssignModal.value = true
}

const confirmDeleteUser = (user: AdminUser) => {
  userToDelete.value = user
  showDeleteDialog.value = true
}

const handleUserCreated = () => {
  showCreateModal.value = false
  fetchUsers()
}

const handleUserUpdated = () => {
  showEditModal.value = false
  userToEdit.value = null
  fetchUsers()
}

const handleTenantAssigned = () => {
  showAssignModal.value = false
  userForTenant.value = null
  fetchUsers()
}

const handleDeleteUser = async () => {
  if (!userToDelete.value) return

  deleteLoading.value = true
  try {
    await window.axios.delete(`/api/admin/users/${userToDelete.value.id}`)
    showDeleteDialog.value = false
    userToDelete.value = null
    fetchUsers()
  } catch (error: any) {
    console.error('Error deleting user:', error)
    alert(error.response?.data?.message || 'Failed to delete user')
  } finally {
    deleteLoading.value = false
  }
}

// Roles functions
const fetchRolesForTenant = async () => {
  rolesLoading.value = true
  try {
    const params: Record<string, any> = {}
    if (selectedTenantForRoles.value) {
      params.tenant_id = selectedTenantForRoles.value
    }
    const response = await window.axios.get('/api/admin/roles', { params })
    roles.value = response.data.data
  } catch (error) {
    console.error('Error fetching roles:', error)
    roles.value = []
  } finally {
    rolesLoading.value = false
  }
}

const fetchPermissions = async () => {
  try {
    console.log('Fetching permissions...')
    const response = await window.axios.get('/api/admin/permissions')
    console.log('Permissions response:', response.data)
    groupedPermissions.value = response.data.data
    console.log('groupedPermissions set to:', groupedPermissions.value)
  } catch (error) {
    console.error('Error fetching permissions:', error)
  }
}

const openCreateRoleModal = () => {
  selectedRole.value = null
  showRoleFormModal.value = true
}

const openEditRoleModal = (role: TenantRole) => {
  selectedRole.value = role
  showRoleFormModal.value = true
}

const closeRoleFormModal = () => {
  showRoleFormModal.value = false
  selectedRole.value = null
  isEditingGlobalRole.value = false
  selectedGlobalRoleName.value = null
}

const handleRoleSaved = () => {
  closeRoleFormModal()
  if (isEditingGlobalRole.value) {
    fetchGlobalRoles()
  } else {
    fetchRolesForTenant()
  }
  isEditingGlobalRole.value = false
  selectedGlobalRoleName.value = null
}

// Global roles functions
const fetchGlobalRoles = async () => {
  globalRolesLoading.value = true
  try {
    const response = await window.axios.get('/api/admin/global-roles')
    globalRoles.value = response.data.data
  } catch (error) {
    console.error('Error fetching global roles:', error)
    globalRoles.value = []
  } finally {
    globalRolesLoading.value = false
  }
}

const toggleGlobalRoleExpand = async (roleName: string) => {
  if (expandedGlobalRoles.value.has(roleName)) {
    expandedGlobalRoles.value.delete(roleName)
    expandedGlobalRoles.value = new Set(expandedGlobalRoles.value)
  } else {
    expandedGlobalRoles.value.add(roleName)
    expandedGlobalRoles.value = new Set(expandedGlobalRoles.value)

    // Fetch users for this role if not already fetched
    if (!globalRoleUsers.value[roleName]) {
      await fetchUsersForGlobalRole(roleName)
    }
  }
}

const fetchUsersForGlobalRole = async (roleName: string) => {
  globalRoleUsersLoading.value = { ...globalRoleUsersLoading.value, [roleName]: true }
  try {
    const response = await window.axios.get(`/api/admin/global-roles/${roleName}/users`)
    globalRoleUsers.value = { ...globalRoleUsers.value, [roleName]: response.data.data || [] }
  } catch (error) {
    console.error('Error fetching global role users:', error)
    globalRoleUsers.value = { ...globalRoleUsers.value, [roleName]: [] }
  } finally {
    globalRoleUsersLoading.value = { ...globalRoleUsersLoading.value, [roleName]: false }
  }
}

const openEditGlobalRoleModal = (role: GlobalRole) => {
  // Convert global role to TenantRole format for modal compatibility
  selectedRole.value = {
    id: 0, // Placeholder - not used for global role updates
    tenant_id: 0,
    name: role.name,
    display_name: role.display_name,
    description: role.description,
    is_system: true,
    permissions: role.permissions,
    users_count: role.users_count,
  } as TenantRole
  isEditingGlobalRole.value = true
  selectedGlobalRoleName.value = role.name
  showRoleFormModal.value = true
}

const confirmDeleteRole = (role: TenantRole) => {
  roleToDelete.value = role
  showDeleteRoleDialog.value = true
}

const handleDeleteRole = async () => {
  if (!roleToDelete.value) return

  deleteRoleLoading.value = true
  try {
    await window.axios.delete(`/api/admin/roles/${roleToDelete.value.id}`)
    showDeleteRoleDialog.value = false
    roleToDelete.value = null
    fetchRolesForTenant()
  } catch (error: any) {
    console.error('Error deleting role:', error)
    alert(error.response?.data?.message || 'Failed to delete role')
  } finally {
    deleteRoleLoading.value = false
  }
}

// Handle tenant change - fetch both roles and users
const handleTenantChange = () => {
  expandedRoles.value.clear()
  roleUsers.value = {}
  fetchRolesForTenant()
  fetchTenantUsers()
  fetchPermissions()
}

// Fetch users for the selected tenant (for users view)
const fetchTenantUsers = async () => {
  if (!selectedTenantForRoles.value) {
    tenantUsers.value = []
    return
  }

  tenantUsersLoading.value = true
  try {
    const response = await window.axios.get(`/api/tenants/${selectedTenantForRoles.value}/users`)
    tenantUsers.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching tenant users:', error)
    tenantUsers.value = []
  } finally {
    tenantUsersLoading.value = false
  }
}

// Toggle role expand/collapse and fetch users
const toggleRoleExpand = async (roleId: number) => {
  if (expandedRoles.value.has(roleId)) {
    expandedRoles.value.delete(roleId)
    expandedRoles.value = new Set(expandedRoles.value)
  } else {
    expandedRoles.value.add(roleId)
    expandedRoles.value = new Set(expandedRoles.value)

    // Fetch users for this role if not already fetched
    if (!roleUsers.value[roleId]) {
      await fetchUsersForRole(roleId)
    }
  }
}

// Fetch users for a specific role
const fetchUsersForRole = async (roleId: number) => {
  roleUsersLoading.value = { ...roleUsersLoading.value, [roleId]: true }
  try {
    const response = await window.axios.get(`/api/admin/roles/${roleId}/users`)
    roleUsers.value = { ...roleUsers.value, [roleId]: response.data.data || [] }
  } catch (error) {
    console.error('Error fetching role users:', error)
    roleUsers.value = { ...roleUsers.value, [roleId]: [] }
  } finally {
    roleUsersLoading.value = { ...roleUsersLoading.value, [roleId]: false }
  }
}

// Handle user role change
const handleUserRoleChange = async (user: TenantUser, newRoleId: string) => {
  if (!selectedTenantForRoles.value) return

  updatingUserRole.value = user.id
  try {
    // Determine the base role based on the tenant role
    const selectedRole = roles.value.find(r => r.id === parseInt(newRoleId))
    const baseRole = selectedRole?.name === 'admin' ? 'admin' : 'viewer'

    await window.axios.put(`/api/tenants/${selectedTenantForRoles.value}/users/${user.id}/role`, {
      role: baseRole,
      tenant_role_id: newRoleId ? parseInt(newRoleId) : null
    })

    // Refresh data
    fetchTenantUsers()
    fetchRolesForTenant()

    // Clear cached role users
    roleUsers.value = {}
  } catch (error: any) {
    console.error('Error updating user role:', error)
    alert(error.response?.data?.message || 'Failed to update role')
  } finally {
    updatingUserRole.value = null
  }
}

// Confirm remove user from tenant
const confirmRemoveUserFromTenant = (user: TenantUser) => {
  userToRemove.value = user
  showRemoveUserDialog.value = true
}

// Handle remove user from tenant
const handleRemoveUserFromTenant = async () => {
  if (!selectedTenantForRoles.value || !userToRemove.value) return

  removeUserLoading.value = true
  try {
    await window.axios.delete(`/api/tenants/${selectedTenantForRoles.value}/users/${userToRemove.value.id}`)
    showRemoveUserDialog.value = false
    userToRemove.value = null

    // Refresh data
    fetchTenantUsers()
    fetchRolesForTenant()

    // Clear cached role users
    roleUsers.value = {}
  } catch (error: any) {
    console.error('Error removing user from tenant:', error)
    alert(error.response?.data?.message || 'Failed to remove user')
  } finally {
    removeUserLoading.value = false
  }
}

// Watch for tenant selection in roles tab - removed since we use handleTenantChange now

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

  if (diffMs < 0) return formatDate(dateString) // Future date
  if (diffMinutes < 1) return 'just now'
  if (diffMinutes < 60) return `${diffMinutes} minutes ago`
  if (diffHours < 24) return `${diffHours} hours ago`
  if (diffDays === 1) return 'yesterday'
  if (diffDays < 7) return `${diffDays} days ago`
  if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`
  return formatDate(dateString)
}

onMounted(() => {
  fetchUsers()
  fetchTenants()
  fetchGlobalRoles()
  fetchPermissions()
})
</script>
