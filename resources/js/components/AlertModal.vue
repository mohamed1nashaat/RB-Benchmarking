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
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">
              <div>
                <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                  {{ isEdit ? $t('modals.edit_alert') : $t('modals.create_alert') }}
                </DialogTitle>

                <form @submit.prevent="saveAlert" class="mt-6 space-y-6">
                  <!-- Alert Name -->
                  <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                      {{ $t('labels.alert_name') }} <span class="text-red-500">{{ $t('messages.required_field') }}</span>
                    </label>
                    <input
                      v-model="form.name"
                      type="text"
                      id="name"
                      required
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                      :placeholder="$t('placeholders.alert_name')"
                    />
                  </div>

                  <!-- Alert Type -->
                  <div>
                    <label for="type" class="block text-sm font-medium text-gray-700">
                      {{ $t('labels.alert_type') }} <span class="text-red-500">{{ $t('messages.required_field') }}</span>
                    </label>
                    <select
                      v-model="form.type"
                      id="type"
                      required
                      @change="onTypeChange"
                      class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
                    >
                      <option value="">{{ $t('placeholders.select_type') }}</option>
                      <option value="threshold">{{ $t('alert_types.threshold') }}</option>
                      <option value="budget">{{ $t('alert_types.budget') }}</option>
                      <option value="anomaly">{{ $t('alert_types.anomaly') }}</option>
                    </select>
                  </div>

                  <!-- Objective (Optional) -->
                  <div>
                    <label for="objective" class="block text-sm font-medium text-gray-700">
                      {{ $t('alerts.labels.campaign_objective_optional') }}
                    </label>
                    <select
                      v-model="form.objective"
                      id="objective"
                      class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
                    >
                      <option value="">{{ $t('filters.all_objectives') }}</option>
                      <option value="awareness">{{ $t('objectives.awareness') }}</option>
                      <option value="leads">{{ $t('objectives.leads') }}</option>
                      <option value="sales">{{ $t('objectives.website_sales') }}</option>
                      <option value="calls">{{ $t('objectives.messages') }}</option>
                    </select>
                    <p class="mt-1 text-sm text-gray-500">
                      {{ $t('alerts.helpers.filter_by_objective') }}
                    </p>
                  </div>

                  <!-- Threshold Alert Conditions -->
                  <div v-if="form.type === 'threshold'" class="space-y-4 p-4 bg-gray-50 rounded-md">
                    <h4 class="text-sm font-medium text-gray-900">{{ $t('alerts.sections.threshold_conditions') }}</h4>

                    <div class="grid grid-cols-3 gap-4">
                      <div>
                        <label class="block text-sm font-medium text-gray-700">{{ $t('labels.metric') }} <span class="text-red-500">{{ $t('messages.required_field') }}</span></label>
                        <select
                          v-model="form.conditions.metric"
                          required
                          class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
                        >
                          <option value="">{{ $t('placeholders.select_option') }}</option>
                          <option value="spend">{{ $t('alerts.metrics.spend') }}</option>
                          <option value="cpl">{{ $t('alerts.metrics.cpl_full') }}</option>
                          <option value="cpc">{{ $t('alerts.metrics.cpc_full') }}</option>
                          <option value="cpm">{{ $t('alerts.metrics.cpm_full') }}</option>
                          <option value="cpa">{{ $t('alerts.metrics.cpa_full') }}</option>
                          <option value="roas">{{ $t('alerts.metrics.roas_full') }}</option>
                          <option value="ctr">{{ $t('alerts.metrics.ctr_full') }}</option>
                          <option value="cvr">{{ $t('alerts.metrics.cvr_full') }}</option>
                          <option value="conversions">{{ $t('alerts.metrics.conversions') }}</option>
                          <option value="leads">{{ $t('alerts.metrics.leads') }}</option>
                          <option value="calls">{{ $t('alerts.metrics.calls') }}</option>
                          <option value="revenue">{{ $t('alerts.metrics.revenue') }}</option>
                        </select>
                      </div>

                      <div>
                        <label class="block text-sm font-medium text-gray-700">{{ $t('labels.operator') }} <span class="text-red-500">{{ $t('messages.required_field') }}</span></label>
                        <select
                          v-model="form.conditions.operator"
                          required
                          class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
                        >
                          <option value="">{{ $t('placeholders.select_option') }}</option>
                          <option value=">">{{ $t('operators.greater_than') }}</option>
                          <option value="<">{{ $t('operators.less_than') }}</option>
                          <option value=">=">{{ $t('operators.greater_equal') }}</option>
                          <option value="<=">{{ $t('operators.less_equal') }}</option>
                          <option value="=">{{ $t('operators.equal') }}</option>
                        </select>
                      </div>

                      <div>
                        <label class="block text-sm font-medium text-gray-700">{{ $t('labels.value') }} <span class="text-red-500">{{ $t('messages.required_field') }}</span></label>
                        <input
                          v-model.number="form.conditions.value"
                          type="number"
                          step="0.01"
                          required
                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                          :placeholder="$t('placeholders.budget_amount')"
                        />
                      </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <label class="block text-sm font-medium text-gray-700">{{ $t('labels.time_period') }} <span class="text-red-500">{{ $t('messages.required_field') }}</span></label>
                        <select
                          v-model="form.conditions.period"
                          required
                          class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
                        >
                          <option value="today">{{ $t('date.today') }}</option>
                          <option value="yesterday">{{ $t('date.yesterday') }}</option>
                          <option value="last_7_days">{{ $t('date.last_7_days') }}</option>
                          <option value="last_30_days">{{ $t('date.last_30_days') }}</option>
                          <option value="this_week">{{ $t('time.this_week') }}</option>
                          <option value="this_month">{{ $t('date.this_month') }}</option>
                        </select>
                      </div>

                      <div>
                        <label class="block text-sm font-medium text-gray-700">{{ $t('labels.scope') }} <span class="text-red-500">{{ $t('messages.required_field') }}</span></label>
                        <select
                          v-model="form.conditions.scope"
                          required
                          class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
                        >
                          <option value="all">{{ $t('scopes.all_accounts') }}</option>
                          <option value="account">{{ $t('scopes.specific_account') }}</option>
                          <option value="campaign">{{ $t('scopes.specific_campaign') }}</option>
                        </select>
                      </div>
                    </div>

                    <div v-if="form.conditions.scope !== 'all'">
                      <label class="block text-sm font-medium text-gray-700">
                        {{ form.conditions.scope === 'account' ? $t('labels.account_id') : $t('labels.campaign_id') }}
                      </label>
                      <input
                        v-model.number="form.conditions.scope_id"
                        type="number"
                        required
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                        :placeholder="$t('placeholders.enter_id')"
                      />
                    </div>
                  </div>

                  <!-- Budget Alert Conditions -->
                  <div v-if="form.type === 'budget'" class="space-y-4 p-4 bg-gray-50 rounded-md">
                    <h4 class="text-sm font-medium text-gray-900">{{ $t('alerts.sections.budget_conditions') }}</h4>

                    <div class="grid grid-cols-3 gap-4">
                      <div>
                        <label class="block text-sm font-medium text-gray-700">{{ $t('labels.budget') }} <span class="text-red-500">{{ $t('messages.required_field') }}</span></label>
                        <input
                          v-model.number="form.conditions.budget"
                          type="number"
                          step="0.01"
                          required
                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                          :placeholder="$t('placeholders.budget_amount')"
                        />
                      </div>

                      <div>
                        <label class="block text-sm font-medium text-gray-700">{{ $t('labels.period') }} <span class="text-red-500">{{ $t('messages.required_field') }}</span></label>
                        <select
                          v-model="form.conditions.period"
                          required
                          class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
                        >
                          <option value="daily">{{ $t('time.daily') }}</option>
                          <option value="weekly">{{ $t('time.weekly') }}</option>
                          <option value="monthly">{{ $t('time.monthly') }}</option>
                        </select>
                      </div>

                      <div>
                        <label class="block text-sm font-medium text-gray-700">{{ $t('labels.alert_at_percent') }} <span class="text-red-500">{{ $t('messages.required_field') }}</span></label>
                        <input
                          v-model.number="form.conditions.threshold"
                          type="number"
                          min="1"
                          max="100"
                          required
                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                          placeholder="90"
                        />
                      </div>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700">{{ $t('labels.scope') }} <span class="text-red-500">{{ $t('messages.required_field') }}</span></label>
                      <select
                        v-model="form.conditions.scope"
                        required
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
                      >
                        <option value="all">{{ $t('scopes.all_accounts') }}</option>
                        <option value="account">{{ $t('scopes.specific_account') }}</option>
                        <option value="campaign">{{ $t('scopes.specific_campaign') }}</option>
                      </select>
                    </div>

                    <div v-if="form.conditions.scope !== 'all'">
                      <label class="block text-sm font-medium text-gray-700">
                        {{ form.conditions.scope === 'account' ? $t('labels.account_id') : $t('labels.campaign_id') }}
                      </label>
                      <input
                        v-model.number="form.conditions.scope_id"
                        type="number"
                        required
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                        :placeholder="$t('placeholders.enter_id')"
                      />
                    </div>
                  </div>

                  <!-- Anomaly Alert Conditions -->
                  <div v-if="form.type === 'anomaly'" class="space-y-4 p-4 bg-gray-50 rounded-md">
                    <h4 class="text-sm font-medium text-gray-900">{{ $t('alerts.sections.anomaly_settings') }}</h4>
                    <p class="text-sm text-gray-600">
                      {{ $t('alerts.helpers.ai_powered_detection') }}
                    </p>

                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <label class="block text-sm font-medium text-gray-700">{{ $t('labels.metric') }} <span class="text-red-500">{{ $t('messages.required_field') }}</span></label>
                        <select
                          v-model="form.conditions.metric"
                          required
                          class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
                        >
                          <option value="">{{ $t('alerts.metrics.select') }}</option>
                          <option value="spend">{{ $t('alerts.metrics.spend') }}</option>
                          <option value="cpl">{{ $t('alerts.metrics.cpl_full') }}</option>
                          <option value="cpc">{{ $t('alerts.metrics.cpc_full') }}</option>
                          <option value="cpm">{{ $t('alerts.metrics.cpm_full') }}</option>
                          <option value="cpa">{{ $t('alerts.metrics.cpa_full') }}</option>
                          <option value="roas">{{ $t('alerts.metrics.roas_full') }}</option>
                          <option value="ctr">{{ $t('alerts.metrics.ctr_full') }}</option>
                          <option value="cvr">{{ $t('alerts.metrics.cvr_full') }}</option>
                          <option value="conversions">{{ $t('alerts.metrics.conversions') }}</option>
                          <option value="leads">{{ $t('alerts.metrics.leads') }}</option>
                          <option value="calls">{{ $t('alerts.metrics.calls') }}</option>
                          <option value="revenue">{{ $t('alerts.metrics.revenue') }}</option>
                          <option value="impressions">{{ $t('alerts.metrics.impressions') }}</option>
                          <option value="clicks">{{ $t('alerts.metrics.clicks') }}</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                          {{ $t('alerts.helpers.metric_to_monitor') }}
                        </p>
                      </div>

                      <div>
                        <label class="block text-sm font-medium text-gray-700">{{ $t('alerts.labels.detection_method') }} <span class="text-red-500">{{ $t('messages.required_field') }}</span></label>
                        <select
                          v-model="form.conditions.detection_method"
                          required
                          class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
                        >
                          <option value="combined">{{ $t('alerts.detection_methods.combined') }}</option>
                          <option value="zscore">{{ $t('alerts.detection_methods.zscore') }}</option>
                          <option value="iqr">{{ $t('alerts.detection_methods.iqr') }}</option>
                          <option value="percentage_change">{{ $t('alerts.detection_methods.percentage_change') }}</option>
                          <option value="seasonal">{{ $t('alerts.detection_methods.seasonal') }}</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                          {{ $t('alerts.helpers.combined_best_accuracy') }}
                        </p>
                      </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <label class="block text-sm font-medium text-gray-700">{{ $t('alerts.labels.sensitivity') }} <span class="text-red-500">{{ $t('messages.required_field') }}</span></label>
                        <select
                          v-model="form.conditions.sensitivity"
                          required
                          class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
                        >
                          <option value="low">{{ $t('alerts.sensitivity_levels.low') }}</option>
                          <option value="moderate">{{ $t('alerts.sensitivity_levels.moderate') }}</option>
                          <option value="high">{{ $t('alerts.sensitivity_levels.high') }}</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                          {{ $t('alerts.helpers.higher_sensitivity') }}
                        </p>
                      </div>

                      <div>
                        <label class="block text-sm font-medium text-gray-700">{{ $t('alerts.labels.lookback_days') }} <span class="text-red-500">{{ $t('messages.required_field') }}</span></label>
                        <select
                          v-model.number="form.conditions.lookback_days"
                          required
                          class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
                        >
                          <option :value="7">{{ $t('alerts.lookback_options.7_days') }}</option>
                          <option :value="14">{{ $t('alerts.lookback_options.14_days') }}</option>
                          <option :value="30">{{ $t('alerts.lookback_options.30_days') }}</option>
                          <option :value="60">{{ $t('alerts.lookback_options.60_days') }}</option>
                          <option :value="90">{{ $t('alerts.lookback_options.90_days') }}</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                          {{ $t('alerts.helpers.historical_period') }}
                        </p>
                      </div>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700">{{ $t('labels.scope') }} <span class="text-red-500">{{ $t('messages.required_field') }}</span></label>
                      <select
                        v-model="form.conditions.scope"
                        required
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
                      >
                        <option value="all">{{ $t('scopes.all_accounts') }}</option>
                        <option value="account">{{ $t('scopes.specific_account') }}</option>
                        <option value="campaign">{{ $t('scopes.specific_campaign') }}</option>
                      </select>
                    </div>

                    <div v-if="form.conditions.scope !== 'all'">
                      <label class="block text-sm font-medium text-gray-700">
                        {{ form.conditions.scope === 'account' ? $t('labels.account_id') : $t('labels.campaign_id') }} <span class="text-red-500">{{ $t('messages.required_field') }}</span>
                      </label>
                      <input
                        v-model.number="form.conditions.scope_id"
                        type="number"
                        required
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                        :placeholder="$t('placeholders.enter_id')"
                      />
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-md p-3">
                      <h5 class="text-sm font-medium text-blue-900 mb-2">{{ $t('alerts.helpers.how_it_works') }}</h5>
                      <ul class="text-xs text-blue-800 space-y-1 list-disc list-inside">
                        <li><strong>Z-Score:</strong> {{ $t('alerts.detection_descriptions.zscore') }}</li>
                        <li><strong>IQR:</strong> {{ $t('alerts.detection_descriptions.iqr') }}</li>
                        <li><strong>Percentage Change:</strong> {{ $t('alerts.detection_descriptions.percentage_change') }}</li>
                        <li><strong>Seasonal:</strong> {{ $t('alerts.detection_descriptions.seasonal') }}</li>
                        <li><strong>Combined:</strong> {{ $t('alerts.detection_descriptions.combined') }}</li>
                      </ul>
                    </div>
                  </div>

                  <!-- Notification Channels -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      {{ $t('alerts.labels.notification_channels') }} <span class="text-red-500">{{ $t('messages.required_field') }}</span>
                    </label>
                    <div class="space-y-2">
                      <div class="flex items-center">
                        <input
                          v-model="form.notification_channels"
                          value="email"
                          type="checkbox"
                          id="channel-email"
                          class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                        />
                        <label for="channel-email" class="ml-2 text-sm text-gray-700">
                          {{ $t('alerts.channels.email') }}
                        </label>
                      </div>
                      <div class="flex items-center">
                        <input
                          v-model="form.notification_channels"
                          value="slack"
                          type="checkbox"
                          id="channel-slack"
                          class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                          disabled
                        />
                        <label for="channel-slack" class="ml-2 text-sm text-gray-400">
                          {{ $t('alerts.channels.slack_soon') }}
                        </label>
                      </div>
                      <div class="flex items-center">
                        <input
                          v-model="form.notification_channels"
                          value="whatsapp"
                          type="checkbox"
                          id="channel-whatsapp"
                          class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                          disabled
                        />
                        <label for="channel-whatsapp" class="ml-2 text-sm text-gray-400">
                          {{ $t('alerts.channels.whatsapp_soon') }}
                        </label>
                      </div>
                    </div>
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
                      {{ $t('alerts.labels.activate_immediately') }}
                    </label>
                  </div>

                  <!-- Actions -->
                  <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                    <button
                      type="submit"
                      :disabled="saving"
                      class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:col-start-2 sm:text-sm disabled:opacity-50"
                    >
                      {{ saving ? $t('alerts.buttons.saving') : (isEdit ? $t('alerts.buttons.update_alert') : $t('alerts.buttons.create_alert')) }}
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
  alert?: any
}

const props = defineProps<Props>()
const emit = defineEmits(['close', 'saved'])

const isEdit = computed(() => !!props.alert)
const saving = ref(false)

const form = reactive({
  name: '',
  type: '',
  objective: '',
  conditions: {
    // Threshold fields
    metric: '',
    operator: '',
    value: 0,
    period: 'last_7_days',
    scope: 'all',
    scope_id: null,
    // Budget fields
    budget: 0,
    threshold: 90,
    // Anomaly fields
    detection_method: 'combined',
    sensitivity: 'moderate',
    lookback_days: 30,
  },
  notification_channels: ['email'],
  is_active: true,
})

// Initialize form if editing
if (props.alert) {
  form.name = props.alert.name
  form.type = props.alert.type
  form.objective = props.alert.objective || ''
  form.conditions = { ...form.conditions, ...props.alert.conditions }
  form.notification_channels = props.alert.notification_channels
  form.is_active = props.alert.is_active
}

const onTypeChange = () => {
  // Reset conditions when type changes
  form.conditions = {
    metric: '',
    operator: '',
    value: 0,
    period: form.type === 'threshold' ? 'last_7_days' : 'daily',
    scope: 'all',
    scope_id: null,
    budget: 0,
    threshold: 90,
    detection_method: 'combined',
    sensitivity: 'moderate',
    lookback_days: 30,
  }
}

const saveAlert = async () => {
  // Validate notification channels
  if (form.notification_channels.length === 0) {
    alert(t('alerts.helpers.select_notification_channel'))
    return
  }

  saving.value = true

  try {
    const data = {
      name: form.name,
      type: form.type,
      objective: form.objective || null,
      conditions: form.conditions,
      notification_channels: form.notification_channels,
      is_active: form.is_active,
    }

    if (isEdit.value) {
      await window.axios.put(`/api/alerts/${props.alert.id}`, data)
    } else {
      await window.axios.post('/api/alerts', data)
    }

    emit('saved')
  } catch (error: any) {
    console.error('Error saving alert:', error)
    alert(t('alerts.helpers.failed_to_save') + (error.response?.data?.message || error.message))
  } finally {
    saving.value = false
  }
}
</script>
