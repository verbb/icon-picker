import '../css/icon-picker.css';

import { allDefined } from '@verbb/plugin-kit-web/plugin-kit';

import { ICON_PICKER_PK_COMPONENTS } from './iconPickerPkComponents.js';
import { IconPickerInput } from './input/IconPickerInput';

const INPUT_SELECTOR = '[data-icon-picker-auto-mount="input"], .ipui-input-component';

const mountedInputs = new WeakSet<Element>();
/** Roots seen before Plugin Kit tags are defined (slideout HTML often lands first). */
const queuedInputs = new Set<HTMLElement>();
let pkReady = false;

const mountInput = (root: Element): void => {
    if (!(root instanceof HTMLElement) || mountedInputs.has(root)) {
        return;
    }

    // Craft slideouts append field HTML, then head/body module scripts. Those modules
    // are deferred — MutationObserver can see roots before `allDefined` finishes.
    if (!pkReady) {
        queuedInputs.add(root);
        return;
    }

    try {
        new IconPickerInput(root).init();
        mountedInputs.add(root);
        queuedInputs.delete(root);
    } catch (error) {
        console.error('[icon-picker] Failed to mount field input', error);
    }
};

const mountAll = (scope: ParentNode = document): void => {
    if (scope instanceof HTMLElement && scope.matches(INPUT_SELECTOR)) {
        mountInput(scope);
    }

    scope.querySelectorAll(INPUT_SELECTOR).forEach(mountInput);
};

const flushQueue = (): void => {
    for (const root of [...queuedInputs]) {
        if (!root.isConnected) {
            queuedInputs.delete(root);
            continue;
        }

        mountInput(root);
    }
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

/**
 * Craft CpScreenSlideout: set content HTML → appendHead/BodyHtml (modules) →
 * initUiElements → trigger('load'). Modules often run *after* that, so wrap
 * initUiElements for a second-chance mount once our bundle is alive.
 */
const hookCraftSlideoutMount = (): void => {
    if (typeof Craft === 'undefined') {
        return;
    }

    if (typeof Craft.initUiElements === 'function' && !Craft.__iconPickerInitUiWrapped) {
        Craft.__iconPickerInitUiWrapped = true;
        const original = Craft.initUiElements.bind(Craft);
        Craft.initUiElements = (element?: unknown) => {
            original(element);
            // WeakSet makes repeat scans cheap; covers slideouts + nested UI refreshes.
            mountAll();
        };
    }

    const Slideout = Craft.CpScreenSlideout;
    if (Slideout && typeof Garnish !== 'undefined' && !Craft.__iconPickerSlideoutLoadHooked) {
        Craft.__iconPickerSlideoutLoadHooked = true;
        Garnish.on?.(Slideout, 'load', () => {
            mountAll();
        });
    }
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

// Observe immediately so slideout roots arriving during `allDefined` are queued,
// not missed (observer started only after await used to drop those mutations).
Craft.IconPicker.startAutoMountObserver();
hookCraftSlideoutMount();

const bootstrap = async (): Promise<void> => {
    // Wait only for *our* tags. Default `match: pk-*` scans the whole CP for any
    // undefined pk-* (other plugins / FOUCE) and can stall mount until DevTools
    // slows the page enough for them to register — classic intermittent slideout blank.
    await allDefined({
        match: () => false,
        additionalElements: [...ICON_PICKER_PK_COMPONENTS],
    });

    pkReady = true;
    hookCraftSlideoutMount();
    flushQueue();
    mountAll();
};

void bootstrap();
