import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'node:path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.tsx',
            ],
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: [
            { find: '@/hooks/useLanguage', replacement: path.resolve(__dirname, 'resources/js/shims/useLanguage.ts') },
            { find: '@', replacement: path.resolve(__dirname, 'react/src') },
            { find: 'react-router-dom', replacement: path.resolve(__dirname, 'resources/js/shims/react-router-dom.tsx') },
            { find: '#resources', replacement: path.resolve(__dirname, 'resources/js') },
        ],
    },
    assetsInclude: ['**/*.md'],
});
