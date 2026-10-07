// Build Tailwind utilities, local Font Awesome fonts, and shared browser code for plain PHP pages.
import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const projectRoot = dirname(fileURLToPath(import.meta.url));

export default defineConfig({
    configFile: false,
    root: projectRoot,
    plugins: [tailwindcss()],
    build: {
        outDir: resolve(projectRoot, 'assets/compiled'),
        emptyOutDir: true,
        rollupOptions: {
            input: resolve(projectRoot, 'resources/js/app.js'),
            output: {
                entryFileNames: 'app.js',
                assetFileNames: (assetInfo) => assetInfo.name?.endsWith('.css') ? 'app.css' : '[name][extname]',
            },
        },
    },
});
