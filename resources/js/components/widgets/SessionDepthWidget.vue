<template>
    <div class="ea-card">
        <h3 class="ea-font-bold">{{ t('session_depth') }}</h3>
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
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: props.t('sessions'),
                data: [],
                backgroundColor: [
                    'rgb(59, 130, 246)',
                    'rgb(16, 185, 129)',
                    'rgb(251, 191, 36)',
                    'rgb(236, 72, 153)',
                    'rgb(124, 58, 237)',
                ],
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
            },
        },
    })
}

function updateChart(data) {
    if (!data?.length) return
    chart.data.labels = data.map(i => i.label + ' ' + props.t('pages_abbr'))
    chart.data.datasets[0].data = data.map(i => i.count)
    chart.update()
}

watch(() => props.data, (val) => { if (val) updateChart(val) })

onMounted(() => {
    initChart()
    if (props.data?.length) updateChart(props.data)
})

onUnmounted(() => { chart?.destroy() })
</script>
