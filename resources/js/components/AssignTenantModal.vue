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
            <DialogPanel class="w-full max-w-lg transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
              <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                {{ $t('pages.admin_users.manage_tenants') }}
              </DialogTitle>
              <p class="mt-1 text-sm text-gray-500">
                {{ $t('pages.admin_users.manage_tenants_for', { name: user?.name }) }}
              </p>

              <!-- Current Tenants -->
              <div class="mt-4">
                <h4 class="text-sm font-medium text-gray-700 mb-2">{{ $t('pages.admin_users.current_tenants') }}</h4>
                <div v-if="user && user.tenants.length > 0" class="space-y-2">
                  <div
                    v-for="tenant in user.tenants"
                    :key="tenant.id"
                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                  >
                    <div class="flex items-center">
                      <span class="font-medium text-gray-900">{{ tenant.name }}</span>
                      <span
                        class="ml-2 px-2 py-0.5 text-xs rounded-full"
                        :class="tenant.role === 'admin' ? 'bg-primary-100 text-primary-800' : 'bg-gray-200 text-gray-700'"
                      >
                        {{ $t(`roles.${tenant.role}`) }}
                      </span>
                    </div>
                    <div class="flex items-center space-x-2">
                      <select
                        :value="tenant.role"
                        @change="updateRole(tenant.id, ($event.target as HTMLSelectElement).value)"
                        class="text-sm rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                        :disabled="updatingRoleId === tenant.id"
                      >
                        <option value="viewer">{{ $t('roles.viewer') }}</option>
                        <option value="admin">{{ $t('roles.admin') }}</option>
                      </select>
                      <button
                        @click="removeTenant(tenant.id)"
                        :disabled="removingTenantId === tenant.id"
                        class="p-1 text-red-600 hover:text-red-800 disabled:opacity-50"
                        :title="$t('pages.admin_users.remove_from_tenant')"
                      >
                        <XMarkIcon class="h-5 w-5" />
                      </button>
                    </div>
                  </div>
                </div>
                <p v-else class="text-sm text-gray-500 italic">
                  {{ $t('pages.admin_users.no_tenants') }}
                </p>
              </div>

              <!-- Add to Tenant -->
              <div class="mt-6 pt-4 border-t border-gray-200">
                <h4 class="text-sm font-medium text-gray-700 mb-2">{{ $t('pages.admin_users.add_to_tenant') }}</h4>
                <div class="flex space-x-2">
                  <select
                    v-model="selectedTenantId"
                    class="flex-1 rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                    :disabled="addingTenant"
                  >
                    <option :value="null">{{ $t('pages.admin_users.select_tenant') }}</option>
                    <option
                      v-for="tenant in availableTenants"
                      :key="tenant.id"
                      :value="tenant.id"
                    >
                      {{ tenant.name }}
                    </option>
                  </select>
                  <select
                    v-model="selectedRole"
                    class="w-32 rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                    :disabled="addingTenant"
                  >
                    <option value="viewer">{{ $t('roles.viewer') }}</option>
                    <option value="admin">{{ $t('roles.admin') }}</option>
                  </select>
                  <button
                    @click="addTenant"
                    :disabled="!selectedTenantId || addingTenant"
                    class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <svg v-if="addingTenant" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span v-else>{{ $t('common.add') }}</span>
                  </button>
                </div>
                <p v-if="availableTenants.length === 0" class="mt-2 text-sm text-gray-500">
                  {{ $t('pages.admin_users.user_in_all_tenants') }}
                </p>
              </div>

              <!-- Error Message -->
              <div v-if="error" class="mt-4 p-3 rounded-md bg-red-50 border border-red-200">
                <p class="text-sm text-red-600">{{ error }}</p>
              </div>

              <!-- Close Button -->
              <div class="mt-6 flex justify-end">
                <button
                  type="button"
                  @click="handleClose"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                >
                  {{ $t('common.close') }}
                </button>
              </div>
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
import { XMarkIcon } from '@heroicons/vue/24/outline'
import type { AdminUser, TenantOption, UserRole } from '@/types/user'

interface Props {
  open: boolean
  user: AdminUser | null
  tenants: TenantOption[]
}

interface Emits {
  (e: 'close'): void
  (e: 'assigned'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const selectedTenantId = ref<number | null>(null)
const selectedRole = ref<UserRole>('viewer')
const addingTenant = ref(false)
const removingTenantId = ref<number | null>(null)
const updatingRoleId = ref<number | null>(null)
const error = ref('')

const availableTenants = computed(() => {
  if (!props.user) return props.tenants
  const userTenantIds = props.user.tenants.map(t => t.id)
  return props.tenants.filter(t => !userTenantIds.includes(t.id))
})

const handleClose = () => {
  selectedTenantId.value = null
  selectedRole.value = 'viewer'
  error.value = ''
  emit('close')
}

const addTenant = async () => {
  if (!props.user || !selectedTenantId.value) return

  addingTenant.value = true
  error.value = ''

  try {
    await window.axios.post(`/api/admin/users/${props.user.id}/tenants`, {
      tenant_id: selectedTenantId.value,
      role: selectedRole.value,
    })
    selectedTenantId.value = null
    selectedRole.value = 'viewer'
    emit('assigned')
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to add user to tenant'
  } finally {
    addingTenant.value = false
  }
}

const removeTenant = async (tenantId: number) => {
  if (!props.user) return

  removingTenantId.value = tenantId
  error.value = ''

  try {
    await window.axios.delete(`/api/admin/users/${props.user.id}/tenants/${tenantId}`)
    emit('assigned')
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to remove user from tenant'
  } finally {
    removingTenantId.value = null
  }
}

const updateRole = async (tenantId: number, role: string) => {
  if (!props.user) return

  updatingRoleId.value = tenantId
  error.value = ''

  try {
    await window.axios.put(`/api/admin/users/${props.user.id}/tenants/${tenantId}/role`, {
      role,
    })
    emit('assigned')
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to update role'
  } finally {
    updatingRoleId.value = null
  }
}

watch(() => props.open, (newVal) => {
  if (newVal) {
    selectedTenantId.value = null
    selectedRole.value = 'viewer'
    error.value = ''
  }
})
</script>
