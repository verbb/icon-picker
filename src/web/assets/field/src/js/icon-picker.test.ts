// @vitest-environment happy-dom
import { expect, it, vi } from 'vitest';
vi.mock('@lit-labs/virtualizer', () => ({}));
vi.mock('@verbb/plugin-kit-web/plugin-kit', () => ({ allDefined: async () => {} }));
import { IconPickerInput } from './input/IconPickerInput.js';

it('destroys removed picker roots and mounts them again if reinserted', async () => {
    vi.stubGlobal('Craft', { randomString: () => 'test', t: (_: string, text: string) => text });
    const destroy = vi.spyOn(IconPickerInput.prototype, 'destroy');
    const init = vi.spyOn(IconPickerInput.prototype, 'init');
    await import('./icon-picker.js');
    const root = document.createElement('div');
    root.dataset.iconPickerAutoMount = 'input';
    root.dataset.settings = JSON.stringify({ name: 'icon' });
    document.body.append(root);
    await vi.waitFor(() => expect(init).toHaveBeenCalledTimes(1));
    root.remove();
    await vi.waitFor(() => expect(destroy).toHaveBeenCalledTimes(1));
    document.body.append(root);
    await vi.waitFor(() => expect(init).toHaveBeenCalledTimes(2));
    root.remove();
    await vi.waitFor(() => expect(destroy).toHaveBeenCalledTimes(2));
    vi.restoreAllMocks();
    vi.unstubAllGlobals();
});
