import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

import type { ScreenshotSetupContext } from '@verbb/docs-screenshots/types';

export type IconPickerDocsFixture = {
    fieldId: number;
    fieldHandle: string;
    settingsRoute: string;
    entryEditRoute: string;
};

const fixtureDir = dirname(fileURLToPath(import.meta.url));
const seedScript = readFileSync(join(fixtureDir, 'seed-docs-field.php'), 'utf8');

/**
 * Seed the Icon Picker demo field + a section/entry that uses it, so field-settings
 * and entry-edit screenshots have real content. Returns the CP routes to capture.
 */
export async function seedIconPickerDocsFixture(context: ScreenshotSetupContext): Promise<IconPickerDocsFixture> {
    const output = await context.runCraftScript(seedScript, { label: 'seed-icon-picker-docs-field' });
    const fixture = JSON.parse(output.trim()) as IconPickerDocsFixture;

    if (!fixture.fieldId || !fixture.settingsRoute || !fixture.entryEditRoute) {
        throw new Error(`Invalid Icon Picker docs fixture payload: ${output}`);
    }

    return fixture;
}
