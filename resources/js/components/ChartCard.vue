<template>
  <div class="bg-white overflow-hidden shadow rounded-lg">
    <div class="p-5">
      <div class="flex items-center justify-between">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
          {{ title }}
        </h3>
        <div class="flex space-x-2">
          <button
            v-for="period in periods"
            :key="period.value"
            @click="selectedPeriod = period.value"
            :class="[
              'px-3 py-1 text-sm rounded-md',
              selectedPeriod === period.value
                ? 'bg-primary-100 text-primary-700'
                : 'text-gray-500 hover:text-gray-700'
            ]"
          >
            {{ period.label }}
          </button>
        </div>
      </div>
      
      <div class="mt-5">
        <div v-if="loading" class="flex justify-center items-center h-64">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        </div>
        
        <div v-else-if="chartData.length === 0" class="flex justify-center items-center h-64 text-gray-500">
          No data available
        </div>
        
        <canvas
          v-else
          ref="chartCanvas"
          class="w-full h-64"
        ></canvas>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, nextTick } from 'vue'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  type ChartConfiguration
} from 'chart.js'

// Register Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend
)

interface ChartDataPoint {
  period: string
  value: number
  raw_metrics?: any
}

interface Props {
  title: string
  chartData: ChartDataPoint[]
  chartType?: 'line' | 'bar'
  loading?: boolean
  metric?: string
}

const props = withDefaults(defineProps<Props>(), {
  chartType: 'line',
  loading: false
})

const chartCanvas = ref<HTMLCanvasElement>()
const selectedPeriod = ref('7d')
let chartInstance: ChartJS | null = null

const periods = [
  { label: '7D', value: '7d' },
  { label: '30D', value: '30d' },
  { label: '90D', value: '90d' }
]

const createChart = () => {
  if (!chartCanvas.value || props.chartData.length === 0) return

  // Destroy existing chart
  if (chartInstance) {
    chartInstance.destroy()
  }

  const ctx = chartCanvas.value.getContext('2d')
  if (!ctx) return

  const labels = props.chartData.map(item => {
    const date = new Date(item.period)
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
  })

  const data = props.chartData.map(item => item.value)

  const config: ChartConfiguration = {
    type: props.chartType,
    data: {
      labels,
      datasets: [{
        label: props.title,
        data,
        borderColor: props.chartType === 'line' ? 'rgb(79, 70, 229)' : undefined,
        backgroundColor: props.chartType === 'line' 
          ? 'rgba(79, 70, 229, 0.1)' 
          : 'rgba(79, 70, 229, 0.8)',
        borderWidth: 2,
        fill: props.chartType === 'line',
        tension: 0.4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          callbacks: {
            label: (context) => {
              const value = context.parsed.y
              if (props.metric) {
                return formatTooltipValue(props.metric, value)
              }
              return value.toLocaleString()
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: (value) => {
              if (props.metric) {
                return formatAxisValue(props.metric, value as number)
              }
              return value
            }
          }
        }
      }
    }
  }

  chartInstance = new ChartJS(ctx, config)
}

const formatTooltipValue = (metric: string, value: number): string => {
  if (['cpm', 'cpl', 'cpc', 'cpa', 'cost_per_call', 'spend'].includes(metric)) {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'SAR'  // TODO: Make this dynamic based on data currency
    }).format(value)
  }
  
  if (['ctr', 'cvr', 'vtr'].includes(metric)) {
    return `${value.toFixed(2)}%`  // Already multiplied by 100 in backend
  }
  
  if (metric === 'roas') {
    return `${value.toFixed(2)}x`
  }
  
  return value.toLocaleString()
}

const formatAxisValue = (metric: string, value: number): string => {
  if (['cpm', 'cpl', 'cpc', 'cpa', 'cost_per_call', 'spend'].includes(metric)) {
    return `﷼${value}`  // SAR currency symbol
  }
  
  if (['ctr', 'cvr', 'vtr'].includes(metric)) {
    return `${value.toFixed(0)}%`  // Already multiplied by 100 in backend
  }
  
  if (metric === 'roas') {
    return `${value.toFixed(1)}x`
  }
  
  if (value >= 1000000) {
    return `${(value / 1000000).toFixed(1)}M`
  } else if (value >= 1000) {
    return `${(value / 1000).toFixed(1)}K`
  }
  
  return value.toString()
}

onMounted(() => {
  nextTick(() => {
    createChart()
  })
})

watch(() => props.chartData, () => {
  nextTick(() => {
    createChart()
  })
}, { deep: true })

watch(() => props.loading, (newLoading) => {
  if (!newLoading) {
    nextTick(() => {
      createChart()
    })
  }
})
</script>
