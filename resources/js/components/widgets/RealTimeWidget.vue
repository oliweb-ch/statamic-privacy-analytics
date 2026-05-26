<template>
    <div class="ea-card ea-realtime-card">
        <div class="ea-flex ea-items-center ea-gap-2 ea-mb-4">
            <span class="ea-live-badge">LIVE</span>
            <h3 class="ea-font-bold">{{ t('realtime_visitors') }}</h3>
        </div>

        <div class="ea-grid ea-grid-cols-3 ea-gap-4 ea-mb-4">
            <div class="ea-realtime-stat">
                <p class="ea-text-secondary ea-text-xs">{{ t('active_sessions') }}</p>
                <p class="ea-text-xl ea-font-bold">{{ totals.active_sessions ?? 0 }}</p>
            </div>
            <div class="ea-realtime-stat">
                <p class="ea-text-secondary ea-text-xs">{{ t('active_visitors') }}</p>
                <p class="ea-text-xl ea-font-bold">{{ totals.active_visitors ?? 0 }}</p>
            </div>
            <div class="ea-realtime-stat">
                <p class="ea-text-secondary ea-text-xs">{{ t('page_views') }}</p>
                <p class="ea-text-xl ea-font-bold">{{ totals.page_views ?? 0 }}</p>
            </div>
        </div>

        <div class="ea-space-y-2">
            <div v-for="minutes in [5, 15, 30]" :key="minutes" class="ea-flex ea-justify-between ea-text-sm">
                <span class="ea-text-secondary">{{ t('last_x_min', { count: minutes }) }}</span>
                <span class="ea-font-medium">
                    {{ breakdowns[`last_${minutes}min`]?.active_sessions ?? 0 }} {{ t('sessions') }}
                    / {{ breakdowns[`last_${minutes}min`]?.page_views ?? 0 }} {{ t('views') }}
                </span>
            </div>
        </div>

        <p class="ea-text-xs ea-text-secondary ea-mt-3">{{ t('updates_every_30s') }}</p>
    </div>
</template>

<script setup>
const props = defineProps({
    totals:     { type: Object, default: () => ({}) },
    breakdowns: { type: Object, default: () => ({}) },
    t:          { type: Function, required: true },
})
</script>
