<template>
    <div class="ea-card">
        <h3 class="ea-font-bold">{{ t('platforms') }}</h3>
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
                label: props.t('visits'),
                data: [],
                backgroundColor: 'rgb(59, 130, 246)',
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
        },
    })
}

function updateChart(data) {
    if (!data || !data.length) return
    chart.data.labels = data.map(i => i.platform || 'Unknown')
    chart.data.datasets[0].data = data.map(i => i.total)
    chart.update()
}

watch(() => props.data, (val) => { if (val) updateChart(val) })

onMounted(() => {
    initChart()
    if (props.data?.length) updateChart(props.data)
})

onUnmounted(() => { chart?.destroy() })
</script>
