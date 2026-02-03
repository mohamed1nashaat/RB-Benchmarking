<template>
  <TransitionRoot as="template" :show="open">
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
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl sm:p-6">
              <!-- Header -->
              <div class="mb-6">
                <DialogTitle as="h3" class="text-lg font-semibold leading-6 text-gray-900">
                  Create Client from Ad Accounts
                </DialogTitle>
                <p class="mt-1 text-sm text-gray-500">
                  Select ad accounts to automatically populate client information
                </p>
              </div>

              <!-- Progress Steps -->
              <nav aria-label="Progress" class="mb-16">
                <ol role="list" class="flex items-center">
                  <li v-for="(step, stepIdx) in steps" :key="step.name" :class="[stepIdx !== steps.length - 1 ? 'pr-8 sm:pr-20' : '', 'relative']">
                    <template v-if="currentStep > stepIdx">
                      <div class="absolute inset-0 flex items-center" aria-hidden="true">
                        <div class="h-0.5 w-full bg-primary-600" />
                      </div>
                      <div class="relative flex h-8 w-8 items-center justify-center rounded-full bg-primary-600">
                        <CheckIcon class="h-5 w-5 text-white" aria-hidden="true" />
                      </div>
                    </template>
                    <template v-else-if="currentStep === stepIdx">
                      <div class="absolute inset-0 flex items-center" aria-hidden="true">
                        <div class="h-0.5 w-full bg-gray-200" />
                      </div>
                      <div class="relative flex h-8 w-8 items-center justify-center rounded-full border-2 border-primary-600 bg-white">
                        <span class="h-2.5 w-2.5 rounded-full bg-primary-600" aria-hidden="true" />
                      </div>
                    </template>
                    <template v-else>
                      <div class="absolute inset-0 flex items-center" aria-hidden="true">
                        <div class="h-0.5 w-full bg-gray-200" />
                      </div>
                      <div class="group relative flex h-8 w-8 items-center justify-center rounded-full border-2 border-gray-300 bg-white">
                        <span class="h-2.5 w-2.5 rounded-full bg-transparent group-hover:bg-gray-300" aria-hidden="true" />
                      </div>
                    </template>
                    <span class="absolute top-10 left-1/2 -translate-x-1/2 text-xs font-medium text-gray-500 whitespace-nowrap">{{ step.name }}</span>
                  </li>
                </ol>
              </nav>

              <!-- Step Content -->
              <div>
                <!-- Step 1: Select Accounts -->
                <div v-if="currentStep === 0" class="space-y-4">
                  <div class="border-b border-gray-200 pb-4">
                    <input
                      v-model="accountSearch"
                      type="text"
                      placeholder="Search accounts..."
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                    />
                  </div>
                  <div class="max-h-96 overflow-y-auto space-y-2">
                    <div v-for="account in filteredAccounts" :key="account.id" class="flex items-center p-3 border rounded-lg hover:bg-gray-50">
                      <input
                        :id="`account-${account.id}`"
                        v-model="selectedAccountIds"
                        :value="account.id"
                        type="checkbox"
                        class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                      />
                      <label :for="`account-${account.id}`" class="ml-3 flex-1 cursor-pointer">
                        <div class="flex items-center justify-between">
                          <div>
                            <p class="text-sm font-medium text-gray-900">{{ account.account_name }}</p>
                            <p class="text-xs text-gray-500 capitalize">{{ account.platform }} • {{ account.industry || 'No industry' }}</p>
                          </div>
                          <span :class="account.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'" class="px-2 py-1 text-xs rounded-full">
                            {{ account.status }}
                          </span>
                        </div>
                      </label>
                    </div>
                  </div>
                  <div v-if="filteredAccounts.length === 0" class="text-center py-12 text-gray-500">
                    <p>No ad accounts available</p>
                  </div>
                </div>

                <!-- Step 2: Review Suggestions -->
                <div v-if="currentStep === 1" class="space-y-6">
                  <div v-if="loading" class="flex justify-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
                  </div>
                  <div v-else-if="suggestions" class="space-y-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                      <p class="text-sm text-blue-700">
                        Based on {{ suggestions.accounts_summary.total_accounts }} selected accounts, we've automatically detected the following information:
                      </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                      <div>
                        <label class="block text-sm font-medium text-gray-700">Company Name</label>
                        <input
                          v-model="formData.name"
                          type="text"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700">Industry</label>
                        <input
                          v-model="formData.industry"
                          type="text"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700">Subscription Tier</label>
                        <select
                          v-model="formData.subscription_tier"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                        >
                          <option value="basic">Basic</option>
                          <option value="pro">Pro</option>
                          <option value="enterprise">Enterprise</option>
                        </select>
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700">Monthly Budget (SAR)</label>
                        <input
                          v-model.number="formData.monthly_budget"
                          type="number"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                        />
                      </div>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700">Description</label>
                      <textarea
                        v-model="formData.description"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                      ></textarea>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4">
                      <h4 class="text-sm font-medium text-gray-900 mb-2">Detected Information</h4>
                      <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                          <dt class="text-gray-500">Platforms</dt>
                          <dd class="font-medium">{{ suggestions.accounts_summary.platforms.join(', ') }}</dd>
                        </div>
                        <div>
                          <dt class="text-gray-500">Total Spend</dt>
                          <dd class="font-medium">{{ formatCurrency(suggestions.accounts_summary.total_spend) }}</dd>
                        </div>
                        <div>
                          <dt class="text-gray-500">Active Accounts</dt>
                          <dd class="font-medium">{{ suggestions.accounts_summary.active_accounts }}</dd>
                        </div>
                      </dl>
                    </div>
                  </div>
                </div>

                <!-- Step 3: Complete Details -->
                <div v-if="currentStep === 2" class="space-y-6">
                  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Contact Email</label>
                      <input
                        v-model="formData.contact_email"
                        type="email"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                      />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Contact Phone</label>
                      <input
                        v-model="formData.contact_phone"
                        type="tel"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                      />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Contact Person</label>
                      <input
                        v-model="formData.contact_person"
                        type="text"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                      />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Website</label>
                      <input
                        v-model="formData.website"
                        type="url"
                        placeholder="https://"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                      />
                    </div>
                    <div class="sm:col-span-2">
                      <label class="block text-sm font-medium text-gray-700">Address</label>
                      <textarea
                        v-model="formData.address"
                        rows="2"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                      ></textarea>
                    </div>
                  </div>

                  <div class="border-t border-gray-200 pt-6">
                    <h4 class="text-sm font-medium text-gray-900 mb-4">Billing & Contract</h4>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                      <div>
                        <label class="block text-sm font-medium text-gray-700">Billing Email</label>
                        <input
                          v-model="formData.billing_email"
                          type="email"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700">Vertical</label>
                        <input
                          v-model="formData.vertical"
                          type="text"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700">Contract Start</label>
                        <input
                          v-model="formData.contract_start_date"
                          type="date"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700">Contract End</label>
                        <input
                          v-model="formData.contract_end_date"
                          type="date"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                        />
                      </div>
                    </div>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea
                      v-model="formData.notes"
                      rows="3"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                    ></textarea>
                  </div>
                </div>

                <!-- Step 4: Review & Create -->
                <div v-if="currentStep === 3" class="space-y-6">
                  <div class="bg-gray-50 rounded-lg p-6">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Review Client Information</h4>

                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                      <div>
                        <dt class="text-sm font-medium text-gray-500">Company Name</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ formData.name }}</dd>
                      </div>
                      <div>
                        <dt class="text-sm font-medium text-gray-500">Industry</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ formData.industry || 'Not specified' }}</dd>
                      </div>
                      <div>
                        <dt class="text-sm font-medium text-gray-500">Subscription Tier</dt>
                        <dd class="mt-1 text-sm text-gray-900 capitalize">{{ formData.subscription_tier }}</dd>
                      </div>
                      <div>
                        <dt class="text-sm font-medium text-gray-500">Monthly Budget</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ formatCurrency(formData.monthly_budget) }}</dd>
                      </div>
                      <div v-if="formData.contact_email">
                        <dt class="text-sm font-medium text-gray-500">Contact Email</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ formData.contact_email }}</dd>
                      </div>
                      <div v-if="formData.contact_phone">
                        <dt class="text-sm font-medium text-gray-500">Contact Phone</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ formData.contact_phone }}</dd>
                      </div>
                    </dl>

                    <div class="mt-6 pt-6 border-t border-gray-200">
                      <h5 class="text-sm font-medium text-gray-900 mb-2">Selected Ad Accounts ({{ selectedAccountIds.length }})</h5>
                      <div class="space-y-2">
                        <div v-for="accountId in selectedAccountIds" :key="accountId" class="text-sm text-gray-600">
                          {{ accounts.find(a => a.id === accountId)?.account_name }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="mt-6 flex justify-between">
                <button
                  v-if="currentStep > 0"
                  type="button"
                  @click="previousStep"
                  class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                >
                  Back
                </button>
                <div v-else></div>

                <div class="flex gap-3">
                  <button
                    type="button"
                    @click="$emit('close')"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                  >
                    Cancel
                  </button>
                  <button
                    v-if="currentStep < steps.length - 1"
                    type="button"
                    @click="nextStep"
                    :disabled="!canProceed"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Continue
                  </button>
                  <button
                    v-else
                    type="button"
                    @click="createClient"
                    :disabled="creating"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    {{ creating ? 'Creating...' : 'Create Client' }}
                  </button>
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
import { ref, computed, watch } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { CheckIcon } from '@heroicons/vue/24/solid'

interface Props {
  open: boolean
  accounts: any[]
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'close'): void
  (e: 'created', client: any): void
}>()

const steps = [
  { name: 'Select Accounts' },
  { name: 'Review' },
  { name: 'Details' },
  { name: 'Confirm' }
]

const currentStep = ref(0)
const selectedAccountIds = ref<number[]>([])
const accountSearch = ref('')
const loading = ref(false)
const creating = ref(false)
const suggestions = ref<any>(null)

const formData = ref({
  name: '',
  description: '',
  industry: '',
  subscription_tier: 'basic',
  monthly_budget: 0,
  contact_email: '',
  contact_phone: '',
  contact_person: '',
  website: '',
  address: '',
  billing_email: '',
  vertical: '',
  contract_start_date: '',
  contract_end_date: '',
  notes: ''
})

const filteredAccounts = computed(() => {
  if (!accountSearch.value) return props.accounts

  const search = accountSearch.value.toLowerCase()
  return props.accounts.filter(account =>
    account.account_name.toLowerCase().includes(search) ||
    account.platform?.toLowerCase().includes(search) ||
    account.industry?.toLowerCase().includes(search)
  )
})

const canProceed = computed(() => {
  if (currentStep.value === 0) {
    return selectedAccountIds.value.length > 0
  }
  if (currentStep.value === 1) {
    return formData.value.name.trim().length > 0
  }
  return true
})

const formatCurrency = (amount: number): string => {
  if (!amount) return '0 SAR'
  if (amount >= 1000000) return `${(amount / 1000000).toFixed(1)}M SAR`
  if (amount >= 1000) return `${(amount / 1000).toFixed(1)}K SAR`
  return `${amount.toFixed(0)} SAR`
}

const fetchSuggestions = async () => {
  loading.value = true
  try {
    const response = await window.axios.post('/api/clients/suggest-from-accounts', {
      account_ids: selectedAccountIds.value
    })

    suggestions.value = response.data.data

    // Auto-fill form with suggestions
    formData.value.name = suggestions.value.suggested.name
    formData.value.industry = suggestions.value.suggested.industry
    formData.value.description = suggestions.value.suggested.description
    formData.value.subscription_tier = suggestions.value.suggested.subscription_tier
    formData.value.monthly_budget = suggestions.value.suggested.monthly_budget

    // Fill contact email if found
    if (suggestions.value.suggested.contact_info.emails_found.length > 0) {
      formData.value.contact_email = suggestions.value.suggested.contact_info.emails_found[0]
    }
  } catch (error) {
    console.error('Error fetching suggestions:', error)
  } finally {
    loading.value = false
  }
}

const nextStep = async () => {
  if (currentStep.value === 0 && selectedAccountIds.value.length > 0) {
    await fetchSuggestions()
  }
  currentStep.value++
}

const previousStep = () => {
  currentStep.value--
}

const createClient = async () => {
  creating.value = true
  try {
    const response = await window.axios.post('/api/clients/create-from-accounts', {
      account_ids: selectedAccountIds.value,
      ...formData.value
    })

    emit('created', response.data.data.client)
    emit('close')
  } catch (error: any) {
    console.error('Error creating client:', error)
    alert(error.response?.data?.message || 'Failed to create client')
  } finally {
    creating.value = false
  }
}

// Reset form when dialog closes
watch(() => props.open, (newValue) => {
  if (!newValue) {
    currentStep.value = 0
    selectedAccountIds.value = []
    accountSearch.value = ''
    suggestions.value = null
    formData.value = {
      name: '',
      description: '',
      industry: '',
      subscription_tier: 'basic',
      monthly_budget: 0,
      contact_email: '',
      contact_phone: '',
      contact_person: '',
      website: '',
      address: '',
      billing_email: '',
      vertical: '',
      contract_start_date: '',
      contract_end_date: '',
      notes: ''
    }
  }
})
</script>
