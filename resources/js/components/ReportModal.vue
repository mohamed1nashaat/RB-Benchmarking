<template>
  <TransitionRoot as="template" :show="true">
    <Dialog as="div" class="relative z-50" @close="$emit('close')">
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
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl sm:p-6">
              <div>
                <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                  {{ isEdit ? $t('modals.edit_report') : $t('modals.create_report') }}
                </DialogTitle>

                <form @submit.prevent="saveReport" class="mt-6 space-y-6">
                  <!-- Report Name & Description -->
                  <div class="grid grid-cols-1 gap-4">
                    <div>
                      <label for="name" class="block text-sm font-medium text-gray-700">
                        {{ $t('labels.report_name') }} <span class="text-red-500">{{ $t('messages.required_field') }}</span>
                      </label>
                      <input
                        v-model="form.name"
                        type="text"
                        id="name"
                        required
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                        :placeholder="$t('placeholders.report_name')"
                      />
                    </div>

                    <div>
                      <label for="description" class="block text-sm font-medium text-gray-700">
                        {{ $t('placeholders.description_optional') }}
                      </label>
                      <textarea
                        v-model="form.description"
                        id="description"
                        rows="2"
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                        :placeholder="$t('placeholders.report_description')"
                      />
                    </div>
                  </div>

                  <!-- Report Type -->
                  <div>
                    <label for="report_type" class="block text-sm font-medium text-gray-700">
                      {{ $t('labels.report_type') }} <span class="text-red-500">{{ $t('messages.required_field') }}</span>
                    </label>
                    <select
                      v-model="form.report_type"
                      id="report_type"
                      required
                      class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
                    >
                      <option value="">{{ $t('placeholders.select_type') }}</option>
                      <option value="performance">{{ $t('report_types.performance') }}</option>
                      <option value="benchmark">{{ $t('report_types.benchmark') }}</option>
                      <option value="campaign">{{ $t('report_types.campaign') }}</option>
                      <option value="account">{{ $t('report_types.account') }}</option>
                      <option value="industry">{{ $t('report_types.industry') }}</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                      {{ getReportTypeDescription(form.report_type) }}
                    </p>
                  </div>

                  <!-- Filters -->
                  <div class="space-y-4 p-4 bg-gray-50 rounded-md">
                    <h4 class="text-sm font-medium text-gray-900">{{ $t('reports.sections.filters') }}</h4>

                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <label class="block text-sm font-medium text-gray-700">{{ $t('labels.time_period') }}</label>
                        <select
                          v-model="form.filters.period"
                          class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
                        >
                          <option value="today">{{ $t('date.today') }}</option>
                          <option value="yesterday">{{ $t('date.yesterday') }}</option>
                          <option value="last_7_days">{{ $t('date.last_7_days') }}</option>
                          <option value="last_30_days">{{ $t('date.last_30_days') }}</option>
                          <option value="this_month">{{ $t('date.this_month') }}</option>
                          <option value="last_month">{{ $t('date.last_month') }}</option>
                        </select>
                      </div>

                      <div>
                        <label class="block text-sm font-medium text-gray-700">{{ $t('labels.objective') }} ({{ $t('placeholders.description_optional').split(' ')[0] }})</label>
                        <select
                          v-model="form.filters.objective"
                          class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
                        >
                          <option value="">{{ $t('filters.all_objectives') }}</option>
                          <option value="awareness">{{ $t('objectives.awareness') }}</option>
                          <option value="leads">{{ $t('objectives.leads') }}</option>
                          <option value="sales">{{ $t('objectives.website_sales') }}</option>
                          <option value="calls">{{ $t('objectives.messages') }}</option>
                        </select>
                      </div>
                    </div>
                  </div>

                  <!-- Schedule Configuration -->
                  <div class="space-y-4 p-4 bg-gray-50 rounded-md">
                    <h4 class="text-sm font-medium text-gray-900">{{ $t('reports.sections.schedule') }}</h4>

                    <div class="grid grid-cols-3 gap-4">
                      <div>
                        <label class="block text-sm font-medium text-gray-700">{{ $t('labels.frequency') }} <span class="text-red-500">{{ $t('messages.required_field') }}</span></label>
                        <select
                          v-model="form.frequency"
                          required
                          class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
                        >
                          <option value="daily">{{ $t('time.daily') }}</option>
                          <option value="weekly">{{ $t('time.weekly') }}</option>
                          <option value="monthly">{{ $t('time.monthly') }}</option>
                        </select>
                      </div>

                      <div v-if="form.frequency === 'weekly'">
                        <label class="block text-sm font-medium text-gray-700">{{ $t('labels.day_of_week') }} <span class="text-red-500">{{ $t('messages.required_field') }}</span></label>
                        <select
                          v-model="form.day_of_week"
                          required
                          class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
                        >
                          <option value="monday">{{ $t('time.monday') }}</option>
                          <option value="tuesday">{{ $t('time.tuesday') }}</option>
                          <option value="wednesday">{{ $t('time.wednesday') }}</option>
                          <option value="thursday">{{ $t('time.thursday') }}</option>
                          <option value="friday">{{ $t('time.friday') }}</option>
                          <option value="saturday">{{ $t('time.saturday') }}</option>
                          <option value="sunday">{{ $t('time.sunday') }}</option>
                        </select>
                      </div>

                      <div v-if="form.frequency === 'monthly'">
                        <label class="block text-sm font-medium text-gray-700">{{ $t('labels.day_of_month') }} <span class="text-red-500">{{ $t('messages.required_field') }}</span></label>
                        <input
                          v-model.number="form.day_of_month"
                          type="number"
                          min="1"
                          max="31"
                          required
                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                          :placeholder="$t('placeholders.day_of_month')"
                        />
                      </div>

                      <div>
                        <label class="block text-sm font-medium text-gray-700">{{ $t('labels.time_of_day') }} <span class="text-red-500">{{ $t('messages.required_field') }}</span></label>
                        <input
                          v-model="form.time_of_day"
                          type="time"
                          required
                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                        />
                      </div>
                    </div>
                  </div>

                  <!-- Export Configuration -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      {{ $t('labels.export_formats') }} <span class="text-red-500">{{ $t('messages.required_field') }}</span>
                    </label>
                    <div class="space-y-2">
                      <div class="flex items-center">
                        <input
                          v-model="form.export_formats"
                          value="pdf"
                          type="checkbox"
                          id="format-pdf"
                          class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                        />
                        <label for="format-pdf" class="ml-2 text-sm text-gray-700">
                          {{ $t('export_formats.pdf') }}
                        </label>
                      </div>
                      <div class="flex items-center">
                        <input
                          v-model="form.export_formats"
                          value="excel"
                          type="checkbox"
                          id="format-excel"
                          class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                        />
                        <label for="format-excel" class="ml-2 text-sm text-gray-700">
                          {{ $t('export_formats.excel') }}
                        </label>
                      </div>
                      <div class="flex items-center">
                        <input
                          v-model="form.export_formats"
                          value="csv"
                          type="checkbox"
                          id="format-csv"
                          class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                        />
                        <label for="format-csv" class="ml-2 text-sm text-gray-700">
                          {{ $t('export_formats.csv') }}
                        </label>
                      </div>
                    </div>
                  </div>

                  <!-- Recipients -->
                  <div>
                    <label for="recipients" class="block text-sm font-medium text-gray-700">
                      {{ $t('reports.labels.email_recipients') }}
                    </label>
                    <input
                      v-model="recipientsInput"
                      type="text"
                      id="recipients"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                      placeholder="email1@example.com, email2@example.com"
                    />
                    <p class="mt-1 text-xs text-gray-500">
                      {{ $t('reports.helpers.separate_emails') }}
                    </p>
                  </div>

                  <!-- Active Status -->
                  <div class="flex items-center">
                    <input
                      v-model="form.is_active"
                      type="checkbox"
                      id="is-active"
                      class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                    />
                    <label for="is-active" class="ml-2 text-sm text-gray-700">
                      {{ $t('reports.labels.activate_immediately') }}
                    </label>
                  </div>

                  <!-- Actions -->
                  <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                    <button
                      type="submit"
                      :disabled="saving"
                      class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:col-start-2 sm:text-sm disabled:opacity-50"
                    >
                      {{ saving ? $t('reports.buttons.saving') : (isEdit ? $t('reports.buttons.update_report') : $t('reports.buttons.create_report')) }}
                    </button>
                    <button
                      type="button"
                      @click="$emit('close')"
                      class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:col-start-1 sm:text-sm"
                    >
                      {{ $t('common.cancel') }}
                    </button>
                  </div>
                </form>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

interface Props {
  report?: any
}

const props = defineProps<Props>()
const emit = defineEmits(['close', 'saved'])

const isEdit = computed(() => !!props.report)
const saving = ref(false)
const recipientsInput = ref('')

const form = reactive({
  name: '',
  description: '',
  report_type: '',
  metrics: [],
  filters: {
    period: 'last_30_days',
    objective: '',
  },
  frequency: 'weekly',
  day_of_week: 'monday',
  day_of_month: 1,
  time_of_day: '09:00',
  export_formats: ['pdf'],
  recipients: [] as string[],
  is_active: true,
})

// Initialize form if editing
if (props.report) {
  form.name = props.report.name
  form.description = props.report.description || ''
  form.report_type = props.report.report_type
  form.metrics = props.report.metrics || []
  form.filters = { ...form.filters, ...(props.report.filters || {}) }
  form.frequency = props.report.frequency
  form.day_of_week = props.report.day_of_week || 'monday'
  form.day_of_month = props.report.day_of_month || 1
  form.time_of_day = props.report.time_of_day || '09:00'
  form.export_formats = props.report.export_formats || ['pdf']
  form.recipients = props.report.recipients || []
  form.is_active = props.report.is_active

  // Set recipients input
  if (form.recipients.length > 0) {
    recipientsInput.value = form.recipients.join(', ')
  }
}

// Watch recipients input and parse emails
watch(recipientsInput, (value) => {
  if (value.trim()) {
    form.recipients = value.split(',').map(email => email.trim()).filter(email => email)
  } else {
    form.recipients = []
  }
})

const saveReport = async () => {
  // Validate export formats
  if (form.export_formats.length === 0) {
    alert(t('reports.helpers.select_export_format'))
    return
  }

  saving.value = true

  try {
    const data = {
      name: form.name,
      description: form.description || null,
      report_type: form.report_type,
      metrics: form.metrics,
      filters: form.filters,
      frequency: form.frequency,
      day_of_week: form.frequency === 'weekly' ? form.day_of_week : null,
      day_of_month: form.frequency === 'monthly' ? form.day_of_month : null,
      time_of_day: form.time_of_day,
      export_formats: form.export_formats,
      recipients: form.recipients.length > 0 ? form.recipients : null,
      is_active: form.is_active,
    }

    if (isEdit.value) {
      await window.axios.put(`/api/scheduled-reports/${props.report.id}`, data)
    } else {
      await window.axios.post('/api/scheduled-reports', data)
    }

    emit('saved')
  } catch (error: any) {
    console.error('Error saving report:', error)
    alert(t('reports.helpers.failed_to_save') + (error.response?.data?.message || error.message))
  } finally {
    saving.value = false
  }
}

const getReportTypeDescription = (type: string): string => {
  const descriptions: Record<string, string> = {
    performance: t('reports.type_descriptions.performance'),
    benchmark: t('reports.type_descriptions.benchmark'),
    campaign: t('reports.type_descriptions.campaign'),
    account: t('reports.type_descriptions.account'),
    industry: t('reports.type_descriptions.industry'),
  }
  return descriptions[type] || ''
}
</script>
