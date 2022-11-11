// CSS needs to be imported here as it's treated as a module
import '@/scss/style.scss';

// Accept HMR as per: https://vitejs.dev/guide/api-hmr.html
if (import.meta.hot) {
    import.meta.hot.accept();
}

//
// Start Vue Apps
//

if (typeof Craft.IconPicker === typeof undefined) {
    Craft.IconPicker = {};
}

import { createVueApp } from './config';

import IconPickerInput from './components/IconPickerInput.vue';

Craft.IconPicker.Input = Garnish.Base.extend({
    init(settings) {
        const app = createVueApp({
            components: {
                IconPickerInput,
            },

            data() {
                return {
                    settings,
                };
            },
        });

        app.mount(`#${settings.inputId}-field .input`);
    },
});

// Re-broadcast the custom `vite-script-loaded` event so that we know that this module has loaded
// Needed because when <script> tags are appended to the DOM, the `onload` handlers
// are not executed, which happens in the field Settings page, and in slideouts
// Do this after the document is ready to ensure proper execution order
$(document).ready(() => {
    // Create a global-loaded flag when switching entry types. This won't be fired multiple times.
    Craft.IconPickerReady = true;

    document.dispatchEvent(new CustomEvent('vite-script-loaded', { detail: { path: 'field/src/js/icon-picker.js' } }));
});
