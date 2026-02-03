<template>
  <TransitionRoot as="template" :show="show">
    <Dialog as="div" class="relative z-10" @close="close">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">
              <div>
                <div class="flex items-center justify-between mb-4">
                  <div>
                    <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                      Import Historical Data
                    </DialogTitle>
                    <p class="mt-1 text-sm text-gray-500">
                      Upload {{ platformName }} CSV exports to recover historical advertising data
                    </p>
                  </div>
                  <button
                    type="button"
                    class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none"
                    @click="close"
                  >
                    <XMarkIcon class="h-6 w-6" />
                  </button>
                </div>

                <!-- Step 1: File Upload -->
                <div v-if="!previewData && !importing" class="mt-5">
                  <div
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="handleDrop"
                    :class="[
                      'mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-dashed rounded-md transition-colors',
                      isDragging ? 'border-primary-500 bg-primary-50' : 'border-gray-300'
                    ]"
                  >
                    <div class="space-y-1 text-center">
                      <DocumentArrowUpIcon class="mx-auto h-12 w-12 text-gray-400" />
                      <div class="flex text-sm text-gray-600">
                        <label
                          for="file-upload"
                          class="relative cursor-pointer bg-white rounded-md font-medium text-primary-600 hover:text-primary-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500"
                        >
                          <span>Upload a file</span>
                          <input
                            id="file-upload"
                            name="file-upload"
                            type="file"
                            accept=".csv,.txt"
                            class="sr-only"
                            @change="handleFileSelect"
                          />
                        </label>
                        <p class="pl-1">or drag and drop</p>
                      </div>
                      <p class="text-xs text-gray-500">
                        CSV files up to 50MB
                      </p>
                    </div>
                  </div>

                  <!-- Selected File Info -->
                  <div v-if="selectedFile" class="mt-4 flex items-center justify-between p-3 bg-gray-50 rounded-md">
                    <div class="flex items-center">
                      <DocumentIcon class="h-5 w-5 text-gray-400 mr-2" />
                      <span class="text-sm text-gray-900">{{ selectedFile.name }}</span>
                      <span class="text-xs text-gray-500 ml-2">({{ formatFileSize(selectedFile.size) }})</span>
                    </div>
                    <button @click="selectedFile = null" class="text-gray-400 hover:text-gray-500">
                      <XMarkIcon class="h-5 w-5" />
                    </button>
                  </div>

                  <!-- Account ID Input -->
                  <div v-if="selectedFile" class="mt-4">
                    <label for="account-id" class="block text-sm font-medium text-gray-700">
                      {{ accountIdLabel }}
                    </label>
                    <input
                      v-model="accountId"
                      type="text"
                      id="account-id"
                      :placeholder="accountIdPlaceholder"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                    />
                    <p class="mt-1 text-xs text-gray-500">
                      {{ accountIdHint }}
                    </p>
                  </div>

                  <!-- Action Buttons -->
                  <div v-if="selectedFile" class="mt-6 flex space-x-3">
                    <button
                      @click="previewFile"
                      :disabled="!accountId || previewing"
                      class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
                    >
                      <span v-if="previewing" class="animate-spin rounded-full h-4 w-4 border-b-2 border-gray-900 mr-2"></span>
                      Preview File
                    </button>
                    <button
                      @click="startImport"
                      :disabled="!accountId || importing"
                      class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
                    >
                      <span v-if="importing" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>
                      Import Now
                    </button>
                  </div>
                </div>

                <!-- Step 2: Preview -->
                <div v-if="previewData && !importing" class="mt-5">
                  <div class="mb-4 p-4 bg-blue-50 rounded-md">
                    <div class="flex">
                      <div class="flex-shrink-0">
                        <InformationCircleIcon class="h-5 w-5 text-blue-400" />
                      </div>
                      <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">
                          File Preview
                        </h3>
                        <div class="mt-2 text-sm text-blue-700">
                          <p><strong>Format:</strong> {{ previewData.detected_format }}</p>
                          <p><strong>Total Rows:</strong> {{ previewData.total_rows.toLocaleString() }}</p>
                          <p><strong>Columns:</strong> {{ previewData.headers.length }}</p>
                        </div>
                        <div class="mt-2">
                          <p class="text-xs text-blue-600" v-for="rec in previewData.recommendations" :key="rec">
                            {{ rec }}
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Preview Table -->
                  <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                      <thead class="bg-gray-50">
                        <tr>
                          <th v-for="header in previewData.headers.slice(0, 6)" :key="header"
                              class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ header }}
                          </th>
                        </tr>
                      </thead>
                      <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="(row, idx) in previewData.preview.slice(0, 5)" :key="idx">
                          <td v-for="header in previewData.headers.slice(0, 6)" :key="header"
                              class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                            {{ row[header] }}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <div class="mt-6 flex space-x-3">
                    <button
                      @click="previewData = null"
                      class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                    >
                      Back
                    </button>
                    <button
                      @click="startImport"
                      :disabled="importing"
                      class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
                    >
                      <span v-if="importing" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>
                      Proceed with Import
                    </button>
                  </div>
                </div>

                <!-- Step 3: Importing Progress -->
                <div v-if="importing" class="mt-5">
                  <div class="text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto"></div>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">Importing Data...</h3>
                    <p class="mt-2 text-sm text-gray-500">This may take a few minutes depending on file size</p>
                  </div>
                </div>

                <!-- Step 4: Results -->
                <div v-if="importResults" class="mt-5">
                  <div :class="[
                    'p-4 rounded-md',
                    importResults.success ? 'bg-green-50' : 'bg-red-50'
                  ]">
                    <div class="flex">
                      <div class="flex-shrink-0">
                        <CheckCircleIcon v-if="importResults.success" class="h-5 w-5 text-green-400" />
                        <XCircleIcon v-else class="h-5 w-5 text-red-400" />
                      </div>
                      <div class="ml-3">
                        <h3 :class="[
                          'text-sm font-medium',
                          importResults.success ? 'text-green-800' : 'text-red-800'
                        ]">
                          {{ importResults.message }}
                        </h3>
                        <div v-if="importResults.stats" class="mt-2 text-sm" :class="importResults.success ? 'text-green-700' : 'text-red-700'">
                          <p><strong>Rows Processed:</strong> {{ importResults.stats.rows_processed.toLocaleString() }}</p>
                          <p><strong>Campaigns Created:</strong> {{ importResults.stats.campaigns_created }}</p>
                          <p><strong>Metrics Created:</strong> {{ importResults.stats.metrics_created.toLocaleString() }}</p>
                          <p><strong>Metrics Updated:</strong> {{ importResults.stats.metrics_updated.toLocaleString() }}</p>
                          <p v-if="importResults.stats.errors > 0" class="text-red-600">
                            <strong>Errors:</strong> {{ importResults.stats.errors }}
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="mt-6">
                    <button
                      @click="reset"
                      class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    >
                      Import Another File
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
import { ref, computed } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import {
  XMarkIcon,
  DocumentArrowUpIcon,
  DocumentIcon,
  InformationCircleIcon,
  CheckCircleIcon,
  XCircleIcon
} from '@heroicons/vue/24/outline'
import axios from 'axios'

interface Props {
  show: boolean
  platform?: 'facebook' | 'google'
}

const props = withDefaults(defineProps<Props>(), {
  platform: 'facebook'
})
const emit = defineEmits<{
  (e: 'close'): void
  (e: 'success'): void
}>()

const selectedFile = ref<File | null>(null)
const accountId = ref('')
const isDragging = ref(false)
const previewing = ref(false)
const importing = ref(false)
const previewData = ref<any>(null)
const importResults = ref<any>(null)

// Platform-specific text
const platformName = computed(() => {
  return props.platform === 'google' ? 'Google Ads' : 'Facebook'
})

const accountIdLabel = computed(() => {
  return props.platform === 'google' ? 'Google Ads Customer ID' : 'Facebook Ad Account ID'
})

const accountIdPlaceholder = computed(() => {
  return props.platform === 'google' ? '123-456-7890' : 'act_123456789'
})

const accountIdHint = computed(() => {
  return props.platform === 'google'
    ? 'Find this in Google Ads → Account Settings (e.g., 819-554-9637)'
    : 'Find this in Facebook Ads Manager → Ad Account Settings'
})

const importEndpoint = computed(() => {
  return props.platform === 'google' ? '/api/csv-import/google-ads' : '/api/csv-import/facebook'
})

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    selectedFile.value = target.files[0]
  }
}

const handleDrop = (event: DragEvent) => {
  isDragging.value = false
  if (event.dataTransfer?.files && event.dataTransfer.files[0]) {
    selectedFile.value = event.dataTransfer.files[0]
  }
}

const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

const previewFile = async () => {
  if (!selectedFile.value) return

  previewing.value = true
  const formData = new FormData()
  formData.append('file', selectedFile.value)

  try {
    const response = await axios.post('/api/csv-import/preview', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    previewData.value = response.data
  } catch (error: any) {
    alert('Preview failed: ' + (error.response?.data?.message || error.message))
  } finally {
    previewing.value = false
  }
}

const startImport = async () => {
  if (!selectedFile.value || !accountId.value) return

  importing.value = true
  const formData = new FormData()
  formData.append('file', selectedFile.value)

  // Use the correct field name based on platform
  const accountFieldName = props.platform === 'google' ? 'customer_id' : 'account_id'
  formData.append(accountFieldName, accountId.value)

  try {
    const response = await axios.post(importEndpoint.value, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      timeout: 300000 // 5 minutes
    })
    importResults.value = response.data
    emit('success')
  } catch (error: any) {
    importResults.value = {
      success: false,
      message: error.response?.data?.message || error.message
    }
  } finally {
    importing.value = false
  }
}

const reset = () => {
  selectedFile.value = null
  accountId.value = ''
  previewData.value = null
  importResults.value = null
  isDragging.value = false
}

const close = () => {
  if (!importing.value) {
    reset()
    emit('close')
  }
}
</script>