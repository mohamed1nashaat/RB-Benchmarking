<template>
  <TransitionRoot appear :show="open" as="template">
    <Dialog as="div" @close="emit('close')" class="relative z-50">
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
              <div class="flex items-start">
                <div class="flex-shrink-0">
                  <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                  </div>
                </div>
                <div class="ml-4 flex-1">
                  <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                    {{ title }}
                  </DialogTitle>
                  <div class="mt-2">
                    <p class="text-sm text-gray-500">
                      {{ message }}
                    </p>

                    <!-- Warning for clients with ad accounts -->
                    <div v-if="hasAdAccounts" class="mt-3 rounded-md bg-yellow-50 p-3 border border-yellow-200">
                      <div class="flex">
                        <div class="flex-shrink-0">
                          <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                          </svg>
                        </div>
                        <div class="ml-3">
                          <p class="text-sm font-medium text-yellow-800">
                            {{ $t('messages.client_has_accounts', { count: adAccountsCount }) }}
                          </p>
                          <p class="mt-1 text-sm text-yellow-700">
                            {{ $t('messages.delete_unassign_warning') }}
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="mt-6 flex justify-end space-x-3">
                    <button
                      type="button"
                      @click="emit('close')"
                      :disabled="loading"
                      class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50"
                    >
                      {{ $t('common.cancel') }}
                    </button>
                    <button
                      type="button"
                      @click="emit('confirm')"
                      :disabled="loading"
                      class="inline-flex justify-center items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50"
                    >
                      <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      {{ loading ? $t('messages.deleting') : $t('common.delete') }}
                    </button>
                  </div>
                </div>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'

interface Props {
  open: boolean
  title: string
  message: string
  loading?: boolean
  hasAdAccounts?: boolean
  adAccountsCount?: number
}

interface Emits {
  (e: 'close'): void
  (e: 'confirm'): void
}

withDefaults(defineProps<Props>(), {
  loading: false,
  hasAdAccounts: false,
  adAccountsCount: 0
})

const emit = defineEmits<Emits>()
</script>
