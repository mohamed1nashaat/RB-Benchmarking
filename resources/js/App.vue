<template>
  <div id="app" class="min-h-screen bg-gray-50">
    <div v-if="authInitialized">
      <router-view />
    </div>
    <div v-else class="flex items-center justify-center min-h-screen">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const authInitialized = ref(false)

onMounted(async () => {
  console.log('App.vue mounted, initializing auth...')
  try {
    // Wait for auth store to initialize
    if (!authStore.initialized) {
      console.log('Auth store not initialized, calling hydrate...')
      await authStore.hydrate()
      console.log('Auth hydrate completed, isAuthenticated:', authStore.isAuthenticated)
    } else {
      console.log('Auth store already initialized, isAuthenticated:', authStore.isAuthenticated)
    }
  } catch (error) {
    console.log('Auth initialization failed:', error)
  } finally {
    console.log('Setting authInitialized to true')
    authInitialized.value = true
  }
})
</script>

<style>
/* Global styles - English fonts (LTR - default) - Elegant/Premium */
body {
  font-family: 'Montserrat', system-ui, -apple-system, sans-serif;
}

h1, h2, h3, h4, h5, h6,
.font-display,
.font-serif {
  font-family: 'Cormorant Garamond', Georgia, serif;
}

/* Arabic fonts (RTL) */
[dir="rtl"] body {
  font-family: 'IBM Plex Sans Arabic', 'Segoe UI', Tahoma, sans-serif;
}

[dir="rtl"] h1,
[dir="rtl"] h2,
[dir="rtl"] h3,
[dir="rtl"] h4,
[dir="rtl"] h5,
[dir="rtl"] h6,
[dir="rtl"] .font-display,
[dir="rtl"] .font-serif {
  font-family: 'Noto Naskh Arabic', 'Traditional Arabic', serif;
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>
