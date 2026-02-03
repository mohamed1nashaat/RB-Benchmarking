<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="max-w-md w-full space-y-8">
      <div class="text-center">
        <div v-if="processing" class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto"></div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          {{ processing ? $t('pages.linkedin_callback.processing') : status.title }}
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          {{ status.message }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const processing = ref(true)
const status = ref({
  title: t('pages.linkedin_callback.processing_title'),
  message: t('pages.linkedin_callback.processing_message')
})

onMounted(async () => {
  try {
    // Get the authorization code from URL parameters
    const code = route.query.code
    const state = route.query.state
    const error = route.query.error
    const errorDescription = route.query.error_description

    if (error) {
      throw new Error(t('pages.linkedin_callback.oauth_error', { error: errorDescription || error }))
    }

    if (!code || !state) {
      throw new Error(t('pages.linkedin_callback.missing_parameters'))
    }

    // Send the code and state to the backend API endpoint
    const response = await axios.get('/api/linkedin/oauth/callback', {
      params: {
        code,
        state
      }
    })

    status.value = {
      title: t('pages.linkedin_callback.success_title'),
      message: t('pages.linkedin_callback.success_message')
    }

    // Redirect to integrations page after a short delay
    setTimeout(() => {
      router.push('/integrations?success=linkedin_connected')
    }, 2000)

  } catch (error: any) {
    console.error('LinkedIn callback error:', error)
    status.value = {
      title: t('pages.linkedin_callback.error_title'),
      message: error.message || t('pages.linkedin_callback.error_message')
    }

    // Redirect to integrations page with error after a delay
    setTimeout(() => {
      router.push('/integrations?error=linkedin_failed')
    }, 3000)
  } finally {
    processing.value = false
  }
})
</script>
