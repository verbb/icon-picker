import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedIconPickerFixture } from '../../../support/fixtures';

let iconSetRoute = '/admin/icon-picker/settings/icon-sets';

export default defineScreenshotScenario({
    id: 'icon-picker-docs-legacy-icon-set-settings',
    output: 'docs/legacy/icon-set-settings.png',
    route: () => iconSetRoute,
    viewport: { width: 1180, height: 800, deviceScaleFactor: 2 },
    async setup(context) {
        iconSetRoute = (await seedIconPickerFixture(context)).iconSetRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'text', text: 'Interface icons' },
        { type: 'selector', selector: '#type', state: 'visible' },
        { type: 'text', text: 'Search Subfolders' },
    ],
    steps: [
        {
            type: 'evaluate',
            expression: `(() => {
                document.activeElement?.blur();
                const main = document.querySelector('#main');
                const details = document.querySelector('#details');
                if (main instanceof HTMLElement) main.style.maxWidth = '760px';
                if (details instanceof HTMLElement) details.style.display = 'none';
            })()`,
        },
        { type: 'wait', waitFor: { type: 'timeout', ms: 150 } },
    ],
    target: { type: 'selector', selector: '#main', padding: 20 },
    caption: 'An SVG Folder icon set showing its source folder and recursive-search setting.',
    intent: 'Retains the legacy icon-set configuration subject for future documentation without adding it to the Features page.',
});
