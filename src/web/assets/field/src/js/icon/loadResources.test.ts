// @vitest-environment happy-dom
import { afterEach, expect, it, vi } from 'vitest';
import { loadScripts, loadSpriteSheets, loadFonts } from './loadResources.js';
import { renderIconInto } from './renderIcon.js';
import { namespaceSpriteSheet } from './namespaceSpriteSheet.js';

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
    const wrapper = document.getElementById('icon-picker-spritesheet-retry-sprites')!;
    expect(wrapper.style.display).not.toBe('none');
    expect(wrapper.style.width).toBe('0px');
    expect(wrapper.style.height).toBe('0px');
    expect(wrapper.getAttribute('aria-hidden')).toBe('true');
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

it('keeps overlapping sprite symbols and their internal references with their source sheet', async () => {
    vi.stubGlobal('Craft', { IconPicker: { Cache: { stylesheets: [], fonts: [], scripts: [] } } });
    const request = vi.fn(async (url: string) => new Response(`<svg><defs><linearGradient id="paint"/></defs><symbol id="heart" data-source="${url}"><title id="label">Heart</title><path id="detail" fill="url(#paint)" aria-labelledby="label"/><use href="#detail"/></symbol></svg>`));
    vi.stubGlobal('fetch', request);
    const sheets = [
        { name: 'shared-sprites', url: '/outline.svg', namespace: 'outline-sheet' },
        { name: 'shared-sprites', url: '/solid.svg', namespace: 'solid-sheet' },
    ];
    await Promise.all([loadSpriteSheets(sheets), loadSpriteSheets(sheets)]);
    for (const sheet of sheets) {
        const host = document.createElement('div');
        renderIconInto(host, { type: 'sprite', value: 'heart', displayValue: 'heart', spriteId: `${sheet.namespace}-heart` });
        const target = host.querySelector('use')!.getAttribute('href')!.slice(1);
        const symbol = document.getElementById(target)!;
        expect(symbol?.getAttribute('data-source')).toBe(sheet.url);
        expect(symbol.querySelector('path')!.getAttribute('fill')).toBe(`url(#${sheet.namespace}-paint)`);
        expect(symbol.querySelector('path')!.getAttribute('aria-labelledby')).toBe(`${sheet.namespace}-label`);
        expect(symbol.querySelector('use')!.getAttribute('href')).toBe(`#${sheet.namespace}-detail`);
    }
    expect(document.getElementById('heart')).toBeNull();
    expect(request).toHaveBeenCalledTimes(2);
    await loadSpriteSheets(sheets);
    expect(request).toHaveBeenCalledTimes(2);
});

it('keeps sprite CSS references aligned without changing colour values', () => {
    const root = document.createElement('div');
    root.innerHTML = '<svg><defs><linearGradient id="paint"/></defs><symbol id="heart"/><symbol id="f00"/></svg>';
    // happy-dom discards SVG style text during HTML parsing; construct the browser DOM directly.
    const style = document.createElement('style');
    style.textContent = "#heart:hover { fill: url('#paint'); stroke: #f00; }";
    root.prepend(style);
    namespaceSpriteSheet(root, 'scoped');
    expect(style.textContent).toBe('#scoped-heart:hover { fill: url(#scoped-paint); stroke: #f00; }');
});
