<template>
  <TransitionRoot
    as="template"
    :show="show"
    enter="transform ease-out duration-300 transition"
    enter-from="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
    enter-to="translate-y-0 opacity-100 sm:translate-x-0"
    leave="transition ease-in duration-100"
    leave-from="opacity-100"
    leave-to="opacity-0"
  >
    <div class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5">
      <div class="p-4">
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <CheckCircleIcon v-if="type === 'success'" class="h-6 w-6 text-green-400" aria-hidden="true" />
            <XCircleIcon v-else-if="type === 'error'" class="h-6 w-6 text-red-400" aria-hidden="true" />
            <ExclamationTriangleIcon v-else-if="type === 'warning'" class="h-6 w-6 text-yellow-400" aria-hidden="true" />
            <InformationCircleIcon v-else class="h-6 w-6 text-blue-400" aria-hidden="true" />
          </div>
          <div class="ml-3 w-0 flex-1 pt-0.5">
            <p class="text-sm font-medium text-gray-900">{{ title }}</p>
            <p v-if="message" class="mt-1 text-sm text-gray-500">{{ message }}</p>
          </div>
          <div class="ml-4 flex flex-shrink-0">
            <button
              type="button"
              @click="$emit('close')"
              class="inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            >
              <span class="sr-only">Close</span>
              <XMarkIcon class="h-5 w-5" aria-hidden="true" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </TransitionRoot>
</template>

<script setup lang="ts">
import { TransitionRoot } from '@headlessui/vue'
import { 
  CheckCircleIcon, 
  XCircleIcon, 
  ExclamationTriangleIcon, 
  InformationCircleIcon,
  XMarkIcon 
} from '@heroicons/vue/24/outline'

interface Props {
  show: boolean
  type: 'success' | 'error' | 'warning' | 'info'
  title: string
  message?: string
}

defineProps<Props>()
defineEmits(['close'])
</script>