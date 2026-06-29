import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/portal/offline-tsr.js',
            ],
            refresh: true,
        }),
    ],
    // The offline TSR queue uses the browser's IndexedDB via Dexie.
    // We serve Dexie from /vendor/dexie/dexie.js in the public dir
    // (copied from node_modules) so the offline module can `import`
    // it without a bundler. No extra aliases needed.
});
