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
                {{ $t('pages.admin_users.edit_user') }}
              </DialogTitle>

              <form @submit.prevent="handleSubmit" class="mt-4 space-y-4">
                <!-- Name -->
                <div>
                  <label for="edit-name" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ $t('labels.name') }}
                  </label>
                  <input
                    id="edit-name"
                    v-model="form.name"
                    type="text"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    :class="{ 'border-red-300': errors.name }"
                  />
                  <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
                </div>

                <!-- Email -->
                <div>
                  <label for="edit-email" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ $t('labels.email') }}
                  </label>
                  <input
                    id="edit-email"
                    v-model="form.email"
                    type="email"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    :class="{ 'border-red-300': errors.email }"
                  />
                  <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
                </div>

                <!-- Password (Optional) -->
                <div>
                  <label for="edit-password" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ $t('labels.new_password') }}
                  </label>
                  <input
                    id="edit-password"
                    v-model="form.password"
                    type="password"
                    :placeholder="$t('pages.admin_users.password_placeholder')"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    :class="{ 'border-red-300': errors.password }"
                  />
                  <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password }}</p>
                  <p class="mt-1 text-xs text-gray-500">{{ $t('pages.admin_users.password_hint') }}</p>
                </div>

                <!-- Error Message -->
                <div v-if="error" class="p-3 rounded-md bg-red-50 border border-red-200">
                  <p class="text-sm text-red-600">{{ error }}</p>
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3 pt-4">
                  <button
                    type="button"
                    @click="handleClose"
                    :disabled="loading"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50"
                  >
                    {{ $t('common.cancel') }}
                  </button>
                  <button
                    type="submit"
                    :disabled="loading"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700 disabled:opacity-50"
                  >
                    <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ $t('common.save_changes') }}
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
import { ref, watch } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import type { AdminUser, UpdateUserForm } from '@/types/user'

interface Props {
  open: boolean
  user: AdminUser | null
}

interface Emits {
  (e: 'close'): void
  (e: 'updated'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const form = ref<UpdateUserForm>({
  name: '',
  email: '',
  password: '',
})

const errors = ref<Record<string, string>>({})
const error = ref('')
const loading = ref(false)

const resetForm = () => {
  form.value = {
    name: props.user?.name || '',
    email: props.user?.email || '',
    password: '',
  }
  errors.value = {}
  error.value = ''
}

const handleClose = () => {
  if (!loading.value) {
    emit('close')
  }
}

const handleSubmit = async () => {
  if (!props.user) return

  errors.value = {}
  error.value = ''
  loading.value = true

  try {
    // Only send fields that have values
    const data: UpdateUserForm = {}
    if (form.value.name) data.name = form.value.name
    if (form.value.email) data.email = form.value.email
    if (form.value.password) data.password = form.value.password

    await window.axios.put(`/api/admin/users/${props.user.id}`, data)
    emit('updated')
  } catch (err: any) {
    if (err.response?.status === 422 && err.response.data.errors) {
      errors.value = Object.fromEntries(
        Object.entries(err.response.data.errors).map(([key, value]) => [key, (value as string[])[0]])
      )
    } else {
      error.value = err.response?.data?.message || 'Failed to update user'
    }
  } finally {
    loading.value = false
  }
}

watch(() => props.open, (newVal) => {
  if (newVal) {
    resetForm()
  }
})

watch(() => props.user, () => {
  if (props.open) {
    resetForm()
  }
})
</script>
