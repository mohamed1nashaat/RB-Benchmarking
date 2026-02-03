<template>
  <div class="relative">
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, onBeforeUnmount } from 'vue'
import {
  Chart,
  DoughnutController,
  ArcElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js'

// Register Chart.js components
Chart.register(
  DoughnutController,
  ArcElement,
  Title,
  Tooltip,
  Legend
)

interface Props {
  labels: string[]
  data: number[]
  backgroundColor?: string[]
  title?: string
  height?: number
  cutout?: string
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  height: 300,
  cutout: '60%',
  backgroundColor: () => [
    'rgba(59, 130, 246, 0.8)',   // Blue
    'rgba(16, 185, 129, 0.8)',   // Green
    'rgba(168, 85, 247, 0.8)',   // Purple
    'rgba(251, 191, 36, 0.8)',   // Yellow
    'rgba(239, 68, 68, 0.8)',    // Red
    'rgba(236, 72, 153, 0.8)',   // Pink
  ]
})

const emit = defineEmits<{
  (e: 'segmentClick', data: { label: string; value: number; index: number }): void
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
    type: 'doughnut',
    data: {
      labels: props.labels,
      datasets: [{
        data: props.data,
        backgroundColor: props.backgroundColor,
        borderColor: '#ffffff',
        borderWidth: 2,
        hoverOffset: 10
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      onClick: (event, elements) => {
        if (elements.length > 0) {
          const index = elements[0].index
          const label = props.labels[index]
          const value = props.data[index]
          emit('segmentClick', { label, value, index })
        }
      },
      cutout: props.cutout,
      plugins: {
        legend: {
          display: true,
          position: 'right' as const,
          labels: {
            usePointStyle: true,
            padding: 15,
            font: {
              size: 12,
              family: "'Inter', sans-serif"
            },
            generateLabels: (chart) => {
              const data = chart.data
              if (data.labels && data.datasets.length) {
                return data.labels.map((label, i) => {
                  const value = data.datasets[0].data[i] as number
                  const total = (data.datasets[0].data as number[]).reduce((a, b) => a + b, 0)
                  const percentage = ((value / total) * 100).toFixed(1)

                  return {
                    text: `${label} (${percentage}%)`,
                    fillStyle: (data.datasets[0].backgroundColor as string[])[i],
                    hidden: false,
                    index: i
                  }
                })
              }
              return []
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
          callbacks: {
            label: function(context) {
              const label = context.label || ''
              const value = context.parsed
              const total = context.dataset.data.reduce((a: number, b: number) => a + b, 0)
              const percentage = ((value / total) * 100).toFixed(1)
              return `${label}: ${value.toLocaleString()} (${percentage}%)`
            }
          }
        }
      }
    }
  })
}

onMounted(() => {
  createChart()
})

// Watch for data changes and recreate chart
watch(() => [props.labels, props.data], () => {
  createChart()
}, { deep: true })

onBeforeUnmount(() => {
  if (chartInstance) {
    chartInstance.destroy()
  }
})
</script>
