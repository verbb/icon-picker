// @vitest-environment happy-dom
import { afterEach, expect, it, vi } from 'vitest';
import { loadScripts } from './loadResources.js';

afterEach(() => { document.body.replaceChildren(); vi.unstubAllGlobals(); vi.restoreAllMocks(); });

it('retries a remote script after a failed request instead of treating it as loaded', async () => {
    vi.stubGlobal('Craft', { IconPicker: { Cache: { stylesheets: [], fonts: [], scripts: [] } } });
    // Deliver script outcomes explicitly; the DOM test must not request a network asset.
    const append = document.body.appendChild.bind(document.body);
    vi.spyOn(document.body, 'appendChild').mockImplementation((node) => {
        if (node instanceof HTMLScriptElement) node.type = 'application/x-test';
        return append(node);
    });
    const resource = { name: 'test-kit', type: 'remote', url: 'https://example.test/icons.js' };
    const first = loadScripts([resource]);
    const failure = expect(first).rejects.toThrow('Failed to load script test-kit');
    const failedScript = document.getElementById('test-kit')!;
    failedScript.dispatchEvent(new Event('error'));
    await failure;
    const retry = loadScripts([resource]);
    const replacement = document.getElementById('test-kit')!;
    expect(replacement).not.toBe(failedScript);
    replacement.dispatchEvent(new Event('load'));
    await retry;
    expect(Craft.IconPicker?.Cache?.scripts).toContain('test-kit');
});
