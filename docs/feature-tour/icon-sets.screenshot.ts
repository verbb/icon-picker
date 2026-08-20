import { defineScreenshotScenario } from '@verbb/docs-screenshots/api';
import { seedIconPickerDocsFixture } from '../.screenshots/icon-picker/fixtures';
import { createIconPickerCleanupStep } from '../.screenshots/icon-picker/presets';

// Starter scenario — captures the seeded Icon Picker field settings page. Retarget the
// route/selector at the field input once the Phase 1 picker UI is built out.
let settingsRoute = '/admin/settings/fields';

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
        const fixture = await seedIconPickerDocsFixture(context);
        settingsRoute = fixture.settingsRoute;
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
    caption: 'Icon Picker field settings.',
    intent: 'Show how an Icon Picker field is configured in the field settings screen.',
});
