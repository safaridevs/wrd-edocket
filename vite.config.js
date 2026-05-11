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
        cors: {
            origin: [
                'http://edocketlocal:8000',
                'http://localhost:8000',
                'http://127.0.0.1:8000',
            ],
        },
    },
});
