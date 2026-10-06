// Build the app's local CSS and JavaScript assets; PHP remains the application backend.
import { defineConfig } from 'vite';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const projectRoot = dirname(fileURLToPath(import.meta.url));

export default defineConfig({
    configFile: false,
    root: projectRoot,
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
