import Dashboard from './components/Dashboard.vue'

Statamic.booting(() => {
    Statamic.$inertia.register('StatamicAnalytics/Dashboard', Dashboard)
})
