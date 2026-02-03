<template>
  <div class="relative">
    <Listbox v-model="selectedTenant" @update:model-value="handleTenantChange">
      <div class="relative">
        <ListboxButton
          class="relative w-48 cursor-default rounded-lg bg-white py-2 pl-3 pr-10 text-left shadow-md focus:outline-none focus-visible:border-primary-500 focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-opacity-75 focus-visible:ring-offset-2 focus-visible:ring-offset-orange-300 sm:text-sm"
        >
          <span class="block truncate">
            {{ selectedTenant?.name || 'All Tenants' }}
          </span>
          <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
            <ChevronUpDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
          </span>
        </ListboxButton>

        <transition
          leave-active-class="transition duration-100 ease-in"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <ListboxOptions
            class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
          >
            <!-- All Tenants option for super admins -->
            <ListboxOption
              v-if="authStore.isSuperAdmin"
              v-slot="{ active, selected }"
              :value="null"
              as="template"
            >
              <li
                :class="[
                  active ? 'bg-amber-100 text-amber-900' : 'text-gray-900',
                  'relative cursor-default select-none py-2 pl-10 pr-4',
                ]"
              >
                <div class="flex flex-col">
                  <span
                    :class="[
                      selected ? 'font-medium' : 'font-normal',
                      'block truncate',
                    ]"
                  >
                    All Tenants
                  </span>
                  <span class="text-xs text-gray-500">
                    View all clients
                  </span>
                </div>
                <span
                  v-if="selected"
                  class="absolute inset-y-0 left-0 flex items-center pl-3 text-amber-600"
                >
                  <CheckIcon class="h-5 w-5" aria-hidden="true" />
                </span>
              </li>
            </ListboxOption>

            <!-- Individual tenants -->
            <ListboxOption
              v-slot="{ active, selected }"
              v-for="tenant in authStore.tenants"
              :key="tenant.id"
              :value="tenant"
              as="template"
            >
              <li
                :class="[
                  active ? 'bg-amber-100 text-amber-900' : 'text-gray-900',
                  'relative cursor-default select-none py-2 pl-10 pr-4',
                ]"
              >
                <div class="flex flex-col">
                  <span
                    :class="[
                      selected ? 'font-medium' : 'font-normal',
                      'block truncate',
                    ]"
                  >
                    {{ tenant.name }}
                  </span>
                  <span class="text-xs text-gray-500">
                    {{ tenant.ad_accounts_count || 0 }} accounts
                  </span>
                </div>
                <span
                  v-if="selected"
                  class="absolute inset-y-0 left-0 flex items-center pl-3 text-amber-600"
                >
                  <CheckIcon class="h-5 w-5" aria-hidden="true" />
                </span>
              </li>
            </ListboxOption>
          </ListboxOptions>
        </transition>
      </div>
    </Listbox>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  Listbox,
  ListboxButton,
  ListboxOptions,
  ListboxOption,
} from '@headlessui/vue'
import { CheckIcon, ChevronUpDownIcon } from '@heroicons/vue/20/solid'
import { useAuthStore } from '@/stores/auth'
import type { Tenant } from '@/stores/auth'

const authStore = useAuthStore()

const selectedTenant = computed({
  get: () => authStore.currentTenant,
  set: (value: Tenant | null) => {
    // Allow setting null for "All Tenants"
    if (value || value === null) {
      if (value === null) {
        // Clear current tenant for "All Tenants"
        authStore.currentTenant = null
        localStorage.removeItem('current_tenant_id')
        sessionStorage.removeItem('current_tenant_id')
      } else {
        authStore.setCurrentTenant(value)
      }
    }
  }
})

const handleTenantChange = (tenant: Tenant | null) => {
  if (tenant === null) {
    // Handle "All Tenants" selection
    authStore.currentTenant = null
    localStorage.removeItem('current_tenant_id')
    sessionStorage.removeItem('current_tenant_id')
  } else {
    authStore.setCurrentTenant(tenant)
  }
  // Refresh the page to reload data for new tenant
  window.location.reload()
}
</script>
