// @vitest-environment happy-dom
import { afterEach, beforeEach, expect, it, vi } from 'vitest';

vi.mock('@lit-labs/virtualizer', () => ({}));
vi.mock('../icon/loadResources.js', () => ({
    loadFonts: vi.fn(), loadSpriteSheets: vi.fn(), loadScripts: vi.fn().mockResolvedValue(undefined),
}));

import { IconPickerInput } from './IconPickerInput.js';

const mount = () => {
    const root = document.createElement('div');
    root.dataset.settings = JSON.stringify({ name: 'icon', fieldId: 1, elementId: 2 });
    document.body.append(root);
    const picker = new IconPickerInput(root);
    picker.init();
    return { root, picker };
};

beforeEach(() => {
    vi.stubGlobal('Craft', {
        randomString: () => 'test',
        t: (_category: string, text: string) => text,
        sendActionRequest: vi.fn(),
    });
});
afterEach(() => { document.body.replaceChildren(); vi.unstubAllGlobals(); vi.restoreAllMocks(); });

it('keeps an actionable error visible after a failed catalog request', async () => {
    const { root, picker } = mount();
    vi.mocked(Craft.sendActionRequest).mockRejectedValue(new Error('Offline'));
    vi.spyOn(console, 'error').mockImplementation(() => {});
    // Exercise the request outcome directly, without a custom-element animation clock.
    await (picker as unknown as { fetchIcons(): Promise<void> }).fetchIcons();
    expect(root.querySelector('.ipui-icons-status')?.textContent).toContain('Request failed.');
    expect(root.querySelector('.ipui-icons-status button')?.textContent).toBe('Retry');
    picker.destroy();
});

it('keeps catalog results when an earlier resource preload finishes afterwards', async () => {
    const { picker } = mount();
    let finishPreload!: (value: { data: unknown }) => void;
    const request = vi.mocked(Craft.sendActionRequest);
    request.mockImplementationOnce(() => new Promise((resolve) => { finishPreload = resolve; }));
    request.mockResolvedValueOnce({ data: { icons: [{ type: 'css', value: 'known' }] } });
    const state = picker as unknown as {
        fetchIcons(options?: { preload: boolean }): Promise<void>;
        icons: unknown[];
        refreshPane(): void;
    };
    // Layout is independently verified in the real Craft browser.
    vi.spyOn(state, 'refreshPane').mockImplementation(() => {});
    const pending = state.fetchIcons({ preload: true });
    await state.fetchIcons();
    finishPreload({ data: { icons: [], fonts: [] } });
    await pending;
    expect(state.icons).toEqual([{ type: 'css', value: 'known' }]);
    picker.destroy();
});
