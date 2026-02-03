<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Top Navigation Bar -->
    <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <!-- Left: Logo + Primary Nav -->
          <div class="flex items-center space-x-8">
            <!-- Logo -->
            <router-link to="/ad-accounts" class="flex-shrink-0">
              <img :src="logoUrl" alt="RB Benchmarks" class="h-8 w-auto">
            </router-link>

            <!-- Desktop Navigation -->
            <nav class="hidden lg:flex items-center space-x-1">
              <router-link
                v-for="item in primaryNavigation"
                :key="item.name"
                :to="item.href"
                :class="[
                  'px-3 py-2 text-sm font-medium rounded-md transition-colors',
                  isActiveRoute(item.href)
                    ? 'bg-primary-50 text-primary-700'
                    : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                ]"
              >
                {{ $t(item.label) }}
              </router-link>
            </nav>
          </div>

          <!-- Right: Actions -->
          <div class="flex items-center space-x-3">
            <!-- Desktop Secondary Nav -->
            <div class="hidden lg:flex items-center space-x-1">
              <router-link
                v-for="item in secondaryNavigation"
                :key="item.name"
                :to="item.href"
                :class="[
                  'p-2 rounded-md transition-colors',
                  isActiveRoute(item.href)
                    ? 'bg-primary-50 text-primary-700'
                    : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'
                ]"
                :title="$t(item.label)"
              >
                <component :is="item.icon" class="h-5 w-5" />
              </router-link>
            </div>

            <!-- Language Switcher -->
            <LanguageSwitcher />

            <!-- User Menu -->
            <UserMenu />

            <!-- Mobile menu button -->
            <button
              @click="mobileMenuOpen = !mobileMenuOpen"
              class="lg:hidden p-2 rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-700"
            >
              <Bars3Icon v-if="!mobileMenuOpen" class="h-6 w-6" />
              <XMarkIcon v-else class="h-6 w-6" />
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile Navigation Menu -->
      <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-1"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-1"
      >
        <div v-if="mobileMenuOpen" class="lg:hidden border-t border-gray-200 bg-white">
          <div class="px-4 py-3 space-y-1">
            <router-link
              v-for="item in [...primaryNavigation, ...secondaryNavigation]"
              :key="item.name"
              :to="item.href"
              @click="mobileMenuOpen = false"
              :class="[
                'flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors',
                isActiveRoute(item.href)
                  ? 'bg-primary-50 text-primary-700'
                  : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
              ]"
            >
              <component :is="item.icon" class="h-5 w-5 mr-3" />
              {{ $t(item.label) }}
            </router-link>
          </div>
        </div>
      </transition>
    </header>

    <!-- Page Content -->
    <main class="flex-1">
      <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <router-view />
      </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex justify-between items-center text-sm text-gray-500">
          <div class="hidden sm:block">
            © {{ currentYear }} RB Benchmarks. All rights reserved.
          </div>
          <div class="space-x-4">
            <router-link to="/privacy-policy" class="hover:text-gray-700">
              Privacy Policy
            </router-link>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import {
  ChartBarIcon,
  PresentationChartBarIcon,
  BuildingOfficeIcon,
  UserGroupIcon,
  BellIcon,
  DocumentTextIcon,
  Cog6ToothIcon,
  Bars3Icon,
  XMarkIcon,
  LinkIcon
} from '@heroicons/vue/24/outline'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'
import UserMenu from '@/components/UserMenu.vue'

const route = useRoute()
const logoUrl = '/logo.svg'
const currentYear = computed(() => new Date().getFullYear())
const mobileMenuOpen = ref(false)

// Primary navigation items (shown as text links)
const primaryNavigation = [
  {
    name: 'benchmarks',
    href: '/benchmarks',
    label: 'navigation.benchmarks',
    icon: PresentationChartBarIcon
  },
  {
    name: 'industry-overview',
    href: '/industry-overview',
    label: 'navigation.industry_overview',
    icon: BuildingOfficeIcon
  }
]

// Secondary navigation items (shown as icons on desktop)
const secondaryNavigation = []

// Check if route is active
const isActiveRoute = (href: string) => {
  return route.path === href || route.path.startsWith(href + '/')
}
</script>
