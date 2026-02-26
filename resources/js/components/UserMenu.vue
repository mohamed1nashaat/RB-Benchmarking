<template>
  <Menu as="div" class="relative inline-block text-left">
    <div>
      <MenuButton
        class="inline-flex items-center gap-2 rounded-full bg-white px-2 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
      >
        <!-- Avatar Image -->
        <img
          v-if="authStore.user?.avatar_url"
          :src="authStore.user.avatar_url"
          alt="Profile"
          class="w-8 h-8 rounded-full object-cover"
        />
        <!-- Initials Fallback -->
        <div
          v-else
          class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center"
        >
          <span class="text-sm font-medium text-primary-700">
            {{ userInitials }}
          </span>
        </div>
        <!-- User Name -->
        <span v-if="authStore.user?.name" class="hidden sm:block text-sm font-medium text-gray-700 max-w-[120px] truncate">
          {{ authStore.user.name }}
        </span>
      </MenuButton>
    </div>

    <transition
      enter-active-class="transition duration-100 ease-out"
      enter-from-class="transform scale-95 opacity-0"
      enter-to-class="transform scale-100 opacity-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="transform scale-100 opacity-100"
      leave-to-class="transform scale-95 opacity-0"
    >
      <MenuItems
        class="absolute right-0 z-10 mt-2 w-56 origin-top-right divide-y divide-gray-100 rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
      >
        <!-- User Info -->
        <div class="px-4 py-3">
          <p class="text-sm font-medium text-gray-900 truncate">
            {{ authStore.user?.name }}
          </p>
          <p class="text-xs text-gray-500 truncate">
            {{ authStore.user?.email }}
          </p>
          <p class="text-xs text-gray-400 mt-1 capitalize">
            {{ authStore.currentTenant?.role }} • {{ authStore.currentTenant?.name }}
          </p>
        </div>

        <!-- Main Navigation -->
        <div class="py-1">
          <MenuItem v-slot="{ active }">
            <router-link
              to="/ad-accounts"
              :class="[
                active ? 'bg-gray-50' : '',
                'flex items-center px-4 py-2 text-sm text-gray-700'
              ]"
            >
              <ChartBarIcon class="mr-3 h-5 w-5 text-gray-400" />
              {{ $t('navigation.ad_accounts') }}
            </router-link>
          </MenuItem>

          <MenuItem v-slot="{ active }">
            <router-link
              to="/industry-management"
              :class="[
                active ? 'bg-gray-50' : '',
                'flex items-center px-4 py-2 text-sm text-gray-700'
              ]"
            >
              <BuildingOfficeIcon class="mr-3 h-5 w-5 text-gray-400" />
              {{ $t('navigation.industry_management') }}
            </router-link>
          </MenuItem>

          <MenuItem v-slot="{ active }">
            <router-link
              to="/clients"
              :class="[
                active ? 'bg-gray-50' : '',
                'flex items-center px-4 py-2 text-sm text-gray-700'
              ]"
            >
              <UserGroupIcon class="mr-3 h-5 w-5 text-gray-400" />
              {{ $t('navigation.clients') }}
            </router-link>
          </MenuItem>

        </div>

        <!-- User Management (Admin Only) -->
        <div v-if="isAdmin || isSuperAdmin" class="py-1">
          <MenuItem v-if="isAdmin" v-slot="{ active }">
            <router-link
              to="/users"
              :class="[
                active ? 'bg-gray-50' : '',
                'flex items-center px-4 py-2 text-sm text-gray-700'
              ]"
            >
              <UsersIcon class="mr-3 h-5 w-5 text-gray-400" />
              {{ $t('navigation.users') }}
            </router-link>
          </MenuItem>

          <MenuItem v-if="isAdmin" v-slot="{ active }">
            <router-link
              to="/roles"
              :class="[
                active ? 'bg-gray-50' : '',
                'flex items-center px-4 py-2 text-sm text-gray-700'
              ]"
            >
              <ShieldCheckIcon class="mr-3 h-5 w-5 text-gray-400" />
              {{ $t('navigation.roles') }}
            </router-link>
          </MenuItem>

          <MenuItem v-if="isSuperAdmin" v-slot="{ active }">
            <router-link
              to="/admin/users"
              :class="[
                active ? 'bg-gray-50' : '',
                'flex items-center px-4 py-2 text-sm text-gray-700'
              ]"
            >
              <UserGroupIcon class="mr-3 h-5 w-5 text-gray-400" />
              {{ $t('navigation.admin_users') }}
            </router-link>
          </MenuItem>
        </div>

        <!-- Settings -->
        <div class="py-1">
          <MenuItem v-slot="{ active }">
            <router-link
              to="/integrations"
              :class="[
                active ? 'bg-gray-50' : '',
                'flex items-center px-4 py-2 text-sm text-gray-700'
              ]"
            >
              <PlusIcon class="mr-3 h-5 w-5 text-gray-400" />
              {{ $t('navigation.integrations') }}
            </router-link>
          </MenuItem>

          <MenuItem v-slot="{ active }">
            <router-link
              to="/settings"
              :class="[
                active ? 'bg-gray-50' : '',
                'flex items-center px-4 py-2 text-sm text-gray-700'
              ]"
            >
              <Cog6ToothIcon class="mr-3 h-5 w-5 text-gray-400" />
              {{ $t('navigation.settings') }}
            </router-link>
          </MenuItem>
        </div>

        <!-- Logout -->
        <div class="py-1">
          <MenuItem v-slot="{ active }">
            <button
              :class="[
                active ? 'bg-gray-50' : '',
                'flex w-full items-center px-4 py-2 text-sm text-gray-700'
              ]"
              @click="handleLogout"
            >
              <ArrowRightOnRectangleIcon class="mr-3 h-5 w-5 text-gray-400" />
              {{ $t('auth.logout') }}
            </button>
          </MenuItem>
        </div>
      </MenuItems>
    </transition>
  </Menu>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import {
  ArrowRightOnRectangleIcon,
  PlusIcon,
  BuildingOfficeIcon,
  Cog6ToothIcon,
  ChartBarIcon,
  UserGroupIcon,
  UsersIcon,
  ShieldCheckIcon,
  BellIcon,
  DocumentTextIcon
} from '@heroicons/vue/20/solid'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const router = useRouter()

// Check if user is admin in current tenant
const isAdmin = computed(() => authStore.user?.role === 'admin')

// Check if user is super admin
const isSuperAdmin = computed(() => {
  const user = authStore.user
  return user?.id === 1 || user?.email === 'technical@redbananas.com'
})

const userInitials = computed(() => {
  if (!authStore.user?.name) return ''
  return authStore.user.name
    .split(' ')
    .map(name => name.charAt(0))
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

const handleLogout = async () => {
  try {
    await authStore.logout()
    router.push('/login')
  } catch (error) {
    console.error('Logout error:', error)
    router.push('/login')
  }
}
</script>
