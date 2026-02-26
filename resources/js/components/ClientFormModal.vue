<template>
  <TransitionRoot appear :show="open" as="template">
    <Dialog as="div" @close="handleClose" :initialFocus="nameInputRef" class="relative z-50">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-xl bg-white shadow-2xl transition-all">
              <!-- Header -->
              <div class="px-6 pt-6 pb-4">
                <div class="flex items-center gap-3">
                  <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-100">
                    <BuildingOffice2Icon class="h-5 w-5 text-primary-600" />
                  </div>
                  <div>
                    <DialogTitle as="h3" class="text-lg font-semibold text-gray-900">
                      {{ isEdit ? $t('modals.edit_client') : $t('modals.create_client') }}
                    </DialogTitle>
                    <p class="text-sm text-gray-500">Add a new client to organize your ad accounts</p>
                  </div>
                </div>
              </div>

              <!-- Form -->
              <form @submit.prevent="handleSubmit">
                <div class="px-6 pb-4 space-y-4">
                  <!-- Client Name -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                      {{ $t('labels.company_name') }}
                    </label>
                    <input
                      ref="nameInputRef"
                      v-model="formData.name"
                      type="text"
                      required
                      class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                      :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.name }"
                      :placeholder="$t('placeholders.enter_company_name')"
                    />
                    <p v-if="errors.name" class="mt-1.5 text-sm text-red-600">{{ errors.name }}</p>
                  </div>

                  <!-- Contact Email (Optional) -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                      {{ $t('labels.contact_email') }}
                      <span class="font-normal text-gray-400 ml-1">(optional)</span>
                    </label>
                    <input
                      v-model="formData.contact_email"
                      type="email"
                      class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                      :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.contact_email }"
                      :placeholder="$t('placeholders.contact_email')"
                    />
                    <p v-if="errors.contact_email" class="mt-1.5 text-sm text-red-600">{{ errors.contact_email }}</p>
                  </div>

                  <!-- Country -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                      {{ $t('labels.country') }}
                      <span class="font-normal text-gray-400 ml-1">(optional)</span>
                    </label>
                    <select
                      v-model="formData.country"
                      class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                    >
                      <option value="">{{ $t('filters.all_countries') }}</option>
                      <option v-for="c in countries" :key="c.code" :value="c.code">
                        {{ c.name }}
                      </option>
                    </select>
                  </div>

                  <!-- Error Message -->
                  <div v-if="submitError" class="rounded-lg bg-red-50 p-3 flex items-start gap-2">
                    <ExclamationCircleIcon class="h-5 w-5 text-red-400 flex-shrink-0 mt-0.5" />
                    <p class="text-sm text-red-700">{{ submitError }}</p>
                  </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-gray-50 flex items-center justify-end gap-3">
                  <button
                    type="button"
                    @click="handleClose"
                    :disabled="loading"
                    class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 rounded-lg disabled:opacity-50 transition-colors"
                  >
                    {{ $t('common.cancel') }}
                  </button>
                  <button
                    type="submit"
                    :disabled="loading"
                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 transition-colors min-w-[100px]"
                  >
                    <svg v-if="loading" class="animate-spin -ml-0.5 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ loading ? $t('common.saving') : (isEdit ? $t('common.update') : $t('common.create')) }}
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
import { ref, watch, computed } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { BuildingOffice2Icon, ExclamationCircleIcon } from '@heroicons/vue/24/outline'
import { countries } from '@/utils/countries'
import type { Client } from '@/types/client'

interface Props {
  open: boolean
  client?: Client | null
}

interface Emits {
  (e: 'close'): void
  (e: 'saved', client: Client): void
}

const props = withDefaults(defineProps<Props>(), {
  client: null
})

const emit = defineEmits<Emits>()

const isEdit = computed(() => !!props.client)
const loading = ref(false)
const submitError = ref('')
const errors = ref<Record<string, string>>({})
const nameInputRef = ref<HTMLInputElement | null>(null)

const formData = ref({
  name: '',
  status: 'active',
  contact_email: '',
  country: ''
})

// Watch for client prop changes to populate form
watch(() => props.client, (client) => {
  if (client) {
    formData.value = {
      name: client.name || '',
      status: client.status || 'active',
      contact_email: client.contact_email || '',
      country: client.country || ''
    }
  } else {
    formData.value = {
      name: '',
      status: 'active',
      contact_email: '',
      country: ''
    }
  }
  errors.value = {}
  submitError.value = ''
}, { immediate: true })

const validateForm = (): boolean => {
  errors.value = {}
  let isValid = true

  if (!formData.value.name.trim()) {
    errors.value.name = 'Client name is required'
    isValid = false
  }

  if (formData.value.contact_email && !isValidEmail(formData.value.contact_email)) {
    errors.value.contact_email = 'Please enter a valid email address'
    isValid = false
  }

  return isValid
}

const isValidEmail = (email: string): boolean => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

const handleSubmit = async () => {
  if (!validateForm()) {
    return
  }

  loading.value = true
  submitError.value = ''

  try {
    const payload = {
      name: formData.value.name,
      status: formData.value.status,
      contact_email: formData.value.contact_email || undefined,
      country: formData.value.country || undefined
    }

    let response
    if (isEdit.value && props.client) {
      response = await window.axios.put(`/api/clients/${props.client.id}`, payload)
    } else {
      response = await window.axios.post('/api/clients', payload)
    }

    emit('saved', response.data.data || response.data)
    emit('close')  // Emit close directly instead of calling handleClose() which checks loading state
  } catch (error: any) {
    console.error('Error saving client:', error)

    if (error.response?.data?.errors) {
      const backendErrors = error.response.data.errors
      Object.keys(backendErrors).forEach(key => {
        errors.value[key] = backendErrors[key][0]
      })
      submitError.value = 'Please correct the errors below'
    } else {
      submitError.value = error.response?.data?.message || 'An error occurred while saving the client'
    }
  } finally {
    loading.value = false
  }
}

const handleClose = () => {
  if (!loading.value) {
    emit('close')
  }
}
</script>
