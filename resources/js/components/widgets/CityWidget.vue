<template>
    <div class="ea-card">
        <h3 class="ea-font-bold">{{ t('top_cities') }}</h3>
        <table class="ea-table">
            <thead>
                <tr>
                    <th>{{ t('city') }}</th>
                    <th>{{ t('country') }}</th>
                    <th class="ea-text-right">{{ t('visits') }}</th>
                    <th style="width: 120px;"></th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="!data || data.length === 0">
                    <td colspan="4" class="ea-text-center">{{ t('loading_data') }}</td>
                </tr>
                <tr v-for="row in data" :key="row.city + row.country_name">
                    <td>{{ row.city }}</td>
                    <td class="ea-text-secondary">{{ row.country_name }}</td>
                    <td class="ea-text-right">{{ row.total.toLocaleString() }}</td>
                    <td>
                        <div class="ea-progress-bar">
                            <div class="ea-progress-fill" :style="{ width: barWidth(row.total) }"></div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    data: { type: Array, default: () => [] },
    t: { type: Function, required: true },
})

const maxTotal = computed(() => {
    if (!props.data?.length) return 1
    return Math.max(...props.data.map(r => r.total))
})

function barWidth(total) {
    return ((total / maxTotal.value) * 100).toFixed(1) + '%'
}
</script>
