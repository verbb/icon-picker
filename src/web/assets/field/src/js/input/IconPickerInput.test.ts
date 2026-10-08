// @vitest-environment happy-dom
import { afterEach, beforeEach, expect, it, vi } from 'vitest';

vi.mock('@lit-labs/virtualizer', () => ({}));
vi.mock('../icon/loadResources.js', () => ({
    loadFonts: vi.fn(), loadSpriteSheets: vi.fn(), loadScripts: vi.fn().mockResolvedValue(undefined),
}));

import { loadFonts, loadSpriteSheets } from '../icon/loadResources.js';
import { render, type TemplateResult } from 'lit';
import { IconPickerInput } from './IconPickerInput.js';

const mount = (value = {}, displaySettings = {}) => {
    const root = document.createElement('div');
    root.dataset.value = JSON.stringify(value);
    root.dataset.settings = JSON.stringify({
        name: 'icon',
        settings: displaySettings,
        fieldId: 1,
        context: 'signed-context',
        elementType: 'verbb\\vizy\\elements\\Block',
        elementId: 2,
        siteId: 3,
    });
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

it('sends the signed element type with the nested input identity', async () => {
    const { picker } = mount();
    vi.mocked(Craft.sendActionRequest).mockResolvedValue({ data: { icons: [] } });

    await (picker as unknown as { fetchIcons(): Promise<void> }).fetchIcons();

    expect(Craft.sendActionRequest).toHaveBeenCalledWith('POST', 'icon-picker/icons/icons-for-field', {
        data: {
            fieldId: 1,
            context: 'signed-context',
            elementType: 'verbb\\vizy\\elements\\Block',
            elementId: 2,
            siteId: 3,
        },
    });
    picker.destroy();
});

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
    request.mockResolvedValueOnce({ data: {
        icons: [{ type: 'css', value: 'known', iconSetHandle: 'custom', browsePath: 'brands' }],
        iconSets: { custom: { id: 'custom-uid', label: 'Custom' } },
        showSetHeadings: true,
    } });
    const state = picker as unknown as {
        fetchIcons(options?: { preload: boolean }): Promise<void>;
        icons: unknown[];
        refreshPane(): void;
    };
    // Layout is independently verified in the real Craft browser.
    vi.spyOn(state, 'refreshPane').mockImplementation(() => {});
    const pending = state.fetchIcons({ preload: true });
    await state.fetchIcons();
    finishPreload({ data: { icons: [], fonts: [], iconSets: {}, showSetHeadings: false } });
    await pending;
    expect(state.icons).toEqual([{
        type: 'css', value: 'known', iconSetHandle: 'custom', browsePath: 'brands',
        browseGroup: { id: 'custom-uid', label: 'Custom', path: 'brands' },
    }]);
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

it('keeps browsing groups out of submitted values', () => {
    const { root, picker } = mount();
    const state = picker as unknown as { select(icon: unknown): void };
    state.select({ value: '/social/star.svg', type: 'svg', iconSetUid: 'set-uid', browsePath: 'social', browseGroup: { id: 'set-uid', label: 'Custom', path: 'social' } });
    expect(root.querySelector<HTMLInputElement>('input[data-icon-picker-key="value"]')?.value).toBe('/social/star.svg');
    expect(root.querySelector<HTMLInputElement>('input[data-icon-picker-key="iconSetUid"]')?.value).toBe('set-uid');
    expect([...root.querySelectorAll('input[type="hidden"]')].some((input) => input.getAttribute('name')?.includes('browse'))).toBe(false);
    picker.destroy();
});

it.each(['hidden', 'tooltip', 'below'] as const)('renders accessible names in %s label mode', (labelDisplay) => {
    const { picker } = mount({}, { labelDisplay });
    const state = picker as unknown as { renderGridItem(item: unknown, index: number): TemplateResult };
    const host = document.createElement('div');
    document.body.append(host);
    render(state.renderGridItem({ type: 'css', value: 'heart', label: 'heart' }, 0), host);
    const button = host.querySelector('button')!;
    expect(button.getAttribute('aria-label')).toBe('Heart');
    expect(host.querySelector('pk-tooltip') !== null).toBe(labelDisplay === 'tooltip');
    expect(host.querySelector('.ipui-icon-label') !== null).toBe(labelDisplay === 'below');
    expect(button.hasAttribute('title')).toBe(labelDisplay === 'below');
    picker.destroy();
});

it('ignores nested tooltip lifecycle events without clearing the picker search', () => {
    const { root, picker } = mount();
    const state = picker as unknown as { open: boolean; search: string };
    state.open = true;
    state.search = 'heart';
    const tooltip = document.createElement('pk-tooltip');
    root.querySelector('pk-popover')!.append(tooltip);
    for (const type of ['pk-hide', 'pk-after-hide', 'pk-after-show']) {
        tooltip.dispatchEvent(new CustomEvent(type, { bubbles: true }));
        expect(state.open).toBe(true);
        expect(state.search).toBe('heart');
    }
    picker.destroy();
});
