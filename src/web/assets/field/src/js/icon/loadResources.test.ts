// @vitest-environment happy-dom
import { afterEach, expect, it, vi } from 'vitest';
import { loadScripts, loadSpriteSheets, loadFonts } from './loadResources.js';

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


it('shares an in-flight script outcome across callers and allows both to retry', async () => {
    vi.stubGlobal('Craft', { IconPicker: { Cache: { stylesheets: [], fonts: [], scripts: [] } } });
    const append = document.body.appendChild.bind(document.body);
    vi.spyOn(document.body, 'appendChild').mockImplementation((node) => {
        if (node instanceof HTMLScriptElement) node.type = 'application/x-test';
        return append(node);
    });
    const resource = { name: 'shared-kit', type: 'remote', url: 'https://example.test/shared.js' };
    const first = loadScripts([resource]);
    const second = loadScripts([resource]);
    const outcomes = Promise.allSettled([first, second]);
    const failedScript = document.getElementById('shared-kit')!;
    failedScript.dispatchEvent(new Event('error'));
    expect((await outcomes).map((outcome) => outcome.status)).toEqual(['rejected', 'rejected']);
    expect(Craft.IconPicker?.Cache?.scripts).not.toContain('shared-kit');
    const retry = Promise.all([loadScripts([resource]), loadScripts([resource])]);
    const replacement = document.getElementById('shared-kit')!;
    expect(replacement).not.toBe(failedScript);
    expect(document.querySelectorAll('#shared-kit')).toHaveLength(1);
    replacement.dispatchEvent(new Event('load'));
    await retry;
    expect(Craft.IconPicker?.Cache?.scripts).toEqual(['shared-kit']);
});

it.each(['http', 'network'])('retries a spritesheet after a %s failure without caching unavailable symbols', async (failure) => {
    vi.stubGlobal('Craft', { IconPicker: { Cache: { stylesheets: [], fonts: [], scripts: [] } } });
    const request = vi.fn();
    if (failure === 'http') request.mockResolvedValueOnce(new Response('Unavailable', { status: 503 }));
    else request.mockRejectedValueOnce(new Error('Offline'));
    request.mockResolvedValueOnce(new Response('<svg><symbol id="retry-symbol"/></svg>'));
    vi.stubGlobal('fetch', request);
    const sheet = { name: 'retry-sprites', url: '/retry-sprites.svg' };
    const outcomes = await Promise.allSettled([loadSpriteSheets([sheet]), loadSpriteSheets([sheet])]);
    expect(outcomes.map((outcome) => outcome.status)).toEqual(['rejected', 'rejected']);
    expect(request).toHaveBeenCalledTimes(1);
    expect(Craft.IconPicker?.Cache?.stylesheets).not.toContain(sheet.name);
    expect(document.getElementById('icon-picker-spritesheet-retry-sprites')).toBeNull();
    await loadSpriteSheets([sheet]);
    expect(request).toHaveBeenCalledTimes(2);
    expect(document.getElementById('retry-symbol')).not.toBeNull();
    expect(Craft.IconPicker?.Cache?.stylesheets).toContain(sheet.name);
});

it('retries a failed remote stylesheet and shares its outcome across fields', async () => {
    vi.stubGlobal('Craft', { IconPicker: { Cache: { stylesheets: [], fonts: [], scripts: [] } } });
    const append = document.head.appendChild.bind(document.head);
    vi.spyOn(document.head, 'appendChild').mockImplementation((node) => {
        if (node instanceof HTMLLinkElement) node.rel = 'audit-test';
        return append(node);
    });
    const font = { name: 'retry-font', type: 'remote', url: 'https://example.test/retry.css' };
    const first = loadFonts([font]);
    const second = loadFonts([font]);
    const outcomes = Promise.allSettled([first, second]);
    const failed = document.head.querySelector<HTMLLinkElement>('link[href="https://example.test/retry.css"]')!;
    failed.dispatchEvent(new Event('error'));
    expect((await outcomes).map((outcome) => outcome.status)).toEqual(['rejected', 'rejected']);
    expect(Craft.IconPicker?.Cache?.fonts).toHaveLength(0);
    expect(failed.isConnected).toBe(false);
    const retry = loadFonts([font]);
    const replacement = document.head.querySelector<HTMLLinkElement>('link[href="https://example.test/retry.css"]')!;
    expect(replacement).not.toBe(failed);
    replacement.dispatchEvent(new Event('load'));
    await retry;
    expect(Craft.IconPicker?.Cache?.fonts).toHaveLength(1);
    replacement.remove();
});

it('loads distinct stylesheets with the same font name while sharing identical requests', async () => {
    vi.stubGlobal('Craft', { IconPicker: { Cache: { stylesheets: [], fonts: [], scripts: [] } } });
    const append = document.head.appendChild.bind(document.head);
    vi.spyOn(document.head, 'appendChild').mockImplementation((node) => {
        if (node instanceof HTMLLinkElement) node.rel = 'audit-test';
        return append(node);
    });
    const solid = { name: 'Font Awesome', type: 'remote', url: 'https://example.test/solid.css' };
    const brands = { ...solid, url: 'https://example.test/brands.css' };
    const requests = Promise.all([loadFonts([solid]), loadFonts([brands]), loadFonts([solid])]);
    const links = Array.from(document.head.querySelectorAll<HTMLLinkElement>('link[href^="https://example.test/"]'));
    // Resolve inserted resources before asserting so a failed regression leaves no pending request.
    links.forEach((link) => link.dispatchEvent(new Event('load')));
    await requests;
    expect(links.map((link) => link.href).sort()).toEqual([brands.url, solid.url]);
    await loadFonts([solid, brands]);
    expect(document.head.querySelectorAll('link[href^="https://example.test/"]')).toHaveLength(2);
    links.forEach((link) => link.remove());
});
