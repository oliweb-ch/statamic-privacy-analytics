import Dashboard from './components/Dashboard.vue'

Statamic.booting(() => {
    Statamic.$inertia.register('EnhancedAnalytics/Dashboard', Dashboard)
})
