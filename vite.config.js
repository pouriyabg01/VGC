import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        // Bound by name so the hot file matches the host the app is browsed
        // on. Left to itself Vite bound to [::1] only, and anything reaching
        // for 127.0.0.1 got nothing.
        host: 'localhost',
        watch: {
            // ignored replaces chokidar's defaults rather than adding to
            // them, so node_modules and vendor have to be named again here.
            // Without them the watcher tries to follow tens of thousands of
            // files and stops reporting the ones that matter.
            ignored: [
                '**/node_modules/**',
                '**/vendor/**',
                '**/.git/**',
                '**/storage/framework/views/**',
            ],
        },
    },
});


