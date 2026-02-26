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
                {{ $t('pages.admin_users.create_user') }}
              </DialogTitle>

              <form @submit.prevent="handleSubmit" class="mt-4 space-y-4">
                <!-- Name -->
                <div>
                  <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ $t('labels.name') }} <span class="text-red-500">*</span>
                  </label>
                  <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    :class="{ 'border-red-300': errors.name }"
                  />
                  <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
                </div>

                <!-- Email -->
                <div>
                  <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ $t('labels.email') }} <span class="text-red-500">*</span>
                  </label>
                  <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    :class="{ 'border-red-300': errors.email }"
                  />
                  <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
                </div>

                <!-- Password -->
                <div>
                  <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ $t('labels.password') }} <span class="text-red-500">*</span>
                  </label>
                  <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    :class="{ 'border-red-300': errors.password }"
                  />
                  <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password }}</p>
                </div>

                <!-- Tenant (Optional) -->
                <div>
                  <label for="tenant" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ $t('labels.tenant') }}
                  </label>
                  <select
                    id="tenant"
                    v-model="form.tenant_id"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                  >
                    <option :value="null">{{ $t('pages.admin_users.no_tenant') }}</option>
                    <option v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">
                      {{ tenant.name }}
                    </option>
                  </select>
                </div>

                <!-- Role (if tenant selected) -->
                <div v-if="form.tenant_id">
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    {{ $t('labels.role') }}
                  </label>
                  <div class="flex space-x-4">
                    <label class="flex items-center">
                      <input
                        type="radio"
                        v-model="form.role"
                        value="viewer"
                        class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300"
                      />
                      <span class="ml-2 text-sm text-gray-700">{{ $t('roles.viewer') }}</span>
                    </label>
                    <label class="flex items-center">
                      <input
                        type="radio"
                        v-model="form.role"
                        value="admin"
                        class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300"
                      />
                      <span class="ml-2 text-sm text-gray-700">{{ $t('roles.admin') }}</span>
                    </label>
                  </div>
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
                    :disabled="loading || !isValid"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700 disabled:opacity-50"
                  >
                    <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ $t('common.create') }}
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
import type { CreateUserForm, TenantOption } from '@/types/user'

interface Props {
  open: boolean
  tenants: TenantOption[]
}

interface Emits {
  (e: 'close'): void
  (e: 'created'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const form = ref<CreateUserForm>({
  name: '',
  email: '',
  password: '',
  tenant_id: null,
  role: 'viewer',
})

const errors = ref<Record<string, string>>({})
const error = ref('')
const loading = ref(false)

const isValid = computed(() => {
  return form.value.name && form.value.email && form.value.password
})

const resetForm = () => {
  form.value = {
    name: '',
    email: '',
    password: '',
    tenant_id: null,
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
  errors.value = {}
  error.value = ''
  loading.value = true

  try {
    await window.axios.post('/api/admin/users', form.value)
    resetForm()
    emit('created')
  } catch (err: any) {
    if (err.response?.status === 422 && err.response.data.errors) {
      errors.value = Object.fromEntries(
        Object.entries(err.response.data.errors).map(([key, value]) => [key, (value as string[])[0]])
      )
    } else {
      error.value = err.response?.data?.message || 'Failed to create user'
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
</script>
