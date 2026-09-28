import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

// In Docker the dev server listens on all interfaces, while the browser loads
// assets from HMR_HOST (localhost by default; set it to the machine's LAN IP to
// test from a phone). File change events from a Windows/macOS host do not always
// reach the container, so WATCH_POLLING=true switches the watcher to polling.
const usePolling = process.env.WATCH_POLLING === 'true';
const port = Number(process.env.DEV_SERVER_PORT || 5173);

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        port,
        strictPort: true,
        hmr: {
            host: process.env.HMR_HOST || 'localhost',
        },
        watch: {
            usePolling,
            interval: 300,
            ignored: ['**/storage/**', '**/vendor/**', '**/node_modules/**'],
        },
    },
});
