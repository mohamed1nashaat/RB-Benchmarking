<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <img class="mx-auto h-12 w-auto" :src="logoUrl" alt="RB Benchmarks" />
      <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
        {{ pageTitle }}
      </h2>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-8">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto"></div>
          <p class="mt-4 text-gray-500">{{ $t('pages.invitation.verifying') }}</p>
        </div>

        <!-- Invalid/Expired Invitation -->
        <div v-else-if="!invitation || !invitation.valid" class="text-center py-8">
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
            <ExclamationTriangleIcon class="h-6 w-6 text-red-600" />
          </div>
          <h3 class="mt-4 text-lg font-medium text-gray-900">
            {{ invitation?.expired ? $t('pages.invitation.expired_title') : $t('pages.invitation.invalid_title') }}
          </h3>
          <p class="mt-2 text-sm text-gray-500">
            {{ invitation?.expired ? $t('pages.invitation.expired_message') : $t('pages.invitation.invalid_message') }}
          </p>
          <div class="mt-6">
            <router-link
              to="/login"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700"
            >
              {{ $t('pages.invitation.go_to_login') }}
            </router-link>
          </div>
        </div>

        <!-- Valid Invitation -->
        <div v-else>
          <!-- Invitation Details -->
          <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <p class="text-sm text-gray-600">
              <span class="font-medium">{{ invitation.inviter.name }}</span>
              {{ $t('pages.invitation.invited_you_to') }}
              <span class="font-medium">{{ invitation.tenant.name }}</span>
              {{ $t('pages.invitation.as') }}
              <span class="font-medium" :class="invitation.role === 'admin' ? 'text-primary-600' : 'text-gray-700'">
                {{ $t(`roles.${invitation.role}`) }}
              </span>
            </p>
          </div>

          <!-- Existing User - Just Accept -->
          <div v-if="invitation.existing_user">
            <p class="text-sm text-gray-600 mb-4">
              {{ $t('pages.invitation.existing_user_message') }}
            </p>
            <button
              @click="acceptInvitation"
              :disabled="accepting"
              class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
            >
              <svg v-if="accepting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ accepting ? $t('pages.invitation.accepting') : $t('pages.invitation.accept') }}
            </button>
          </div>

          <!-- New User - Create Account -->
          <form v-else @submit.prevent="acceptInvitation" class="space-y-4">
            <p class="text-sm text-gray-600 mb-4">
              {{ $t('pages.invitation.new_user_message') }}
            </p>

            <!-- Email (Read-only) -->
            <div>
              <label class="block text-sm font-medium text-gray-700">{{ $t('labels.email') }}</label>
              <input
                type="email"
                :value="invitation.email"
                disabled
                class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm"
              />
            </div>

            <!-- Name -->
            <div>
              <label for="name" class="block text-sm font-medium text-gray-700">
                {{ $t('labels.name') }} <span class="text-red-500">*</span>
              </label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                :class="{ 'border-red-300': errors.name }"
              />
              <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
            </div>

            <!-- Password -->
            <div>
              <label for="password" class="block text-sm font-medium text-gray-700">
                {{ $t('labels.password') }} <span class="text-red-500">*</span>
              </label>
              <input
                id="password"
                v-model="form.password"
                type="password"
                required
                minlength="8"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                :class="{ 'border-red-300': errors.password }"
              />
              <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password }}</p>
            </div>

            <!-- Confirm Password -->
            <div>
              <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                {{ $t('labels.confirm_password') }} <span class="text-red-500">*</span>
              </label>
              <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                required
                minlength="8"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                :class="{ 'border-red-300': passwordMismatch }"
              />
              <p v-if="passwordMismatch" class="mt-1 text-sm text-red-600">{{ $t('pages.invitation.passwords_mismatch') }}</p>
            </div>

            <!-- Error Message -->
            <div v-if="error" class="p-3 rounded-md bg-red-50 border border-red-200">
              <p class="text-sm text-red-600">{{ error }}</p>
            </div>

            <!-- Submit Button -->
            <button
              type="submit"
              :disabled="accepting || !isFormValid"
              class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
            >
              <svg v-if="accepting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ accepting ? $t('pages.invitation.creating_account') : $t('pages.invitation.create_account_and_join') }}
            </button>
          </form>
        </div>

        <!-- Success State -->
        <div v-if="success" class="text-center py-8">
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
            <CheckIcon class="h-6 w-6 text-green-600" />
          </div>
          <h3 class="mt-4 text-lg font-medium text-gray-900">{{ $t('pages.invitation.success_title') }}</h3>
          <p class="mt-2 text-sm text-gray-500">
            {{ $t('pages.invitation.success_message', { tenant: successTenant }) }}
          </p>
          <div class="mt-6">
            <router-link
              to="/login"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700"
            >
              {{ $t('pages.invitation.go_to_login') }}
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ExclamationTriangleIcon, CheckIcon } from '@heroicons/vue/24/outline'
import type { InvitationVerification, AcceptInvitationForm } from '@/types/user'

const route = useRoute()
const router = useRouter()
const logoUrl = '/logo.svg'

const token = computed(() => route.params.token as string)

const loading = ref(true)
const accepting = ref(false)
const success = ref(false)
const successTenant = ref('')
const invitation = ref<InvitationVerification | null>(null)
const error = ref('')
const errors = ref<Record<string, string>>({})

const form = ref<AcceptInvitationForm>({
  name: '',
  password: '',
  password_confirmation: '',
})

const pageTitle = computed(() => {
  if (loading.value) return 'Verifying Invitation...'
  if (!invitation.value || !invitation.value.valid) return 'Invalid Invitation'
  if (success.value) return 'Welcome!'
  return invitation.value.existing_user ? 'Join Organization' : 'Create Your Account'
})

const passwordMismatch = computed(() => {
  return form.value.password && form.value.password_confirmation &&
         form.value.password !== form.value.password_confirmation
})

const isFormValid = computed(() => {
  if (invitation.value?.existing_user) return true
  return form.value.name &&
         form.value.password &&
         form.value.password.length >= 8 &&
         form.value.password === form.value.password_confirmation
})

const verifyInvitation = async () => {
  loading.value = true
  try {
    const response = await window.axios.get(`/api/invitations/${token.value}/verify`)
    invitation.value = response.data
  } catch (err: any) {
    if (err.response?.status === 404) {
      invitation.value = {
        valid: false,
        expired: false,
        accepted: false,
        email: '',
        role: 'viewer',
        tenant: { id: 0, name: '' },
        inviter: { name: '' },
        existing_user: false,
        expires_at: '',
      }
    } else {
      console.error('Error verifying invitation:', err)
      error.value = 'Failed to verify invitation'
    }
  } finally {
    loading.value = false
  }
}

const acceptInvitation = async () => {
  error.value = ''
  errors.value = {}
  accepting.value = true

  try {
    const data: AcceptInvitationForm = {}
    if (!invitation.value?.existing_user) {
      data.name = form.value.name
      data.password = form.value.password
      data.password_confirmation = form.value.password_confirmation
    }

    const response = await window.axios.post(`/api/invitations/${token.value}/accept`, data)

    success.value = true
    successTenant.value = response.data.tenant.name

    // Hide the form
    invitation.value = null
  } catch (err: any) {
    if (err.response?.status === 422 && err.response.data.errors) {
      errors.value = Object.fromEntries(
        Object.entries(err.response.data.errors).map(([key, value]) => [key, (value as string[])[0]])
      )
    } else if (err.response?.status === 410) {
      // Invitation expired or already used
      invitation.value = {
        valid: false,
        expired: true,
        accepted: err.response.data.message?.includes('already'),
        email: invitation.value?.email || '',
        role: 'viewer',
        tenant: invitation.value?.tenant || { id: 0, name: '' },
        inviter: invitation.value?.inviter || { name: '' },
        existing_user: false,
        expires_at: '',
      }
    } else {
      error.value = err.response?.data?.message || 'Failed to accept invitation'
    }
  } finally {
    accepting.value = false
  }
}

onMounted(() => {
  verifyInvitation()
})
</script>
