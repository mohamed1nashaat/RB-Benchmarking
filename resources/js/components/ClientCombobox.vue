<template>
  <Combobox v-model="selectedClient" @update:model-value="handleChange" nullable>
    <div class="relative">
      <div class="relative">
        <ComboboxInput
          class="w-full rounded-lg border-gray-300 bg-white py-2 pl-3 pr-10 text-sm leading-5 text-gray-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
          :class="{ 'opacity-50': disabled }"
          :display-value="(client) => client?.name || ''"
          :placeholder="placeholder"
          :disabled="disabled"
          @change="query = $event.target.value"
        />
        <ComboboxButton class="absolute inset-y-0 right-0 flex items-center pr-2">
          <ChevronUpDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
        </ComboboxButton>
      </div>

      <TransitionRoot
        leave="transition ease-in duration-100"
        leaveFrom="opacity-100"
        leaveTo="opacity-0"
        @after-leave="query = ''"
      >
        <ComboboxOptions
          class="absolute z-50 mt-1 max-h-60 min-w-[200px] w-full overflow-auto rounded-lg bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
        >
          <!-- Unassigned option -->
          <ComboboxOption
            v-slot="{ active, selected }"
            :value="null"
            as="template"
          >
            <li
              :class="[
                active ? 'bg-gray-100' : '',
                'relative cursor-pointer select-none py-2.5 pl-10 pr-4'
              ]"
            >
              <div class="flex items-center gap-2">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                  <UserIcon class="w-4 h-4 text-gray-400" />
                </div>
                <div class="flex flex-col">
                  <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate text-gray-500']">
                    Unassigned
                  </span>
                </div>
              </div>
              <span
                v-if="selected"
                class="absolute inset-y-0 left-0 flex items-center pl-3 text-primary-600"
              >
                <CheckIcon class="h-5 w-5" aria-hidden="true" />
              </span>
            </li>
          </ComboboxOption>

          <!-- No results -->
          <div
            v-if="filteredClients.length === 0 && query !== ''"
            class="relative cursor-default select-none py-3 px-4 text-gray-500 text-sm"
          >
            No clients found for "{{ query }}"
          </div>

          <!-- Client options -->
          <ComboboxOption
            v-for="client in filteredClients"
            :key="client.id"
            v-slot="{ active, selected }"
            :value="client"
            as="template"
          >
            <li
              :class="[
                active ? 'bg-primary-50' : '',
                'relative cursor-pointer select-none py-2.5 pl-10 pr-4'
              ]"
            >
              <div class="flex items-center gap-2">
                <div
                  class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold"
                  :class="getClientColor(client.id)"
                >
                  {{ client.name.charAt(0).toUpperCase() }}
                </div>
                <div class="flex flex-col min-w-0">
                  <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate text-gray-900']">
                    {{ client.name }}
                  </span>
                  <span v-if="client.ad_accounts_count !== undefined" class="text-xs text-gray-500">
                    {{ client.ad_accounts_count }} account{{ client.ad_accounts_count !== 1 ? 's' : '' }}
                  </span>
                </div>
              </div>
              <span
                v-if="selected"
                class="absolute inset-y-0 left-0 flex items-center pl-3 text-primary-600"
              >
                <CheckIcon class="h-5 w-5" aria-hidden="true" />
              </span>
            </li>
          </ComboboxOption>

          <!-- Add new client option -->
          <div
            v-if="showAddOption"
            class="border-t border-gray-100 mt-1 pt-1"
          >
            <button
              type="button"
              @click.stop.prevent="handleAddClient"
              @mousedown.stop.prevent
              class="w-full flex items-center gap-2 py-2.5 px-4 text-sm text-primary-600 hover:bg-primary-50 transition-colors"
            >
              <PlusCircleIcon class="w-5 h-5" />
              <span class="font-medium">Add New Client</span>
            </button>
          </div>
        </ComboboxOptions>
      </TransitionRoot>
    </div>
  </Combobox>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import {
  Combobox,
  ComboboxInput,
  ComboboxButton,
  ComboboxOptions,
  ComboboxOption,
  TransitionRoot,
} from '@headlessui/vue'
import { CheckIcon, ChevronUpDownIcon, PlusCircleIcon, UserIcon } from '@heroicons/vue/20/solid'

interface Client {
  id: number
  name: string
  ad_accounts_count?: number
}

const props = withDefaults(defineProps<{
  modelValue: Client | null
  clients: Client[]
  placeholder?: string
  disabled?: boolean
  showAddOption?: boolean
}>(), {
  placeholder: 'Select client...',
  disabled: false,
  showAddOption: true
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: Client | null): void
  (e: 'change', value: Client | null): void
  (e: 'add-client'): void
}>()

const query = ref('')

const selectedClient = computed({
  get: () => props.modelValue,
  set: (value: Client | null) => {
    emit('update:modelValue', value)
  }
})

const filteredClients = computed(() => {
  if (query.value === '') {
    return props.clients
  }
  return props.clients.filter((client) =>
    client.name.toLowerCase().includes(query.value.toLowerCase())
  )
})

const handleChange = (client: Client | null) => {
  emit('change', client)
}

const handleAddClient = () => {
  emit('add-client')
}

// Generate consistent colors based on client ID
const colors = [
  'bg-primary-100 text-primary-700',
  'bg-blue-100 text-blue-700',
  'bg-green-100 text-green-700',
  'bg-purple-100 text-purple-700',
  'bg-orange-100 text-orange-700',
  'bg-pink-100 text-pink-700',
  'bg-teal-100 text-teal-700',
  'bg-indigo-100 text-indigo-700',
]

const getClientColor = (id: number) => {
  return colors[id % colors.length]
}
</script>
