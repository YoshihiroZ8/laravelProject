import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: { 
        host: '0.0.0.0', // Listen on all network interfaces
        hmr: {
            host: '192.168.100.233', // Your machine's IP on the network
            // protocol: 'ws', // Optional: specify WebSocket protocol if needed
        },
    },
});