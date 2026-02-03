<template>
  <div class="relative">
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, onBeforeUnmount } from 'vue'
import {
  Chart,
  BarController,
  BarElement,
  CategoryScale,
  LinearScale,
  Title,
  Tooltip,
  Legend
} from 'chart.js'

// Register Chart.js components
Chart.register(
  BarController,
  BarElement,
  CategoryScale,
  LinearScale,
  Title,
  Tooltip,
  Legend
)

interface Props {
  labels: string[]
  datasets: {
    label: string
    data: number[]
    backgroundColor?: string | string[]
    borderColor?: string | string[]
    borderWidth?: number
  }[]
  title?: string
  indexAxis?: 'x' | 'y'
  height?: number
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  indexAxis: 'y',
  height: 400
})

const emit = defineEmits<{
  (e: 'barClick', data: { label: string; value: number; index: number }): void
}>()

const chartCanvas = ref<HTMLCanvasElement | null>(null)
let chartInstance: Chart | null = null

const createChart = () => {
  if (!chartCanvas.value) return

  // Destroy existing chart if it exists
  if (chartInstance) {
    chartInstance.destroy()
  }

  const ctx = chartCanvas.value.getContext('2d')
  if (!ctx) return

  chartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: props.labels,
      datasets: props.datasets.map(dataset => ({
        ...dataset,
        backgroundColor: dataset.backgroundColor || 'rgba(59, 130, 246, 0.8)',
        borderColor: dataset.borderColor || 'rgba(59, 130, 246, 1)',
        borderWidth: dataset.borderWidth || 1,
        borderRadius: 6,
        barThickness: 'flex' as const,
        maxBarThickness: 40
      }))
    },
    options: {
      indexAxis: props.indexAxis,
      responsive: true,
      maintainAspectRatio: false,
      onClick: (event, elements) => {
        if (elements.length > 0) {
          const index = elements[0].index
          const label = props.labels[index]
          const value = props.datasets[0].data[index]
          emit('barClick', { label, value, index })
        }
      },
      plugins: {
        legend: {
          display: props.datasets.length > 1,
          position: 'top' as const,
          labels: {
            usePointStyle: true,
            padding: 15,
            font: {
              size: 12,
              family: "'Inter', sans-serif"
            }
          }
        },
        title: {
          display: !!props.title,
          text: props.title,
          font: {
            size: 16,
            weight: 'bold' as const,
            family: "'Inter', sans-serif"
          },
          padding: {
            top: 10,
            bottom: 20
          }
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          padding: 12,
          titleFont: {
            size: 13,
            weight: 'bold' as const
          },
          bodyFont: {
            size: 12
          },
          borderColor: 'rgba(255, 255, 255, 0.1)',
          borderWidth: 1,
          displayColors: true,
          callbacks: {
            label: function(context) {
              const label = context.dataset.label || ''
              const value = context.parsed.x || context.parsed.y
              return `${label}: ${value.toLocaleString()}`
            }
          }
        }
      },
      scales: {
        x: {
          beginAtZero: true,
          grid: {
            display: props.indexAxis === 'x',
            color: 'rgba(0, 0, 0, 0.05)'
          },
          ticks: {
            font: {
              size: 11,
              family: "'Inter', sans-serif"
            },
            callback: function(value) {
              if (typeof value === 'number') {
                if (value >= 1000000) {
                  return (value / 1000000).toFixed(1) + 'M'
                } else if (value >= 1000) {
                  return (value / 1000).toFixed(1) + 'K'
                }
                return value.toLocaleString()
              }
              return value
            }
          }
        },
        y: {
          beginAtZero: true,
          grid: {
            display: props.indexAxis === 'y',
            color: 'rgba(0, 0, 0, 0.05)'
          },
          ticks: {
            font: {
              size: 11,
              family: "'Inter', sans-serif"
            },
            callback: function(value) {
              if (typeof value === 'number') {
                if (value >= 1000000) {
                  return (value / 1000000).toFixed(1) + 'M'
                } else if (value >= 1000) {
                  return (value / 1000).toFixed(1) + 'K'
                }
                return value.toLocaleString()
              }
              return value
            }
          }
        }
      },
      interaction: {
        intersect: false,
        mode: 'index'
      }
    }
  })
}

onMounted(() => {
  createChart()
})

// Watch for data changes and recreate chart
watch(() => [props.labels, props.datasets], () => {
  createChart()
}, { deep: true })

onBeforeUnmount(() => {
  if (chartInstance) {
    chartInstance.destroy()
  }
})
</script>
