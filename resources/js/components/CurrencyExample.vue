<template>
  <div class="p-6 bg-white rounded-lg shadow-sm border border-gray-200">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Saudi Riyal Currency Display Examples</h3>

    <div class="space-y-4">
      <!-- Regular Display -->
      <div class="border-b border-gray-200 pb-4">
        <h4 class="text-sm font-medium text-gray-700 mb-2">Regular Display</h4>
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Small amount:</span>
            <CurrencyDisplay :amount="125.50" currency="SAR" />
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Large amount:</span>
            <CurrencyDisplay :amount="1275000" currency="SAR" />
          </div>
        </div>
      </div>

      <!-- Compact Display -->
      <div class="border-b border-gray-200 pb-4">
        <h4 class="text-sm font-medium text-gray-700 mb-2">Compact Display (K/M)</h4>
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Thousands:</span>
            <CurrencyDisplay :amount="125500" currency="SAR" :compact="true" />
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Millions:</span>
            <CurrencyDisplay :amount="12750000" currency="SAR" :compact="true" />
          </div>
        </div>
      </div>

      <!-- Different Sizes -->
      <div class="border-b border-gray-200 pb-4">
        <h4 class="text-sm font-medium text-gray-700 mb-2">Different Icon Sizes</h4>
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Small icon:</span>
            <CurrencyDisplay :amount="1000" currency="SAR" icon-size="0.6em" />
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Normal icon:</span>
            <CurrencyDisplay :amount="1000" currency="SAR" icon-size="0.9em" />
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Large icon:</span>
            <CurrencyDisplay :amount="1000" currency="SAR" icon-size="1.2em" />
          </div>
        </div>
      </div>

      <!-- Text Formatting -->
      <div class="border-b border-gray-200 pb-4">
        <h4 class="text-sm font-medium text-gray-700 mb-2">Text-Only Formatting</h4>
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Text format:</span>
            <span class="font-medium">{{ formatSAR(1275.50) }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Compact text:</span>
            <span class="font-medium">{{ formatSARCompact(1275500) }}</span>
          </div>
        </div>
      </div>

      <!-- Custom Styling -->
      <div>
        <h4 class="text-sm font-medium text-gray-700 mb-2">Custom Styling</h4>
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Green success:</span>
            <CurrencyDisplay
              :amount="5000"
              currency="SAR"
              icon-color="#10b981"
              container-class="text-green-600 font-semibold"
            />
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Red danger:</span>
            <CurrencyDisplay
              :amount="2500"
              currency="SAR"
              icon-color="#ef4444"
              container-class="text-red-600 font-semibold"
            />
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Large display:</span>
            <CurrencyDisplay
              :amount="15000"
              currency="SAR"
              icon-size="1.5em"
              container-class="text-2xl font-bold text-gray-900"
              :compact="true"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import CurrencyDisplay from './CurrencyDisplay.vue'
import { formatSAR, formatSARCompact } from '@/utils/currency'
</script>