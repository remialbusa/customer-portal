import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/guest.js',
                'resources/js/portal/offline-tsr.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    // Split vendor libs (pusher-js ~45KB + laravel-echo ~5KB)
                    // into a cacheable chunk. These rarely change, so the
                    // browser caches them independently of app code changes.
                    if (id.includes('node_modules/pusher-js') ||
                        id.includes('node_modules/laravel-echo')) {
                        return 'vendor-realtime';
                    }
                },
            },
        },
    },
    // The offline TSR queue uses the browser's IndexedDB via Dexie.
    // We serve Dexie from /vendor/dexie/dexie.js in the public dir
    // (copied from node_modules) so the offline module can `import`
    // it without a bundler. No extra aliases needed.
});
