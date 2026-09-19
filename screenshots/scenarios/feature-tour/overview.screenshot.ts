import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedIconPickerFixture } from '../../support/fixtures';
import { createIconPickerFrameStep, positionIconPickerMenuStep } from '../../support/presets';

let entryEditRoute = '/admin/entries';

export default defineScreenshotScenario({
    id: 'icon-picker-feature-tour-overview',
    output: 'feature-tour/icon-picker-field.png',
    route: () => entryEditRoute,
    viewport: {
        width: 820,
        height: 520,
        deviceScaleFactor: 2,
    },
    expectedOutput: {
        width: 1366,
        height: 720,
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
        createIconPickerFrameStep(),
        { type: 'click', selector: '.ipui-icon-input input[type="text"]' },
        { type: 'wait', waitFor: { type: 'selector', selector: '[data-tippy-root] .ipui-icon-wrap', state: 'visible' } },
        positionIconPickerMenuStep(),
        { type: 'wait', waitFor: { type: 'timeout', ms: 300 } },
    ],
    target: {
        type: 'selector',
        selector: '#icon-picker-screenshot-frame',
        padding: 0,
    },
    caption: 'A searchable Icon Picker field showing a grid of locally managed SVG icons.',
    intent: 'Recreates the production field-picker screenshot with the current Craft 5 interface and deterministic local icons.',
});
