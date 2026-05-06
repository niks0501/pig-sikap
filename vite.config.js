import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),   // ← Tailwind CSS v4
        vue({            // ← Enables Vue 3 Single File Components
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('chart.js') || id.includes('vue-chartjs')) return 'chart';
                    if (id.includes('node_modules/vue') || id.includes('node_modules/alpinejs')) return 'vendor';
                    if (id.includes('components/dashboard/')) return 'dashboard';
                    if (id.includes('components/pig-registry/')) return 'cycles';
                    if (id.includes('components/expenses/')) return 'expenses';
                    if (id.includes('components/workflow/')) return 'workflow';
                    if (id.includes('components/reports/')) return 'reports';
                    if (id.includes('components/sales/')) return 'sales';
                    if (id.includes('components/health/')) return 'health';
                    if (id.includes('components/admin/')) return 'admin';
                },
            },
        },
    },
});