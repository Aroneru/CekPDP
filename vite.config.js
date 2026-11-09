import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            // Downloaded at build time and served from /build: pages make no
            // requests to a third-party font service.
            fonts: [
                bunny('Newsreader', {
                    weights: [400, 500, 600],
                    styles: ['normal', 'italic'],
                    preload: [{ weight: 500, style: 'normal' }],
                }),
                bunny('IBM Plex Sans', {
                    weights: [400, 500, 600],
                    preload: [{ weight: 400 }, { weight: 600 }],
                }),
                bunny('IBM Plex Mono', {
                    weights: [400, 500],
                    preload: false,
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
