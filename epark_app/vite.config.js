import { realpathSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// Resolve Windows junctions before Vite and Laravel calculate manifest keys.
process.chdir(realpathSync.native(fileURLToPath(new URL('.', import.meta.url))));

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
