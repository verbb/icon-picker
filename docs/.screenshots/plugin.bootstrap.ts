import type { ScreenshotSetupContext } from '@verbb/docs-screenshots/types';
import { registerPluginBootstrap } from '@verbb/docs-screenshots/api';

export default registerPluginBootstrap({
    id: 'icon-picker',
    async setup(context: ScreenshotSetupContext) {
        // Ensure Icon Picker migrations/tables exist before fixture seed.
        await context.runCraft(['migrate/up', '--plugin=icon-picker'], { allowFailure: true });
    },
});
