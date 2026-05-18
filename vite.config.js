import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import legacy from '@vitejs/plugin-legacy';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/operational/production-engine.js',
                'resources/js/ppc/planning.js'
            ],
            refresh: true,
        }),
        tailwindcss(),
        legacy({
            targets: ['defaults', 'not IE 11', 'Chrome 49', 'Safari 10'],
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        cors: true,
        hmr: {
            host: '192.168.8.100'
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    build: {
        cssTarget: 'chrome49',
        target: 'es2015',
    }
});