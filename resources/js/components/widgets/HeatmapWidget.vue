<template>
    <div class="ea-card">
        <h3 class="ea-font-bold">{{ t('activity_heatmap') }}</h3>
        <div class="ea-heatmap-wrapper">
            <!-- Hour labels row -->
            <div class="ea-heatmap-grid">
                <div class="ea-heatmap-label"></div>
                <div v-for="h in 24" :key="'h'+h" class="ea-heatmap-hour-label">{{ h - 1 }}h</div>
            </div>
            <!-- Day rows -->
            <div v-for="(dayLabel, dayIdx) in dayLabels" :key="dayIdx" class="ea-heatmap-grid">
                <div class="ea-heatmap-label">{{ dayLabel }}</div>
                <div
                    v-for="hour in 24"
                    :key="hour"
                    class="ea-heatmap-cell"
                    :title="`${dayLabel} ${hour - 1}h : ${getCount(dayIdx, hour - 1)}`"
                    :style="{ '--intensity': getIntensity(dayIdx, hour - 1) }"
                ></div>
            </div>
            <!-- Legend -->
            <div class="ea-heatmap-legend">
                <span class="ea-text-secondary ea-text-xs">{{ t('low_activity') }}</span>
                <div class="ea-heatmap-legend-gradient"></div>
                <span class="ea-text-secondary ea-text-xs">{{ t('high_activity') }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    data: { type: Array, default: () => [] },
    t: { type: Function, required: true },
})

// dayLabels: 0=Mon … 6=Sun
const dayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']

// Build lookup map: "day_hour" => count
const cellMap = computed(() => {
    const map = {}
    for (const item of props.data ?? []) {
        map[`${item.day}_${item.hour}`] = item.count
    }
    return map
})

const maxCount = computed(() => {
    if (!props.data?.length) return 1
    return Math.max(...props.data.map(i => i.count), 1)
})

function getCount(day, hour) {
    return cellMap.value[`${day}_${hour}`] ?? 0
}

function getIntensity(day, hour) {
    return (getCount(day, hour) / maxCount.value).toFixed(3)
}
</script>
