<template>
  <div class="relative" ref="dropdownRef">
    <button
      type="button"
      @click="toggleDropdown"
      :disabled="disabled || loading"
      class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium transition-colors disabled:cursor-not-allowed"
      :class="[
        roleClasses,
        !disabled && !loading ? 'cursor-pointer hover:opacity-80' : ''
      ]"
    >
      <svg v-if="loading" class="animate-spin -ml-0.5 mr-1.5 h-3 w-3" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      {{ displayRoleName }}
      <ChevronDownIcon v-if="!disabled && !loading" class="ml-1 h-3 w-3" />
    </button>

    <transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="isOpen"
        class="absolute z-10 mt-1 w-48 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
      >
        <div class="py-1 max-h-60 overflow-y-auto">
          <!-- Loading state -->
          <div v-if="rolesLoading" class="px-4 py-2 text-sm text-gray-500">
            {{ $t('common.loading') }}...
          </div>

          <!-- Roles list -->
          <template v-else>
            <button
              v-for="role in availableRoles"
              :key="role.id"
              type="button"
              @click="selectRole(role)"
              class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 flex items-center justify-between"
              :class="isCurrentRole(role) ? 'bg-gray-50 text-primary-600' : 'text-gray-700'"
            >
              <div>
                <span>{{ role.display_name }}</span>
                <span v-if="role.is_system" class="ml-1 text-xs text-gray-400">({{ $t('pages.roles.system') }})</span>
              </div>
              <CheckIcon v-if="isCurrentRole(role)" class="h-4 w-4" />
            </button>
          </template>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { ChevronDownIcon, CheckIcon } from '@heroicons/vue/20/solid'
import { useAuthStore } from '@/stores/auth'
import type { TenantUser, TenantRole, UserRole } from '@/types/user'

interface Props {
  user: TenantUser
  disabled?: boolean
}

interface Emits {
  (e: 'change', role: UserRole, roleId?: number): void
}

const props = withDefaults(defineProps<Props>(), {
  disabled: false,
})

const emit = defineEmits<Emits>()

const authStore = useAuthStore()

const isOpen = ref(false)
const loading = ref(false)
const rolesLoading = ref(false)
const dropdownRef = ref<HTMLElement | null>(null)
const availableRoles = ref<TenantRole[]>([])

const currentTenantId = computed(() => authStore.currentTenant?.id)

const displayRoleName = computed(() => {
  // If user has a tenant_role, use its display_name
  if (props.user.tenant_role) {
    return props.user.tenant_role.display_name
  }
  // Check if we have loaded roles and can find a matching one
  const matchedRole = availableRoles.value.find(r => r.id === props.user.tenant_role_id)
  if (matchedRole) {
    return matchedRole.display_name
  }
  // Fall back to legacy role translation
  return props.user.role === 'admin' ? 'Admin' : 'Viewer'
})

const roleClasses = computed(() => {
  return props.user.role === 'admin'
    ? 'bg-primary-100 text-primary-800'
    : 'bg-gray-100 text-gray-800'
})

const isCurrentRole = (role: TenantRole): boolean => {
  if (props.user.tenant_role_id) {
    return role.id === props.user.tenant_role_id
  }
  // Fall back to legacy role matching
  return role.name === props.user.role && role.is_system
}

const fetchRoles = async () => {
  if (!currentTenantId.value) return

  rolesLoading.value = true
  try {
    const response = await window.axios.get(`/api/tenants/${currentTenantId.value}/roles`)
    availableRoles.value = response.data.data
  } catch (error) {
    console.error('Error fetching roles:', error)
    // Fall back to default roles
    availableRoles.value = [
      { id: 0, name: 'viewer', display_name: 'Viewer', description: null, is_system: true, permissions: [], users_count: 0, created_at: null, updated_at: null },
      { id: 0, name: 'admin', display_name: 'Admin', description: null, is_system: true, permissions: [], users_count: 0, created_at: null, updated_at: null },
    ]
  } finally {
    rolesLoading.value = false
  }
}

const toggleDropdown = async () => {
  if (props.disabled || loading.value) return

  if (!isOpen.value && availableRoles.value.length === 0) {
    await fetchRoles()
  }

  isOpen.value = !isOpen.value
}

const selectRole = async (role: TenantRole) => {
  if (isCurrentRole(role)) {
    isOpen.value = false
    return
  }

  loading.value = true
  isOpen.value = false

  // Emit the legacy role (admin/viewer) for backward compatibility
  // and the role ID for the new system
  const legacyRole = role.is_system ? (role.name as UserRole) : 'viewer'
  emit('change', legacyRole, role.id)

  // Reset loading after a short delay (parent will handle the actual update)
  setTimeout(() => {
    loading.value = false
  }, 500)
}

const handleClickOutside = (event: MouseEvent) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
    isOpen.value = false
  }
}

// Watch for tenant changes to reset roles
watch(currentTenantId, () => {
  availableRoles.value = []
})

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>
