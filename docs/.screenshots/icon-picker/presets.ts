import type {
    ScreenshotStep,
    ScreenshotTarget,
    ScreenshotViewport,
} from '@verbb/docs-screenshots/types';
import {
    createCpDetailViewPreset as createBaseCpDetailViewPreset,
    createCpFocusedRegionPreset as createBaseCpFocusedRegionPreset,
    createCpFullScreenPreset as createBaseCpFullScreenPreset,
    createCpModalPreset as createBaseCpModalPreset,
} from '@verbb/docs-screenshots/presets';

// Plugin-local preset layer. Generic capture math lives in @verbb/docs-screenshots;
// this file only adds Icon-Picker-specific CP chrome cleanup + framing steps. As the
// Phase 1 field UI lands, add promo-crop steps here (model on Hyper's presets.ts).

type CpPresetOptions = {
    selector?: string;
    viewport?: ScreenshotViewport;
    padding?: NonNullable<Extract<ScreenshotTarget, { type: 'selector' }>['padding']>;
    hidePlaceholder?: boolean;
};

/** Craft CP page wash — use when the shot should read as in-CP, not a cutout. */
export const ICON_PICKER_CP_GRAY = '#f3f7fc';

const scrollResetSelectors = [
    'html',
    'body',
    '#content-container',
    '#main-content',
    '#content',
    '.content-pane',
];

function buildCleanupCss({ hidePlaceholder = true }: { hidePlaceholder?: boolean }): string {
    const rules = [
        'craft-global-sidebar, footer#global-footer { display: none !important; }',
        'craft-global-sidebar { width: 0 !important; min-width: 0 !important; flex: 0 0 0 !important; }',
        '#global-header * { display: none !important; }',
        '#details-container { position: static !important; }',
        'body.fixed-header #header { position: static !important; top: auto !important; }',
        'body.fixed-header #content-container { padding-top: 0 !important; }',
        '#content-container, #main-content, #content { max-width: none !important; }',
        '#content-container { padding: 24px !important; }',
        '#main-content { padding-top: 0 !important; }',
        '#page-container, #content-container, #main-content, #content, .content-pane { left: 0 !important; margin-left: 0 !important; }',
        'html, body, * { scrollbar-width: none !important; -ms-overflow-style: none !important; }',
        'html::-webkit-scrollbar, body::-webkit-scrollbar, *::-webkit-scrollbar { display: none !important; width: 0 !important; height: 0 !important; }',
    ];

    if (hidePlaceholder) {
        rules.push('.cp-placeholder, .placeholder { display: none !important; }');
    }

    return rules.join('\n');
}

/** Strip Craft chrome (global sidebar/header/footer, scrollbars) for focused field crops. */
export function createIconPickerCleanupStep({ hidePlaceholder = true }: { hidePlaceholder?: boolean } = {}): ScreenshotStep {
    const css = buildCleanupCss({ hidePlaceholder });

    return {
        type: 'evaluate',
        expression: `
            (() => {
                const styleId = 'icon-picker-docs-screenshot-cleanup';
                let style = document.getElementById(styleId);

                if (!(style instanceof HTMLStyleElement)) {
                    style = document.createElement('style');
                    style.id = styleId;
                    document.head.appendChild(style);
                }

                style.textContent = ${JSON.stringify(css)};

                ${JSON.stringify(scrollResetSelectors)}.forEach((selector) => {
                    document.querySelectorAll(selector).forEach((element) => {
                        if (element instanceof HTMLElement) {
                            element.scrollTop = 0;
                            element.scrollLeft = 0;
                        }
                    });
                });

                window.scrollTo(0, 0);
            })();
        `,
    };
}

/**
 * Pin the Icon Picker field (label + open grid) onto a fixed canvas.
 * Moves the live `.field` node so pk-popover / shadow DOM stay intact; forces the
 * open pane to flow under the input instead of floating outside the crop.
 *
 * Default framing matches docs cutouts: flush on CP grey (no white card / inset pad).
 */
export function createIconPickerFieldPromoCropStep({
    width = 720,
    padding = 0,
    background = ICON_PICKER_CP_GRAY,
    openPane = true,
}: {
    width?: number;
    padding?: number;
    background?: string;
    /** When false, only stage the closed field (open the pane afterward). */
    openPane?: boolean;
} = {}): ScreenshotStep {
    return {
        type: 'evaluate',
        expression: `
            (() => {
                document.getElementById('icon-picker-docs-screenshot-frame')?.remove();
                document.getElementById('icon-picker-docs-screenshot-stage')?.remove();

                const field = document.querySelector('.field:has(.ipui-input-component)');
                if (!(field instanceof HTMLElement)) {
                    throw new Error('Icon Picker field not found for promo crop.');
                }

                // Craft field chrome (status / ellipsis) — keep the cutout to label + input + pane.
                field.querySelectorAll('.status-label, .status, .menubtn, .spacer').forEach((el) => {
                    if (el instanceof HTMLElement) {
                        el.style.setProperty('display', 'none', 'important');
                    }
                });

                const openPane = ${openPane ? 'true' : 'false'};
                if (openPane) {
                    const pane = document.querySelector('.ipui-icons-pane');
                    if (!(pane instanceof HTMLElement)) {
                        throw new Error('Icon Picker open pane not found for promo crop.');
                    }

                    // Clear ::part(panel) shadow — host styles alone leave a double-border look.
                    const styleId = 'icon-picker-docs-screenshot-pane-chrome';
                    let style = document.getElementById(styleId);
                    if (!(style instanceof HTMLStyleElement)) {
                        style = document.createElement('style');
                        style.id = styleId;
                        document.head.appendChild(style);
                    }
                    style.textContent = [
                        '.ipui-icons-popover::part(panel) {',
                        '  box-shadow: none !important;',
                        '  background: transparent !important;',
                        '  padding: 0 !important;',
                        '  border: none !important;',
                        '}',
                    ].join('\\n');

                    const popover = pane.closest('pk-popover');
                    if (popover instanceof HTMLElement) {
                        popover.style.setProperty('position', 'static', 'important');
                        popover.style.setProperty('inset', 'auto', 'important');
                        popover.style.setProperty('transform', 'none', 'important');
                        popover.style.setProperty('display', 'block', 'important');
                    }
                    pane.style.setProperty('position', 'static', 'important');
                    pane.style.setProperty('max-height', '360px', 'important');
                    pane.style.setProperty('width', '100%', 'important');
                    pane.style.setProperty('margin-top', '6px', 'important');
                    // Single hairline only — panel ::part shadow is cleared above.
                    pane.style.setProperty('box-shadow', 'none', 'important');
                    pane.style.setProperty('border', '1px solid rgba(96,125,159,.25)', 'important');
                    pane.style.setProperty('border-radius', '8px', 'important');
                    pane.style.setProperty('background', '#fff', 'important');
                }

                const inset = ${padding};
                const frameWidth = ${width};
                const stageBackground = ${JSON.stringify(background)};

                const stage = document.createElement('div');
                stage.id = 'icon-picker-docs-screenshot-stage';
                stage.style.cssText = [
                    'position:fixed',
                    'left:0',
                    'top:0',
                    'width:' + frameWidth + 'px',
                    'z-index:2147483640',
                    'background:' + stageBackground,
                    'padding:' + inset + 'px',
                    'box-sizing:border-box',
                    'overflow:hidden',
                ].join(';');

                // Transparent frame — no white card / inset pad (docs cutout sits on CP grey).
                const frame = document.createElement('div');
                frame.id = 'icon-picker-docs-screenshot-frame';
                frame.style.cssText = [
                    'background:transparent',
                    'border-radius:0',
                    'box-sizing:border-box',
                    'padding:0',
                    'overflow:hidden',
                ].join(';');

                frame.appendChild(field);
                field.style.margin = '0';
                field.style.maxWidth = '100%';

                stage.appendChild(frame);
                document.body.appendChild(stage);

                const box = frame.getBoundingClientRect();
                stage.style.height = Math.ceil(box.height + inset * 2) + 'px';

                document.documentElement.style.background = stageBackground;
                document.body.style.background = stageBackground;
                Array.from(document.body.children).forEach((child) => {
                    if (
                        child instanceof HTMLElement
                        && child.id !== 'icon-picker-docs-screenshot-stage'
                    ) {
                        child.style.setProperty('display', 'none', 'important');
                    }
                });
            })();
        `,
    };
}

export function createCpFocusedRegionPreset(options: CpPresetOptions = {}) {
    const preset = createBaseCpFocusedRegionPreset(options);

    return {
        ...preset,
        steps: [
            createIconPickerCleanupStep({ hidePlaceholder: options.hidePlaceholder }),
            ...preset.steps,
        ] satisfies ScreenshotStep[],
    };
}

export function createCpFullScreenPreset(options: CpPresetOptions = {}) {
    const preset = createBaseCpFullScreenPreset(options);

    return {
        ...preset,
        steps: [
            createIconPickerCleanupStep({ hidePlaceholder: options.hidePlaceholder }),
            ...preset.steps,
        ] satisfies ScreenshotStep[],
    };
}

export function createCpModalPreset(options: CpPresetOptions = {}) {
    return createBaseCpModalPreset(options);
}

export function createCpDetailViewPreset(options: CpPresetOptions = {}) {
    const preset = createBaseCpDetailViewPreset(options);

    return {
        ...preset,
        steps: [
            createIconPickerCleanupStep({ hidePlaceholder: options.hidePlaceholder }),
            ...preset.steps,
        ] satisfies ScreenshotStep[],
    };
}
