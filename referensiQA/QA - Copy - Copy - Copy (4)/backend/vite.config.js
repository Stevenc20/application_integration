import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/liForm.js',
                'resources/js/itemCheckForm.js',
                'resources/js/qprList.js',
                'resources/js/qprForm.js',
                'resources/js/userManagement.js',
                'resources/js/qcWorklist.js',
                'resources/js/approval.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: true,
        port: 5173,
        strictPort: true,
        cors: true,
        hmr: {
            // host: '192.168.100.106',
            // host: ' 10.27.239.159',
            // host: '192.168.8.105',
            host: '192.168.100.106',
            // host: '10.150.26.159',
            // host: '192.168.98.228',
            port: 5173,
            protocol: 'ws',
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});