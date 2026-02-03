<template>
  <Menu as="div" class="relative inline-block text-left">
    <div>
      <MenuButton
        class="flex items-center px-3 py-2 rounded-md hover:bg-gray-100 transition-colors"
        :aria-label="$t('common.change_language')"
      >
        <span class="text-sm font-medium text-gray-700">
          {{ currentLocale === 'ar' ? 'العربية' : 'EN' }}
        </span>
        <svg
          class="w-4 h-4 ml-1 rtl:ml-0 rtl:mr-1 text-gray-500"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </MenuButton>
    </div>

    <transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <MenuItems
        class="absolute right-0 rtl:right-auto rtl:left-0 z-50 mt-2 w-48 origin-top-right rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none"
      >
        <div class="py-1" role="menu">
          <MenuItem v-for="locale in locales" :key="locale.code" v-slot="{ active, close }">
            <button
              @click="changeLocale(locale.code, close)"
              :class="[
                active ? 'bg-gray-100' : '',
                currentLocale === locale.code ? 'bg-primary-50 text-primary-700' : 'text-gray-700',
                'flex items-center w-full px-4 py-2 text-sm transition-colors'
              ]"
              role="menuitem"
            >
              <span>{{ locale.name }}</span>
              <svg
                v-if="currentLocale === locale.code"
                class="w-4 h-4 ml-auto rtl:ml-0 rtl:mr-auto text-primary-600"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fill-rule="evenodd"
                  d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                  clip-rule="evenodd"
                />
              </svg>
            </button>
          </MenuItem>
        </div>
      </MenuItems>
    </transition>
  </Menu>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'

const { locale } = useI18n()

const locales = [
  { code: 'en', name: 'English', flag: '🇬🇧' },
  { code: 'ar', name: 'العربية', flag: '🇸🇦' }
]

const currentLocale = computed(() => locale.value)

const changeLocale = (newLocale: string, close: () => void) => {
  locale.value = newLocale

  // Save to localStorage
  localStorage.setItem('locale', newLocale)

  // Update document direction
  document.documentElement.dir = newLocale === 'ar' ? 'rtl' : 'ltr'

  // Update html lang attribute
  document.documentElement.lang = newLocale

  // Close dropdown using HeadlessUI's close function
  close()
}

// Initialize locale from localStorage on mount
onMounted(() => {
  const savedLocale = localStorage.getItem('locale')
  if (savedLocale && ['en', 'ar'].includes(savedLocale)) {
    locale.value = savedLocale
    document.documentElement.dir = savedLocale === 'ar' ? 'rtl' : 'ltr'
    document.documentElement.lang = savedLocale
  }
})
</script>
