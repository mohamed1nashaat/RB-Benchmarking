<template>
  <div class="py-6">
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('pages.roles.title') }}</h1>
          <p class="mt-1 text-sm text-gray-500">
            {{ $t('pages.roles.description') }}
          </p>
        </div>
        <button
          @click="openCreateModal"
          class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
        >
          <PlusIcon class="w-5 h-5 mr-2" />
          {{ $t('pages.roles.create_role') }}
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="bg-white shadow rounded-lg p-6">
      <div class="animate-pulse space-y-4">
        <div v-for="i in 3" :key="i" class="flex items-center justify-between border-b border-gray-100 pb-4">
          <div class="flex-1">
            <div class="h-5 bg-gray-200 rounded w-1/4 mb-2"></div>
            <div class="h-4 bg-gray-200 rounded w-1/3"></div>
          </div>
          <div class="flex space-x-2">
            <div class="h-8 bg-gray-200 rounded w-16"></div>
            <div class="h-8 bg-gray-200 rounded w-16"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Roles List -->
    <div v-else-if="roles.length > 0" class="bg-white shadow rounded-lg divide-y divide-gray-200">
      <div
        v-for="role in roles"
        :key="role.id"
        class="p-6 hover:bg-gray-50"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="flex items-center gap-2">
              <h3 class="text-lg font-medium text-gray-900">{{ role.display_name }}</h3>
              <span
                v-if="role.is_system"
                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800"
              >
                {{ $t('pages.roles.system_role') }}
              </span>
            </div>
            <p class="mt-1 text-sm text-gray-500">{{ role.description || $t('pages.roles.no_description') }}</p>
            <div class="mt-3 flex items-center gap-4 text-sm text-gray-500">
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
            <div class="mt-3 flex flex-wrap gap-1">
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
              @click="openEditModal(role)"
              class="px-3 py-1.5 text-sm border border-gray-300 rounded-md hover:bg-gray-50"
            >
              {{ $t('buttons.edit') }}
            </button>
            <button
              v-if="!role.is_system"
              @click="confirmDeleteRole(role)"
              class="px-3 py-1.5 text-sm text-red-600 border border-red-300 rounded-md hover:bg-red-50"
            >
              {{ $t('buttons.delete') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12 bg-white shadow rounded-lg">
      <ShieldCheckIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('pages.roles.no_roles') }}</h3>
      <p class="mt-1 text-sm text-gray-500">{{ $t('pages.roles.no_roles_description') }}</p>
      <div class="mt-6">
        <button
          @click="openCreateModal"
          class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700"
        >
          <PlusIcon class="w-5 h-5 mr-2" />
          {{ $t('pages.roles.create_role') }}
        </button>
      </div>
    </div>

    <!-- Create/Edit Role Modal -->
    <RoleFormModal
      :open="showFormModal"
      :role="selectedRole"
      :permissions="groupedPermissions"
      @close="closeFormModal"
      @saved="handleRoleSaved"
    />

    <!-- Delete Confirmation -->
    <ConfirmDialog
      :open="showDeleteDialog"
      :title="$t('pages.roles.delete_role')"
      :message="deleteMessage"
      :confirm-text="$t('buttons.delete')"
      :loading="deleteLoading"
      variant="danger"
      @close="showDeleteDialog = false"
      @confirm="handleDeleteRole"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { PlusIcon, UsersIcon, KeyIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import { useI18n } from 'vue-i18n'
import RoleFormModal from '@/components/RoleFormModal.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import type { TenantRole, GroupedPermissions } from '@/types/user'

const { t } = useI18n()
const authStore = useAuthStore()

const roles = ref<TenantRole[]>([])
const groupedPermissions = ref<GroupedPermissions>({})
const loading = ref(false)
const showFormModal = ref(false)
const selectedRole = ref<TenantRole | null>(null)
const showDeleteDialog = ref(false)
const roleToDelete = ref<TenantRole | null>(null)
const deleteLoading = ref(false)

const currentTenantId = computed(() => authStore.currentTenant?.id)

const deleteMessage = computed(() => {
  if (!roleToDelete.value) return ''
  const usersCount = roleToDelete.value.users_count
  if (usersCount > 0) {
    return t('pages.roles.delete_message_with_users', { name: roleToDelete.value.display_name, count: usersCount })
  }
  return t('pages.roles.delete_message', { name: roleToDelete.value.display_name })
})

const fetchRoles = async () => {
  if (!currentTenantId.value) return

  loading.value = true
  try {
    const response = await window.axios.get(`/api/tenants/${currentTenantId.value}/roles`)
    roles.value = response.data.data
  } catch (error) {
    console.error('Error fetching roles:', error)
  } finally {
    loading.value = false
  }
}

const fetchPermissions = async () => {
  if (!currentTenantId.value) return

  try {
    const response = await window.axios.get(`/api/tenants/${currentTenantId.value}/roles/permissions`)
    groupedPermissions.value = response.data.data
  } catch (error) {
    console.error('Error fetching permissions:', error)
  }
}

const openCreateModal = () => {
  selectedRole.value = null
  showFormModal.value = true
}

const openEditModal = (role: TenantRole) => {
  selectedRole.value = role
  showFormModal.value = true
}

const closeFormModal = () => {
  showFormModal.value = false
  selectedRole.value = null
}

const handleRoleSaved = () => {
  closeFormModal()
  fetchRoles()
}

const confirmDeleteRole = (role: TenantRole) => {
  roleToDelete.value = role
  showDeleteDialog.value = true
}

const handleDeleteRole = async () => {
  if (!currentTenantId.value || !roleToDelete.value) return

  deleteLoading.value = true
  try {
    await window.axios.delete(`/api/tenants/${currentTenantId.value}/roles/${roleToDelete.value.id}`)
    showDeleteDialog.value = false
    roleToDelete.value = null
    fetchRoles()
  } catch (error: any) {
    console.error('Error deleting role:', error)
    alert(error.response?.data?.message || 'Failed to delete role')
  } finally {
    deleteLoading.value = false
  }
}

// Watch for tenant changes
watch(currentTenantId, (newVal) => {
  if (newVal) {
    fetchRoles()
    fetchPermissions()
  }
})

onMounted(() => {
  if (currentTenantId.value) {
    fetchRoles()
    fetchPermissions()
  }
})
</script>
