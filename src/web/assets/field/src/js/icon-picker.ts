import '../css/icon-picker.css';

import { allDefined } from '@verbb/plugin-kit-web/plugin-kit';

import { ICON_PICKER_PK_COMPONENTS } from './iconPickerPkComponents.js';
import { IconPickerInput } from './input/IconPickerInput';

const INPUT_SELECTOR = '[data-icon-picker-auto-mount="input"], .ipui-input-component';

const mountedInputs = new WeakSet<Element>();

const mountInput = (root: Element): void => {
    if (!(root instanceof HTMLElement) || mountedInputs.has(root)) {
        return;
    }

    new IconPickerInput(root).init();
    mountedInputs.add(root);
};

const mountAll = (scope: ParentNode = document): void => {
    if (scope instanceof HTMLElement && scope.matches(INPUT_SELECTOR)) {
        mountInput(scope);
    }

    scope.querySelectorAll(INPUT_SELECTOR).forEach(mountInput);
};

const startObserver = (): void => {
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    mountAll(node as HTMLElement);
                }
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
};

Craft.IconPicker = Craft.IconPicker || {};
Craft.IconPicker.mountAll = mountAll;
Craft.IconPicker.startAutoMountObserver = (): void => {
    if (Craft.IconPicker.__autoMountObserverStarted) {
        return;
    }

    Craft.IconPicker.__autoMountObserverStarted = true;
    startObserver();
};

const pkMatch = (tag: string): boolean => tag.startsWith('pk-');

// The register bundle defines ICON_PICKER_PK_COMPONENTS before this runs; gate on
// them so we never touch a `pk-*` element in field DOM before it has upgraded.
const bootstrap = async (): Promise<void> => {
    await allDefined({ match: pkMatch, additionalElements: [...ICON_PICKER_PK_COMPONENTS] });

    Craft.IconPicker.mountAll();
    Craft.IconPicker.startAutoMountObserver();
};

void bootstrap();
