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
            <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
              <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                {{ $t('pages.users.invite_user') }}
              </DialogTitle>

              <form @submit.prevent="handleSubmit" class="mt-4">
                <!-- Email -->
                <div class="mb-4">
                  <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ $t('labels.email') }} <span class="text-red-500">*</span>
                  </label>
                  <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    :placeholder="$t('placeholders.invite_email')"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    :class="{ 'border-red-300': errors.email }"
                  />
                  <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
                </div>

                <!-- Role -->
                <div class="mb-6">
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ $t('labels.role') }} <span class="text-red-500">*</span>
                  </label>
                  <div class="space-y-2">
                    <label
                      class="flex items-start p-3 border rounded-lg cursor-pointer transition-colors"
                      :class="form.role === 'viewer' ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:bg-gray-50'"
                    >
                      <input
                        type="radio"
                        v-model="form.role"
                        value="viewer"
                        class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 mt-0.5"
                      />
                      <div class="ml-3">
                        <span class="block text-sm font-medium text-gray-900">{{ $t('roles.viewer') }}</span>
                        <span class="block text-sm text-gray-500">{{ $t('pages.users.role_descriptions.viewer') }}</span>
                      </div>
                    </label>
                    <label
                      class="flex items-start p-3 border rounded-lg cursor-pointer transition-colors"
                      :class="form.role === 'admin' ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:bg-gray-50'"
                    >
                      <input
                        type="radio"
                        v-model="form.role"
                        value="admin"
                        class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 mt-0.5"
                      />
                      <div class="ml-3">
                        <span class="block text-sm font-medium text-gray-900">{{ $t('roles.admin') }}</span>
                        <span class="block text-sm text-gray-500">{{ $t('pages.users.role_descriptions.admin') }}</span>
                      </div>
                    </label>
                  </div>
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
                    {{ loading ? $t('common.sending') : $t('pages.users.send_invitation') }}
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
import type { InviteUserForm, UserRole } from '@/types/user'

interface Props {
  open: boolean
}

interface Emits {
  (e: 'close'): void
  (e: 'invited'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const authStore = useAuthStore()

const form = ref<InviteUserForm>({
  email: '',
  role: 'viewer',
})

const errors = ref<Record<string, string>>({})
const error = ref('')
const loading = ref(false)

const isValid = computed(() => {
  return form.value.email && form.value.email.includes('@') && form.value.role
})

const resetForm = () => {
  form.value = {
    email: '',
    role: 'viewer',
  }
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
  if (!authStore.currentTenant?.id) return

  errors.value = {}
  error.value = ''
  loading.value = true

  try {
    await window.axios.post(
      `/api/tenants/${authStore.currentTenant.id}/users/invite`,
      form.value
    )
    resetForm()
    emit('invited')
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
      error.value = err.response?.data?.message || 'Failed to send invitation'
    }
  } finally {
    loading.value = false
  }
}

// Reset form when modal opens
watch(() => props.open, (newVal) => {
  if (newVal) {
    resetForm()
  }
})
</script>
