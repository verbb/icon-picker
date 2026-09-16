// @vitest-environment happy-dom
import { afterEach, beforeEach, expect, it, vi } from 'vitest';

vi.mock('@lit-labs/virtualizer', () => ({}));
vi.mock('../icon/loadResources.js', () => ({
    loadFonts: vi.fn(), loadSpriteSheets: vi.fn(), loadScripts: vi.fn().mockResolvedValue(undefined),
}));

import { loadFonts, loadSpriteSheets } from '../icon/loadResources.js';
import { IconPickerInput } from './IconPickerInput.js';

const mount = (value = {}) => {
    const root = document.createElement('div');
    root.dataset.value = JSON.stringify(value);
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

it('searches an icon name as well as its additional metadata keywords', () => {
    const { picker } = mount();
    const state = picker as unknown as { icons: unknown[]; search: string; readonly iconsFiltered: unknown[] };
    const icon = { type: 'css', value: 'bi bi-alarm', label: 'Alarm', keywords: 'time wake' };
    state.icons = [icon];
    state.search = 'ALARM';
    expect(state.iconsFiltered).toEqual([icon]);
    state.search = 'wake';
    expect(state.iconsFiltered).toEqual([icon]);
    picker.destroy();
});

it.each([['stylesheet', loadFonts], ['spritesheet', loadSpriteSheets]] as const)('shows Retry when a %s resource fails and recovers on the next request', async (_name, loader) => {
    const { root, picker } = mount();
    vi.mocked(Craft.sendActionRequest).mockResolvedValue({ data: { icons: [] } });
    vi.mocked(loader).mockRejectedValueOnce(new Error('Resource unavailable'));
    vi.spyOn(console, 'error').mockImplementation(() => {});
    const state = picker as unknown as { fetchIcons(): Promise<void> };
    await state.fetchIcons();
    expect(root.querySelector('.ipui-icons-status')?.textContent).toContain('Request failed.');
    expect(root.querySelector('.ipui-icons-status button')?.textContent).toBe('Retry');
    await state.fetchIcons();
    expect(root.querySelector('.ipui-icons-status')?.textContent).toBe('No icons match your query.');
    picker.destroy();
});


it.each(['Enter', ' '])('preserves native Clear activation for %s without opening the picker', (key) => {
    const { root, picker } = mount({ type: 'css', value: 'bi bi-alarm', displayValue: 'bi bi-alarm', label: 'Alarm' });
    const clear = root.querySelector<HTMLButtonElement>('.ipui-icon-input-clear')!;
    const keydown = new KeyboardEvent('keydown', { key, bubbles: true, cancelable: true });
    clear.dispatchEvent(keydown);
    expect(keydown.defaultPrevented).toBe(false);
    expect(root.querySelector('.ipui-icon-input')?.classList.contains('is-open')).toBe(false);
    // happy-dom does not synthesize keyboard clicks; the real browser covers activation.
    clear.click();
    expect(root.querySelector<HTMLInputElement>('input[data-icon-picker-key="value"]')?.value).toBe('');
    expect(clear.isConnected).toBe(false);
    picker.destroy();
});

it('removes cleared icon markup consumed by the Redactor insertion dialog', () => {
    const { root, picker } = mount({ type: 'css', value: 'bi bi-alarm', displayValue: 'bi bi-alarm', label: 'Alarm' });
    const preview = root.querySelector('.ipui-icon-input-svg')!;
    expect(preview.innerHTML).toContain('bi-alarm');
    root.querySelector<HTMLButtonElement>('.ipui-icon-input-clear')!.click();
    expect(preview.innerHTML).toBe('');
    picker.destroy();
});


it('submits stable source identity and clears it with the selection', () => {
    const uid = 'a8887078-8df1-442a-909c-43ed16d293f5';
    const { root, picker } = mount({ type: 'css', value: 'bi bi-alarm', iconSetHandle: 'icons', iconSetUid: uid });
    const input = root.querySelector<HTMLInputElement>('input[data-icon-picker-key="iconSetUid"]')!;
    expect(input.value).toBe(uid);
    root.querySelector<HTMLButtonElement>('.ipui-icon-input-clear')!.click();
    expect(input.value).toBe('');
    picker.destroy();
});
