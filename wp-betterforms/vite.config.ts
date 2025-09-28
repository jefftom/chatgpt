import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
plugins: [react()],
build: {
emptyOutDir: false,
rollupOptions: {
input: {
admin: path.resolve(__dirname, 'src/admin/main.tsx'),
runtime: path.resolve(__dirname, 'src/frontend/runtime.ts'),
},
output: {
dir: path.resolve(__dirname, 'public/js'),
entryFileNames: (chunk) => `${chunk.name}.js`,
assetFileNames: (asset) => {
if (asset.name?.endsWith('.css')) {
return '../css/[name][extname]';
}
return '[name][extname]';
},
},
},
},
});
