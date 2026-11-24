import path from 'path';
import AnalyzePlugin from 'rollup-plugin-analyzer';
import CompressionPlugin from 'vite-plugin-compression';

// Web-components field bundle (Plugin Kit v2), modelled on Hyper.
// Two entries: `pluginKit` registers the `pk-*` custom elements first, then the
// `icon-picker` app entry mounts field UI once those elements are defined.
export default {
    root: './src/web/assets',
    base: '',

    build: {
        outDir: 'field/dist',
        emptyOutDir: true,
        manifest: 'manifest.json',
        sourcemap: true,
        rollupOptions: {
            input: {
                pluginKit: '/field/src/js/plugin-kit-register.ts',
                'icon-picker': '/field/src/js/icon-picker.ts',
            },
        },
    },

    server: {
        origin: 'http://localhost:4005',
        hmr: { protocol: 'ws' },
    },

    plugins: [
        AnalyzePlugin({ summaryOnly: true, limit: 10 }),
        CompressionPlugin({ filter: /\.(js|mjs|json|css|map)$/i }),
    ],

    resolve: {
        alias: { '@': path.resolve('./src/web/assets/field/src') },
        // Resolve kit peers (lit, @floating-ui/dom, …) from this plugin's node_modules.
        preserveSymlinks: false,
        // Plugin Kit is linked locally until its next release, so keep one copy of its peers.
        dedupe: ['lit', '@verbb/plugin-kit-core', '@verbb/plugin-kit-icons', '@verbb/plugin-kit-web'],
    },

    optimizeDeps: { include: ['lodash-es', 'lit', '@lit-labs/virtualizer'] },
};
