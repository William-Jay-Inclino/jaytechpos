<script setup lang="ts">
import { ref, onMounted } from 'vue'

interface SalesChartData {
    labels: string[]
    data: number[]
}

interface Props {
    chartData: SalesChartData
}

const props = defineProps<Props>()

const canvasRef = ref<HTMLCanvasElement>()
const chartInstance = ref<any>(null)

// Simple chart implementation without external dependencies
onMounted(() => {
    drawChart()
})

function drawChart() {
    const canvas = canvasRef.value
    if (!canvas) return

    const ctx = canvas.getContext('2d')
    if (!ctx) return

    const { labels, data } = props.chartData
    
    // Canvas dimensions
    const width = canvas.width
    const height = canvas.height
    const padding = 60
    
    // Clear canvas
    ctx.clearRect(0, 0, width, height)
    
    // Set styles
    ctx.strokeStyle = 'hsl(var(--primary))'
    ctx.fillStyle = 'hsl(var(--primary))'
    ctx.lineWidth = 3
    ctx.font = '12px system-ui'
    
    // Calculate scales
    const maxValue = Math.max(...data) || 1
    const minValue = Math.min(...data) || 0
    const valueRange = maxValue - minValue || 1
    
    const chartWidth = width - padding * 2
    const chartHeight = height - padding * 2
    
    // Draw axes
    ctx.strokeStyle = 'hsl(var(--muted-foreground))'
    ctx.lineWidth = 1
    
    // Y-axis
    ctx.beginPath()
    ctx.moveTo(padding, padding)
    ctx.lineTo(padding, height - padding)
    ctx.stroke()
    
    // X-axis
    ctx.beginPath()
    ctx.moveTo(padding, height - padding)
    ctx.lineTo(width - padding, height - padding)
    ctx.stroke()
    
    // Draw grid lines and labels
    const ySteps = 5
    for (let i = 0; i <= ySteps; i++) {
        const y = padding + (chartHeight / ySteps) * i
        const value = maxValue - (valueRange / ySteps) * i
        
        // Grid line
        if (i > 0 && i < ySteps) {
            ctx.strokeStyle = 'hsl(var(--muted-foreground) / 0.2)'
            ctx.beginPath()
            ctx.moveTo(padding, y)
            ctx.lineTo(width - padding, y)
            ctx.stroke()
        }
        
        // Y-axis label
        ctx.fillStyle = 'hsl(var(--muted-foreground))'
        ctx.textAlign = 'right'
        ctx.textBaseline = 'middle'
        ctx.fillText(formatCurrency(value), padding - 10, y)
    }
    
    // Draw data line
    if (data.length > 0) {
        ctx.strokeStyle = 'hsl(var(--primary))'
        ctx.lineWidth = 3
        ctx.beginPath()
        
        data.forEach((value, index) => {
            const x = padding + (chartWidth / (data.length - 1)) * index
            const y = height - padding - ((value - minValue) / valueRange) * chartHeight
            
            if (index === 0) {
                ctx.moveTo(x, y)
            } else {
                ctx.lineTo(x, y)
            }
        })
        
        ctx.stroke()
        
        // Draw data points
        ctx.fillStyle = 'hsl(var(--primary))'
        data.forEach((value, index) => {
            const x = padding + (chartWidth / (data.length - 1)) * index
            const y = height - padding - ((value - minValue) / valueRange) * chartHeight
            
            ctx.beginPath()
            ctx.arc(x, y, 4, 0, 2 * Math.PI)
            ctx.fill()
        })
    }
    
    // Draw X-axis labels
    ctx.fillStyle = 'hsl(var(--muted-foreground))'
    ctx.textAlign = 'center'
    ctx.textBaseline = 'top'
    
    const maxLabelsToShow = Math.floor(chartWidth / 60) // Show label every 60 pixels
    const labelStep = Math.ceil(labels.length / maxLabelsToShow)
    
    labels.forEach((label, index) => {
        if (index % labelStep === 0) {
            const x = padding + (chartWidth / (labels.length - 1)) * index
            ctx.fillText(label, x, height - padding + 10)
        }
    })
}

function formatCurrency(value: number): string {
    if (value === 0) return '₱0'
    if (value >= 1000000) return `₱${(value / 1000000).toFixed(1)}M`
    if (value >= 1000) return `₱${(value / 1000).toFixed(1)}K`
    return `₱${Math.round(value)}`
}
</script>

<template>
    <div class="rounded-xl border border-sidebar-border/70 bg-card p-6 dark:border-sidebar-border">
        <div class="mb-6 flex items-center justify-between">
            <h3 class="text-lg font-semibold">Sales Trend (Last 30 Days)</h3>
        </div>
        
        <div class="relative h-80 w-full">
            <canvas
                ref="canvasRef"
                :width="800"
                :height="320"
                class="h-full w-full"
            />
        </div>
        
        <div v-if="chartData.data.length === 0" class="flex h-80 items-center justify-center text-muted-foreground">
            No sales data available
        </div>
    </div>
</template>