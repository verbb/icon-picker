import { defineScreenshotScenario } from '@verbb/docs-screenshots/api';
import { seedIconPickerDocsFixture } from '../.screenshots/icon-picker/fixtures';
import { createIconPickerCleanupStep } from '../.screenshots/icon-picker/presets';

// Image scrapped — awkward Type-dropdown crop. Stub kept for a future clean
// Icon Set form capture (named set, Type closed). Do not ship icon-sets.png until then.
let settingsRoute = '/admin/icon-picker/settings/icon-sets';

export default defineScreenshotScenario({
    id: 'feature-tour-icon-sets',
    output: '_screenshots/feature-tour/icon-sets.png',
    route: () => settingsRoute,
    viewport: {
        width: 1320,
        height: 820,
        deviceScaleFactor: 2,
    },
    async setup(context) {
        await seedIconPickerDocsFixture(context);
    },
    waitFor: [
        { type: 'selector', selector: '#content', state: 'visible' },
    ],
    preSteps: [
        createIconPickerCleanupStep(),
    ],
    steps: [],
    target: {
        type: 'selector',
        selector: '#content',
        padding: 20,
    },
    caption: 'Creating an Icon Set in Icon Picker settings.',
    intent: 'Show the Icon Set edit form (stub — image not published).',
});
