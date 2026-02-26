<template>
  <TransitionRoot appear :show="open" as="template">
    <Dialog as="div" @close="handleClose" class="relative z-50">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black bg-opacity-25" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
              <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                {{ props.isGlobalRole ? $t('pages.roles.edit_global_role', 'Edit Global Role') : (isEditing ? $t('pages.roles.edit_role') : $t('pages.roles.create_role')) }}
              </DialogTitle>

              <form @submit.prevent="handleSubmit" class="mt-4">
                <!-- Tenant Selector (only for super admin creating new roles) -->
                <div class="mb-4" v-if="showTenantSelector">
                  <label for="tenant_id" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ $t('labels.tenant') }} <span class="text-red-500">*</span>
                  </label>
                  <select
                    id="tenant_id"
                    v-model="selectedTenantId"
                    required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    :class="{ 'border-red-300': errors.tenant_id }"
                  >
                    <option :value="null" disabled>{{ $t('placeholders.select_option') }}</option>
                    <option v-for="tenant in props.tenants" :key="tenant.id" :value="tenant.id">
                      {{ tenant.name }}
                    </option>
                  </select>
                  <p v-if="errors.tenant_id" class="mt-1 text-sm text-red-600">{{ errors.tenant_id }}</p>
                </div>

                <!-- Role Name -->
                <div class="mb-4" v-if="!isSystemRole">
                  <label for="display_name" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ $t('pages.roles.role_name') }} <span class="text-red-500">*</span>
                  </label>
                  <input
                    id="display_name"
                    v-model="form.display_name"
                    type="text"
                    required
                    :placeholder="$t('pages.roles.role_name_placeholder')"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    :class="{ 'border-red-300': errors.display_name }"
                  />
                  <p v-if="errors.display_name" class="mt-1 text-sm text-red-600">{{ errors.display_name }}</p>
                </div>

                <!-- Global Role Notice -->
                <div v-if="props.isGlobalRole" class="mb-4 p-3 rounded-md bg-amber-50 border border-amber-200">
                  <p class="text-sm text-amber-700">
                    <strong>{{ $t('pages.roles.global_role_notice_title', 'Global Role') }}:</strong>
                    {{ $t('pages.roles.global_role_notice', 'Changing permissions here will update this role for ALL organizations.') }}
                  </p>
                </div>

                <!-- System Role Notice -->
                <div v-else-if="isSystemRole" class="mb-4 p-3 rounded-md bg-blue-50 border border-blue-200">
                  <p class="text-sm text-blue-700">{{ $t('pages.roles.system_role_notice') }}</p>
                </div>

                <!-- Description -->
                <div class="mb-4" v-if="!isSystemRole">
                  <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ $t('pages.roles.description') }}
                  </label>
                  <textarea
                    id="description"
                    v-model="form.description"
                    rows="2"
                    :placeholder="$t('pages.roles.description_placeholder')"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                  ></textarea>
                </div>

                <!-- Permissions -->
                <div class="mb-6">
                  <label class="block text-sm font-medium text-gray-700 mb-3">
                    {{ $t('pages.roles.permissions') }} <span class="text-red-500">*</span>
                  </label>

                  <!-- Select All -->
                  <div class="mb-3 flex items-center justify-between border-b border-gray-200 pb-3">
                    <span class="text-sm text-gray-600">{{ $t('pages.roles.select_all') }}</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                      <input
                        type="checkbox"
                        :checked="allSelected"
                        @change="toggleAll"
                        class="sr-only peer"
                      />
                      <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                    </label>
                  </div>

                  <!-- Permission Groups -->
                  <div class="space-y-4 max-h-80 overflow-y-auto pr-2">
                    <div v-for="(groupPermissions, groupName) in permissions" :key="groupName" class="border border-gray-200 rounded-lg">
                      <!-- Group Header -->
                      <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                        <span class="font-medium text-sm text-gray-700 capitalize">{{ formatGroupName(groupName as string) }}</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                          <input
                            type="checkbox"
                            :checked="isGroupSelected(groupPermissions)"
                            @change="toggleGroup(groupPermissions)"
                            class="sr-only peer"
                          />
                          <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-600"></div>
                        </label>
                      </div>
                      <!-- Group Permissions -->
                      <div class="p-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <label
                          v-for="permission in groupPermissions"
                          :key="permission.id"
                          class="flex items-start space-x-3 p-2 rounded hover:bg-gray-50 cursor-pointer"
                        >
                          <input
                            type="checkbox"
                            :checked="form.permissions.includes(permission.id)"
                            @change="togglePermission(permission.id)"
                            class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded mt-0.5"
                          />
                          <div class="flex-1 min-w-0">
                            <span class="block text-sm font-medium text-gray-900">{{ permission.display_name }}</span>
                            <span v-if="permission.description" class="block text-xs text-gray-500 truncate">{{ permission.description }}</span>
                          </div>
                        </label>
                      </div>
                    </div>
                  </div>

                  <p v-if="errors.permissions" class="mt-2 text-sm text-red-600">{{ errors.permissions }}</p>
                  <p class="mt-2 text-sm text-gray-500">
                    {{ form.permissions.length }} {{ $t('pages.roles.permissions_selected') }}
                  </p>
                </div>

                <!-- Error Message -->
                <div v-if="error" class="mb-4 p-3 rounded-md bg-red-50 border border-red-200">
                  <p class="text-sm text-red-600">{{ error }}</p>
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3">
                  <button
                    type="button"
                    @click="handleClose"
                    :disabled="loading"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50"
                  >
                    {{ $t('common.cancel') }}
                  </button>
                  <button
                    type="submit"
                    :disabled="loading || !isValid"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50"
                  >
                    <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ loading ? $t('common.saving') : (isEditing ? $t('buttons.save') : $t('pages.roles.create_role')) }}
                  </button>
                </div>
              </form>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { useAuthStore } from '@/stores/auth'
import type { TenantRole, Permission, GroupedPermissions, CreateRoleForm } from '@/types/user'

interface TenantOption {
  id: number
  name: string
}

interface Props {
  open: boolean
  role: TenantRole | null
  permissions: GroupedPermissions
  tenants?: TenantOption[]
  preselectedTenantId?: number | null
  isGlobalRole?: boolean
  globalRoleName?: string | null
}

interface Emits {
  (e: 'close'): void
  (e: 'saved'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const authStore = useAuthStore()

const form = ref<CreateRoleForm>({
  name: '',
  display_name: '',
  description: '',
  permissions: [],
})

const selectedTenantId = ref<number | null>(null)
const errors = ref<Record<string, string>>({})
const error = ref('')
const loading = ref(false)

const isEditing = computed(() => !!props.role)
const isSystemRole = computed(() => props.role?.is_system ?? false)
const showTenantSelector = computed(() => !isEditing.value && props.tenants && props.tenants.length > 0)

const allPermissionIds = computed(() => {
  const ids: number[] = []
  Object.values(props.permissions).forEach((group) => {
    group.forEach((p) => ids.push(p.id))
  })
  return ids
})

const allSelected = computed(() => {
  return allPermissionIds.value.length > 0 &&
    allPermissionIds.value.every((id) => form.value.permissions.includes(id))
})

const isValid = computed(() => {
  if (isSystemRole.value) {
    return form.value.permissions.length > 0
  }
  const hasName = form.value.display_name.trim()
  const hasPermissions = form.value.permissions.length > 0
  // If creating with tenant selector, require tenant selection
  if (showTenantSelector.value) {
    return hasName && hasPermissions && selectedTenantId.value !== null
  }
  return hasName && hasPermissions
})

const formatGroupName = (name: string): string => {
  return name.replace(/_/g, ' ')
}

const isGroupSelected = (groupPermissions: Permission[]): boolean => {
  return groupPermissions.every((p) => form.value.permissions.includes(p.id))
}

const toggleGroup = (groupPermissions: Permission[]) => {
  const groupIds = groupPermissions.map((p) => p.id)
  const allSelected = groupIds.every((id) => form.value.permissions.includes(id))

  if (allSelected) {
    form.value.permissions = form.value.permissions.filter((id) => !groupIds.includes(id))
  } else {
    const newIds = groupIds.filter((id) => !form.value.permissions.includes(id))
    form.value.permissions = [...form.value.permissions, ...newIds]
  }
}

const toggleAll = () => {
  if (allSelected.value) {
    form.value.permissions = []
  } else {
    form.value.permissions = [...allPermissionIds.value]
  }
}

const togglePermission = (id: number) => {
  const index = form.value.permissions.indexOf(id)
  if (index === -1) {
    form.value.permissions.push(id)
  } else {
    form.value.permissions.splice(index, 1)
  }
}

const resetForm = () => {
  form.value = {
    name: '',
    display_name: '',
    description: '',
    permissions: [],
  }
  selectedTenantId.value = props.preselectedTenantId || null
  errors.value = {}
  error.value = ''
}

const handleClose = () => {
  if (!loading.value) {
    resetForm()
    emit('close')
  }
}

const handleSubmit = async () => {
  errors.value = {}
  error.value = ''
  loading.value = true

  try {
    const payload: any = {
      permissions: form.value.permissions,
    }

    // Handle global role update (updates all tenants)
    if (props.isGlobalRole && props.globalRoleName) {
      await window.axios.put(`/api/admin/global-roles/${props.globalRoleName}`, payload)
      resetForm()
      emit('saved')
      return
    }

    // Determine tenant ID: use selected tenant for super admin, or current tenant for regular users
    const tenantId = showTenantSelector.value
      ? selectedTenantId.value
      : (isEditing.value && props.role?.tenant_id ? props.role.tenant_id : authStore.currentTenant?.id)

    if (!tenantId && !showTenantSelector.value) {
      loading.value = false
      return
    }

    if (!isSystemRole.value) {
      payload.name = form.value.display_name.toLowerCase().replace(/\s+/g, '-')
      payload.display_name = form.value.display_name
      payload.description = form.value.description || null
    }

    if (isEditing.value && props.role) {
      // For editing, use super admin endpoint if available
      if (props.tenants && props.tenants.length > 0) {
        await window.axios.put(`/api/admin/roles/${props.role.id}`, payload)
      } else {
        await window.axios.put(`/api/tenants/${tenantId}/roles/${props.role.id}`, payload)
      }
    } else {
      // For creating, use super admin endpoint if in super admin mode
      if (showTenantSelector.value) {
        payload.tenant_id = selectedTenantId.value
        await window.axios.post('/api/admin/roles', payload)
      } else {
        await window.axios.post(`/api/tenants/${tenantId}/roles`, payload)
      }
    }

    resetForm()
    emit('saved')
  } catch (err: any) {
    if (err.response?.status === 422) {
      if (err.response.data.errors) {
        errors.value = Object.fromEntries(
          Object.entries(err.response.data.errors).map(([key, value]) => [key, (value as string[])[0]])
        )
      } else if (err.response.data.message) {
        error.value = err.response.data.message
      }
    } else {
      error.value = err.response?.data?.message || 'Failed to save role'
    }
  } finally {
    loading.value = false
  }
}

// Initialize form when modal opens or role changes
watch([() => props.open, () => props.role], ([newOpen, newRole]) => {
  if (newOpen) {
    if (newRole) {
      // Editing existing role
      form.value = {
        name: newRole.name,
        display_name: newRole.display_name,
        description: newRole.description || '',
        permissions: newRole.permissions.map((p) => p.id),
      }
      selectedTenantId.value = newRole.tenant_id || null
    } else {
      // Creating new role
      resetForm()
    }
  }
}, { immediate: true })
</script>
