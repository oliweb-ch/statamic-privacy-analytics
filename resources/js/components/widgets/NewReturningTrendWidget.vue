<template>
    <div class="ea-card">
        <h3 class="ea-font-bold">{{ t('new_vs_returning_trend') }}</h3>
        <div class="ea-chart-wrapper">
            <canvas ref="chartEl"></canvas>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { Chart } from 'chart.js/auto'

const props = defineProps({
    data: { type: Array, default: () => [] },
    t: { type: Function, required: true },
})

const chartEl = ref(null)
let chart = null

function initChart() {
    chart = new Chart(chartEl.value, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: props.t('new_visitors'),
                    data: [],
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.15)',
                    fill: true,
                    tension: 0.3,
                },
                {
                    label: props.t('returning_visitors'),
                    data: [],
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.15)',
                    fill: true,
                    tension: 0.3,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            scales: {
                y: { stacked: false },
            },
        },
    })
}

function updateChart(data) {
    if (!data?.length) return
    chart.data.labels = data.map(i => i.date)
    chart.data.datasets[0].data = data.map(i => i.new_visitors)
    chart.data.datasets[1].data = data.map(i => i.returning_visitors)
    chart.update()
}

watch(() => props.data, (val) => { if (val) updateChart(val) })

onMounted(() => {
    initChart()
    if (props.data?.length) updateChart(props.data)
})

onUnmounted(() => { chart?.destroy() })
</script>
