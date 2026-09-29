import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

import type { ScreenshotSetupContext } from '@verbb/craft-screenshots/types';

type IconPickerFixture = {
    entryEditRoute: string;
    iconSetRoute: string;
};

const supportDir = dirname(fileURLToPath(import.meta.url));
const seedScript = readFileSync(join(supportDir, 'seed', 'seed-icon-picker-entry.php'), 'utf8');

/** Seed a local SVG icon set and an entry using it. */
export async function seedIconPickerFixture(context: ScreenshotSetupContext): Promise<IconPickerFixture> {
    const output = await context.runCraftScript(seedScript, { label: 'seed-icon-picker-entry' });
    const fixture = JSON.parse(output.trim()) as IconPickerFixture;

    if (!fixture.entryEditRoute || !fixture.iconSetRoute) {
        throw new Error(`Invalid Icon Picker fixture payload: ${output}`);
    }

    return fixture;
}
