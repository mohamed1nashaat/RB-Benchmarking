<template>
  <div class="relative">
    <canvas :id="chartId" :style="{ height: height + 'px' }"></canvas>
    <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-75">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  BarElement,
  ArcElement,
  Filler,
  RadarController,
  RadialLinearScale,
  PolarAreaController
} from 'chart.js'
import ChartDataLabels from 'chartjs-plugin-datalabels'

// Register Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler,
  RadarController,
  RadialLinearScale,
  PolarAreaController,
  ChartDataLabels
)

interface Props {
  type: 'line' | 'bar' | 'doughnut' | 'pie' | 'radar' | 'polarArea'
  data: any
  options?: any
  height?: number
  loading?: boolean
  responsive?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  height: 300,
  loading: false,
  responsive: true,
  options: () => ({})
})

const emit = defineEmits<{
  chartCreated: [chart: ChartJS]
  chartDestroyed: []
}>()

const chartId = ref(`chart-${Math.random().toString(36).substr(2, 9)}`)
const chart = ref<ChartJS | null>(null)

const defaultOptions = {
  responsive: props.responsive,
  maintainAspectRatio: false,
  interaction: {
    intersect: false,
    mode: 'index' as const
  },
  plugins: {
    legend: {
      position: 'top' as const,
      display: true
    },
    title: {
      display: false
    },
    datalabels: {
      display: false
    },
    tooltip: {
      enabled: true,
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      titleColor: '#fff',
      bodyColor: '#fff',
      borderColor: 'rgba(255, 255, 255, 0.1)',
      borderWidth: 1,
      cornerRadius: 8,
    }
  },
  scales: props.type !== 'doughnut' && props.type !== 'pie' && props.type !== 'radar' ? {
    x: {
      display: true,
      grid: {
        display: false,
        drawBorder: true
      },
      ticks: {
        display: true,
        color: '#6b7280'
      }
    },
    y: {
      display: true,
      grid: {
        display: true,
        color: 'rgba(0, 0, 0, 0.1)',
        drawBorder: true
      },
      ticks: {
        display: true,
        color: '#6b7280'
      }
    }
  } : {}
}

const createChart = () => {
  if (chart.value) {
    chart.value.destroy()
  }

  const canvas = document.getElementById(chartId.value) as HTMLCanvasElement
  if (!canvas) return

  const ctx = canvas.getContext('2d')
  if (!ctx) return

  const mergedOptions = {
    ...defaultOptions,
    ...props.options
  }

  try {
    chart.value = new ChartJS(ctx, {
      type: props.type,
      data: props.data || { labels: [], datasets: [] },
      options: mergedOptions
    })

    emit('chartCreated', chart.value)
  } catch (error) {
    console.warn('Chart creation failed:', error)
  }
}

const updateChart = () => {
  if (chart.value && props.data) {
    chart.value.data = props.data
    chart.value.update('active')
  }
}

const destroyChart = () => {
  if (chart.value) {
    chart.value.destroy()
    chart.value = null
    emit('chartDestroyed')
  }
}

watch(() => props.data, () => {
  if (chart.value) {
    updateChart()
  } else {
    nextTick(createChart)
  }
}, { deep: true })

watch(() => props.options, () => {
  if (chart.value) {
    destroyChart()
    nextTick(createChart)
  }
}, { deep: true })

onMounted(() => {
  nextTick(createChart)
})

onUnmounted(() => {
  destroyChart()
})

// Expose methods for parent components
defineExpose({
  chart: () => chart.value,
  refresh: createChart,
  destroy: destroyChart
})
</script>