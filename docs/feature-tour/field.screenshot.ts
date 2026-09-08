import { defineScreenshotScenario } from '@verbb/docs-screenshots/api';
import { seedIconPickerDocsFixture } from '../.screenshots/icon-picker/fixtures';
import {
    createIconPickerCleanupStep,
    createIconPickerFieldPromoCropStep,
} from '../.screenshots/icon-picker/presets';

// Capture the Icon Picker field with the popover open (search + icon grid).
let entryEditRoute = '/admin/entries';

const viewport = {
    width: 1100,
    height: 900,
    deviceScaleFactor: 2,
};

export default defineScreenshotScenario({
    id: 'feature-tour-field',
    output: '_screenshots/feature-tour/field.png',
    route: () => entryEditRoute,
    viewport,
    async setup(context) {
        const fixture = await seedIconPickerDocsFixture(context);
        entryEditRoute = fixture.entryEditRoute;
    },
    waitFor: [
        { type: 'selector', selector: '.ipui-input-component', state: 'visible' },
    ],
    preSteps: [
        createIconPickerCleanupStep(),
        // Stage the field first, then open — moving a live open pk-popover kills the pane.
        // Content width 572 = 10×56px cells + 10px scroller pad + 2px pane border.
        // Stage border-box = content + 20px white pad each side.
        createIconPickerFieldPromoCropStep({ padding: 20, width: 612, background: '#ffffff', openPane: false }),
        { type: 'click', selector: '#icon-picker-docs-screenshot-frame .ipui-icon-input' },
        { type: 'wait', waitFor: { type: 'selector', selector: '.ipui-icons-pane', state: 'visible', timeout: 30000 } },
        { type: 'wait', waitFor: { type: 'selector', selector: '.ipui-icon-wrap', state: 'visible', timeout: 60000 } },
        // Flow the open pane under the input; size scroller to whole icon rows only.
        {
            type: 'evaluate',
            expression: `
                (() => {
                    const pane = document.querySelector('.ipui-icons-pane');
                    const stage = document.getElementById('icon-picker-docs-screenshot-stage');
                    const frame = document.getElementById('icon-picker-docs-screenshot-frame');
                    const scroller = document.querySelector('.ipui-icons-scroller');
                    if (!(pane instanceof HTMLElement) || !(stage instanceof HTMLElement) || !(frame instanceof HTMLElement)) {
                        throw new Error('Missing pane/stage after open.');
                    }

                    // ::part(panel) still carries --pk-shadow-popup from icon-picker.css; host/pane
                    // styles never clear it, so shadow ring + pane hairline read as a double border.
                    const styleId = 'icon-picker-docs-screenshot-pane-chrome';
                    let style = document.getElementById(styleId);
                    if (!(style instanceof HTMLStyleElement)) {
                        style = document.createElement('style');
                        style.id = styleId;
                        document.head.appendChild(style);
                    }
                    // Transparent panel shell so margin-top gap is stage white (not a
                    // second frame above the pane hairline). Match input width so the
                    // pane isn’t inset on the left.
                    const inputEl = frame.querySelector('.ipui-icon-input') || frame.querySelector('.ipui-input-component');
                    const inputWidth = inputEl instanceof HTMLElement
                        ? Math.ceil(inputEl.getBoundingClientRect().width)
                        : 0;
                    const panelWidthRule = inputWidth > 0
                        ? ('  width: ' + inputWidth + 'px !important;\\n  max-width: none !important;\\n  min-width: 0 !important;')
                        : '';
                    style.textContent = [
                        '#icon-picker-docs-screenshot-stage .ipui-icons-popover::part(panel) {',
                        '  box-shadow: none !important;',
                        '  background: transparent !important;',
                        '  padding: 0 !important;',
                        '  border: none !important;',
                        '  margin: 0 !important;',
                        // Floating-ui leaves inset/transform on the panel; static-ize so it
                        // flows under the input instead of sitting with a leftover left offset.
                        '  position: static !important;',
                        '  inset: auto !important;',
                        '  left: auto !important;',
                        '  top: auto !important;',
                        '  transform: none !important;',
                        panelWidthRule,
                        '}',
                        '#icon-picker-docs-screenshot-stage .field,',
                        '#icon-picker-docs-screenshot-stage .heading,',
                        '#icon-picker-docs-screenshot-stage .input {',
                        '  margin: 0 !important;',
                        '  padding: 0 !important;',
                        '}',
                    ].filter(Boolean).join('\\n');

                    const popover = pane.closest('pk-popover');
                    if (popover instanceof HTMLElement) {
                        popover.style.setProperty('position', 'static', 'important');
                        popover.style.setProperty('inset', 'auto', 'important');
                        popover.style.setProperty('transform', 'none', 'important');
                        popover.style.setProperty('display', 'block', 'important');
                        popover.style.setProperty('box-shadow', 'none', 'important');
                        popover.style.setProperty('border', 'none', 'important');
                        popover.style.setProperty('background', 'transparent', 'important');
                        popover.style.setProperty('padding', '0', 'important');
                        popover.style.setProperty('margin', '0', 'important');
                    }

                    // Prefer measured cell height so we never clip mid-icon (CSS default 56px).
                    const sample = document.querySelector('.ipui-icon-wrap');
                    const cellSize = sample instanceof HTMLElement
                        ? Math.ceil(sample.getBoundingClientRect().height)
                        : 56;
                    const gapPx = 0; // unlabeled grid
                    const cols = 10; // exact fit — avoids a ragged right gutter in the cutout
                    const rows = 3;
                    const scrollerPad = 10; // .ipui-icons-scroller padding 5px × 2
                    // Pane is border-box with a 1px hairline — clientWidth must be exact for cols.
                    // With the tall viewport above, layout uses the no-gutter path: 10×56 + 10.
                    const gridClient = cols * cellSize + Math.max(0, cols - 1) * gapPx + scrollerPad;
                    const paneOuter = gridClient + 2;
                    const scrollerHeight = rows * cellSize + Math.max(0, rows - 1) * gapPx + scrollerPad;

                    // Reparent out of pk-popover — floating-ui panel overflow:hidden clips any
                    // left realignment and leaves a CP-grey gutter beside the pane.
                    const inputAnchor = frame.querySelector('.ipui-input-component') || frame.querySelector('.input') || frame;
                    if (pane.parentElement !== inputAnchor) {
                        inputAnchor.appendChild(pane);
                    }
                    // Empty host would still reserve layout / paint chrome after reparent.
                    if (popover instanceof HTMLElement) {
                        popover.style.setProperty('display', 'none', 'important');
                    }

                    // Pin field + pane to an exact N-column grid width (search matches pane).
                    frame.style.width = paneOuter + 'px';
                    frame.style.maxWidth = paneOuter + 'px';
                    const fieldEl = frame.querySelector('.field') || frame;
                    if (fieldEl instanceof HTMLElement) {
                        fieldEl.style.width = paneOuter + 'px';
                        fieldEl.style.maxWidth = paneOuter + 'px';
                    }

                    pane.style.setProperty('position', 'static', 'important');
                    pane.style.setProperty('max-height', 'none', 'important');
                    pane.style.setProperty('height', 'auto', 'important');
                    pane.style.setProperty('box-sizing', 'border-box', 'important');
                    pane.style.setProperty('width', paneOuter + 'px', 'important');
                    frame.style.setProperty('--ipui-popover-width', paneOuter + 'px');
                    pane.style.setProperty('margin', '4px 0 0 0', 'important');
                    pane.style.setProperty('left', 'auto', 'important');
                    // One hairline only (panel shadow cleared above).
                    pane.style.setProperty('box-shadow', 'none', 'important');
                    pane.style.setProperty('outline', 'none', 'important');
                    pane.style.setProperty('border', '1px solid rgba(96,125,159,.25)', 'important');
                    // Square outer corners — soft radii would anti-alias into the white stage pad.
                    pane.style.setProperty('border-radius', '0', 'important');
                    pane.style.setProperty('background', '#fff', 'important');
                    pane.style.setProperty('overflow', 'hidden', 'important');

                    if (scroller instanceof HTMLElement) {
                        scroller.style.setProperty('max-height', scrollerHeight + 'px', 'important');
                        scroller.style.setProperty('height', scrollerHeight + 'px', 'important');
                        scroller.style.setProperty('min-height', scrollerHeight + 'px', 'important');
                        scroller.style.setProperty('overflow', 'hidden', 'important');
                    }

                    window.dispatchEvent(new Event('resize'));

                    // Resize rewrites scroller height for the full icon set — clamp again to 3 rows.
                    if (scroller instanceof HTMLElement) {
                        scroller.style.setProperty('max-height', scrollerHeight + 'px', 'important');
                        scroller.style.setProperty('height', scrollerHeight + 'px', 'important');
                        scroller.style.setProperty('min-height', scrollerHeight + 'px', 'important');
                        scroller.style.setProperty('overflow', 'hidden', 'important');
                    }

                    frame.style.overflow = 'visible';
                    frame.style.padding = '0';
                    frame.style.margin = '0';
                    stage.style.overflow = 'hidden';
                    stage.style.background = '#ffffff';
                    // Measure flush first; white breathing room comes from content-box padding
                    // after (avoids focus-ring / border-box eating the right inset).
                    stage.style.padding = '0';
                    stage.style.boxSizing = 'content-box';

                    const inset = 20;
                    return new Promise((resolve) => {
                        requestAnimationFrame(() => {
                            requestAnimationFrame(() => {
                                const icons = Array.from(document.querySelectorAll('.ipui-icon-wrap'))
                                    .filter((el) => el instanceof HTMLElement);
                                const heading = frame.querySelector('.heading label')
                                    || frame.querySelector('.heading .heading-text')
                                    || frame.querySelector('.heading')
                                    || frame.querySelector('label');
                                const instructions = frame.querySelector('.instructions');
                                const input = frame.querySelector('.ipui-icon-input') || frame.querySelector('.ipui-input-component');

                                // Keep pane + search locked to the exact 10-col width.
                                pane.style.setProperty('width', paneOuter + 'px', 'important');
                                if (input instanceof HTMLElement) {
                                    input.style.width = paneOuter + 'px';
                                    input.style.maxWidth = paneOuter + 'px';
                                }
                                if (scroller instanceof HTMLElement) {
                                    scroller.style.setProperty('height', scrollerHeight + 'px', 'important');
                                    scroller.style.setProperty('min-height', scrollerHeight + 'px', 'important');
                                    scroller.style.setProperty('max-height', scrollerHeight + 'px', 'important');
                                    scroller.style.setProperty('overflow', 'hidden', 'important');
                                }

                                const parts = [heading, instructions, input, pane].filter((el) => el instanceof HTMLElement);
                                const boxes = parts.map((el) => el.getBoundingClientRect());
                                if (!boxes.length) {
                                    resolve(true);
                                    return;
                                }

                                let left = Math.min(...boxes.map((b) => b.left));
                                let top = Math.min(...boxes.map((b) => b.top));
                                let right = Math.max(...boxes.map((b) => b.right));
                                let bottom = Math.max(...boxes.map((b) => b.bottom));

                                if (icons.length) {
                                    const tops = [...new Set(icons.map((el) => Math.round(el.getBoundingClientRect().top)))].sort((a, b) => a - b);
                                    const firstRow = icons.filter((el) => Math.round(el.getBoundingClientRect().top) === tops[0]);
                                    const lastRowTop = tops[Math.min(rows, tops.length) - 1];
                                    const lastRow = icons.filter((el) => Math.round(el.getBoundingClientRect().top) === lastRowTop);
                                    const lastBottom = Math.max(...lastRow.map((el) => el.getBoundingClientRect().bottom));
                                    // Crop to three rows — don’t let a tall scroller leak into the stage.
                                    bottom = lastBottom + 1;
                                    left = Math.min(left, pane.getBoundingClientRect().left);
                                    // Crop to the icon cells + scroller pad + hairline (drop any gutter slack).
                                    if (firstRow.length) {
                                        const iconsRight = Math.max(...firstRow.map((el) => el.getBoundingClientRect().right));
                                        right = iconsRight + 5 + 1;
                                    } else {
                                        right = Math.max(right, pane.getBoundingClientRect().right);
                                    }
                                }

                                // Flush content to the stage origin, then pad with white.
                                const stageBox = stage.getBoundingClientRect();
                                frame.style.marginLeft = Math.round(stageBox.left - left) + 'px';
                                frame.style.marginTop = Math.round(stageBox.top - top) + 'px';
                                stage.style.width = Math.ceil(right - left) + 'px';
                                stage.style.height = Math.ceil(bottom - top) + 'px';
                                stage.style.padding = inset + 'px';
                                resolve(true);
                            });
                        });
                    });
                })();
            `,
        },
        { type: 'wait', waitFor: { type: 'timeout', ms: 600 } },
    ],
    steps: [],
    target: {
        type: 'selector',
        selector: '#icon-picker-docs-screenshot-stage',
        padding: 0,
    },
    caption: 'Icon Picker field with search and icon grid.',
    intent: 'Show the searchable icon picker pane on a field.',
});
