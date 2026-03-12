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

const ICON_PICKER_INPUT_SELECTOR = '[data-icon-picker-auto-mount="input"], .ipui-input-component';
const mountedRoots = new WeakSet();

const parseJsonDataAttr = (root, attrName, fallback = null) => {
    const raw = root?.getAttribute(attrName);

    if (!raw) {
        return fallback;
    }

    try {
        return JSON.parse(raw);
    } catch (e) {
        return fallback;
    }
};

const mountInputRoot = (root) => {
    if (!root || mountedRoots.has(root)) {
        return;
    }

    const settings = parseJsonDataAttr(root, 'data-settings', {});

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

    app.mount(root);
    mountedRoots.add(root);
};

const rootsForSelector = (scope, selector) => {
    if (!scope) {
        return [];
    }

    const roots = [];

    if (scope.matches && scope.matches(selector)) {
        roots.push(scope);
    }

    roots.push(...scope.querySelectorAll(selector));

    return roots;
};

Craft.IconPicker.mountAll = (scope = document) => {
    rootsForSelector(scope, ICON_PICKER_INPUT_SELECTOR).forEach((root) => {
        mountInputRoot(root);
    });
};

Craft.IconPicker.startAutoMountObserver = () => {
    if (Craft.IconPicker.__autoMountObserverStarted) {
        return;
    }

    Craft.IconPicker.__autoMountObserverStarted = true;

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType !== Node.ELEMENT_NODE) {
                    return;
                }

                Craft.IconPicker.mountAll(node);
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
    });
};

Craft.IconPicker.Input = Garnish.Base.extend({
    init(settings) {
        const root = document.querySelector(`#${settings.inputId}-field ${ICON_PICKER_INPUT_SELECTOR}`);
        mountInputRoot(root);
    },
});
$(document).ready(() => {
    Craft.IconPicker.mountAll(document);
    Craft.IconPicker.startAutoMountObserver();
});
