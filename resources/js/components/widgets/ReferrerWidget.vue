<template>
    <div class="ea-card">
        <h3 class="ea-font-bold">{{ t('traffic_sources') }}</h3>
        <div class="ea-grid ea-grid-cols-2 ea-gap-4">
            <div class="ea-chart-wrapper">
                <canvas ref="chartEl"></canvas>
            </div>
            <div>
                <table class="ea-table">
                    <thead>
                        <tr>
                            <th>{{ t('domain') }}</th>
                            <th class="ea-text-right">{{ t('visits') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in topDomains" :key="row.domain">
                            <td class="max-w-xs truncate">{{ row.domain }}</td>
                            <td class="ea-text-right">{{ row.total.toLocaleString() }}</td>
                        </tr>
                        <tr v-if="topDomains.length === 0">
                            <td colspan="2" class="ea-text-center">{{ t('loading_data') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { Chart } from 'chart.js/auto'

const props = defineProps({
    data: { type: Object, default: null },
    t: { type: Function, required: true },
})

const chartEl = ref(null)
let chart = null

const topDomains = ref([])

const SOURCE_COLORS = {
    direct:   'rgb(59, 130, 246)',
    search:   'rgb(16, 185, 129)',
    social:   'rgb(251, 191, 36)',
    referral: 'rgb(236, 72, 153)',
}

function initChart() {
    chart = new Chart(chartEl.value, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{ data: [], backgroundColor: [] }],
        },
        options: { responsive: true, maintainAspectRatio: false },
    })
}

function updateChart(data) {
    if (!data) return
    if (data.sources) {
        chart.data.labels = data.sources.map(s => props.t('source_' + s.source) || s.source)
        chart.data.datasets[0].data = data.sources.map(s => s.total)
        chart.data.datasets[0].backgroundColor = data.sources.map(s => SOURCE_COLORS[s.source] ?? 'rgb(156, 163, 175)')
        chart.update()
    }
    topDomains.value = data.top_domains ?? []
}

watch(() => props.data, (val) => { if (val) updateChart(val) })

onMounted(() => {
    initChart()
    if (props.data) updateChart(props.data)
})

onUnmounted(() => { chart?.destroy() })
</script>
