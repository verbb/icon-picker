import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedIconPickerFixture } from '../../support/fixtures';

let entryEditRoute = '/admin/entries';

export default defineScreenshotScenario({
    id: 'icon-picker-feature-tour-overview',
    output: 'feature-tour/icon-picker-field.png',
    route: () => entryEditRoute,
    viewport: {
        width: 1200,
        height: 800,
        deviceScaleFactor: 2,
    },
    async setup(context) {
        const fixture = await seedIconPickerFixture(context);
        entryEditRoute = fixture.entryEditRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'selector', selector: '.ipui-icon-input input[type="text"]', state: 'visible' },
    ],
    preSteps: [
        {
            type: 'evaluate',
            expression: `(() => {
                const details = document.querySelector('#details-container');
                const detailsToggle = document.querySelector('#details-toggle-wrapper');
                const contentGrid = document.querySelector('.content-grid');
                const contentMain = document.querySelector('.content-grid__main');
                const input = document.querySelector('.ipui-icon-input input[type="text"]');
                const field = input?.closest('.field');

                document.body.style.background = '#fff';

                if (details instanceof HTMLElement) {
                    details.style.display = 'none';
                }

                if (detailsToggle instanceof HTMLElement) {
                    detailsToggle.style.display = 'none';
                }

                if (contentGrid instanceof HTMLElement) {
                    contentGrid.style.gridTemplateColumns = 'minmax(0, 1fr)';
                }

                if (contentMain instanceof HTMLElement) {
                    contentMain.style.gridColumn = '1 / -1';
                    contentMain.style.maxWidth = 'none';
                }

                if (field instanceof HTMLElement) {
                    field.style.width = '760px';
                    field.style.maxWidth = '100%';
                }
            })()`,
        },
        { type: 'click', selector: '.ipui-icon-input input[type="text"]' },
        { type: 'wait', waitFor: { type: 'selector', selector: '[data-tippy-root] .ipui-icon-wrap', state: 'visible' } },
        { type: 'wait', waitFor: { type: 'timeout', ms: 300 } },
    ],
    target: {
        type: 'anchoredClip',
        selector: '.ipui-icon-input',
        x: -8,
        y: -34,
        width: 728,
        height: 488,
    },
    caption: 'A searchable Icon Picker field showing a grid of locally managed SVG icons.',
    intent: 'Captures the real Icon Picker field and its open popup in the current Craft 5 control panel.',
});
