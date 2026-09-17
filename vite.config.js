import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import * as Vue from 'vue'
import { rollup } from 'rollup'

// Replicate Statamic's externals plugin: use window.Vue instead of bundling Vue
const vueExports = Object.keys(Vue).filter(k => k !== 'default' && /^[a-zA-Z_$][a-zA-Z0-9_$]*$/.test(k))

function statamicExternals() {
    const VIRTUAL = '\0vue-external'
    return {
        name: 'statamic-externals',
        enforce: 'pre',
        resolveId(id) {
            if (id === 'vue') return VIRTUAL
        },
        load(id) {
            if (id === VIRTUAL) {
                return `const Vue = window.Vue;\nexport default Vue;\nexport const { ${vueExports.join(', ')} } = Vue;`
            }
        },
    }
}

/**
 * Plugin Vite — compile tracker-bundle.js en IIFE dans resources/dist/tracker.js.
 *
 * Format IIFE obligatoire : le script est injecté inline par ConsentBanner::tracker()
 * et ne peut pas contenir d'imports/exports (pas de type="module" → syntaxe ESM rejetée).
 * Rollup est déjà une dépendance de Vite, aucun paquet supplémentaire requis.
 */
function buildTrackerIIFE() {
    return {
        name: 'tracker-iife',
        apply: 'build',
        closeBundle: async () => {
            const bundle = await rollup({
                input: 'resources/js/tracker-bundle.js',
            });
            await bundle.write({
                file: 'resources/dist/tracker.js',
                format: 'iife',
                name: '__anlTracker',
            });
            await bundle.close();
        },
    };
}

export default defineConfig({
    plugins: [
        statamicExternals(),
        laravel({
            input: [
                'resources/js/consent-banner.js',
                'resources/js/statamic-analytics.js',
                'resources/css/statamic-analytics.css'
            ],
            publicDirectory: 'resources/dist',
        }),
        vue(),
        tailwindcss(),
        buildTrackerIIFE(),
    ],
});
