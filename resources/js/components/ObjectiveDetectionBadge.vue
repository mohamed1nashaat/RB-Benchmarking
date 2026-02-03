<template>
  <div v-if="campaignName" class="inline-flex items-center">
    <span v-if="detection.objective"
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium cursor-pointer"
          :class="getConfidenceClass()"
          :title="`Auto-detected from campaign name with ${detection.confidence} confidence (score: ${detection.score})`"
          @click="$emit('objectiveDetected', detection)">
      {{ formatObjective(detection.objective) }}
      <span v-if="showConfidence" class="ml-1 text-xs opacity-75">
        ({{ detection.confidence }})
      </span>
      <!-- Auto-detection icon -->
      <svg class="ml-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
      </svg>
    </span>
    <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs text-gray-400 bg-gray-50">
      Not detected
    </span>
    <!-- Action button to auto-set objective -->
    <button v-if="detection.objective && showSetButton"
            @click="setObjective"
            class="ml-2 inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-primary-700 bg-primary-100 hover:bg-primary-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            :title="`Set dashboard objective to ${formatObjective(detection.objective)}`">
      Set as Objective
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { detectCampaignObjectiveWithConfidence } from '@/utils/objectiveDetection'
import { useDashboardStore } from '@/stores/dashboard'

interface Props {
  campaignName: string
  showConfidence?: boolean
  showSetButton?: boolean
}

interface Emits {
  (e: 'objectiveDetected', detection: any): void
  (e: 'objectiveSet', objective: string): void
}

const props = withDefaults(defineProps<Props>(), {
  showConfidence: true,
  showSetButton: true
})

const emit = defineEmits<Emits>()
const dashboardStore = useDashboardStore()

const detection = computed(() => {
  if (!props.campaignName) {
    return { objective: null, confidence: 'none', score: 0 }
  }
  return detectCampaignObjectiveWithConfidence(props.campaignName)
})

const getConfidenceClass = () => {
  switch (detection.value.confidence) {
    case 'high':
      return 'bg-primary-100 text-primary-800 hover:bg-primary-200'
    case 'medium':
      return 'bg-secondary-100 text-secondary-800 hover:bg-secondary-200'
    case 'low':
      return 'bg-gray-100 text-gray-600 hover:bg-gray-200'
    default:
      return 'bg-gray-50 text-gray-400'
  }
}

const formatObjective = (objective: string | null) => {
  if (!objective) return ''
  return objective.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const setObjective = () => {
  if (detection.value.objective) {
    dashboardStore.setObjective(detection.value.objective as any)
    emit('objectiveSet', detection.value.objective)
  }
}
</script>