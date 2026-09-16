// Font / spritesheet / script loaders for Icon Picker.
// Dedupes via Craft.IconPicker.Cache (always registered on the CP by IconPickerCacheAsset).

import { namespaceSpriteSheet } from './namespaceSpriteSheet.js';

type FontResource = {
    name: string;
    id?: string;
    type: 'local' | 'proxy' | 'remote' | string;
    url?: string | string[];
};

type SpriteSheetResource = {
    name: string;
    url: string;
    namespace?: string;
};

type ScriptResource = {
    name: string;
    type: 'local' | 'remote' | string;
    url?: string;
    content?: string;
    onload?: string;
};

const pendingFonts = new Map<string, Promise<void>>();
const pendingSpriteSheets = new Map<string, Promise<void>>();
const pendingScripts = new Map<string, Promise<void>>();

const ensureCache = (): { stylesheets: string[]; fonts: string[]; scripts: string[] } => {
    Craft.IconPicker = Craft.IconPicker || {};
    Craft.IconPicker.Cache = Craft.IconPicker.Cache || { stylesheets: [], fonts: [], scripts: [] };
    Craft.IconPicker.Cache.scripts = Craft.IconPicker.Cache.scripts || [];

    return {
        stylesheets: Craft.IconPicker.Cache.stylesheets,
        fonts: Craft.IconPicker.Cache.fonts,
        scripts: Craft.IconPicker.Cache.scripts,
    };
};

export const loadFonts = (fonts: FontResource[] | undefined): Promise<void> => {
    if (!fonts?.length) {
        return Promise.resolve();
    }

    const cache = ensureCache();

    return Promise.all(fonts.map((font) => {
        // Providers can share a family name while loading different collections or versions.
        const urls = font.url ? (Array.isArray(font.url) ? font.url : [font.url]) : [];
        const key = JSON.stringify([font.type, font.name, font.id, urls]);
        const pending = pendingFonts.get(key);
        if (pending) {
            return pending;
        }
        if (cache.fonts.includes(key)) {
            return Promise.resolve();
        }

        const nodes: HTMLElement[] = [];
        const request = (async () => {
            if (font.type === 'local' && font.url) {
                const url = Array.isArray(font.url) ? font.url[0] : font.url;
                const style = document.createElement('style');
                style.textContent = [
                    `@font-face { font-family: "${font.name}"; src: url("${url}"); font-weight: normal; font-style: normal; }`,
                    `.${font.name} { font-family: "${font.name}" !important; }`,
                ].join('\n');
                nodes.push(style);
                document.head.appendChild(style);
            } else if (font.type === 'proxy' && font.id) {
                const style = document.createElement('style');
                // Escape dots in generated class selectors (e.g. FontAwesome ids).
                style.textContent = `.${font.id.replace('.', '\\.')} { font-family: "${font.name}" !important; }`;
                nodes.push(style);
                document.head.appendChild(style);
            } else if (font.type === 'remote' && font.url) {
                const urls = Array.isArray(font.url) ? font.url : [font.url];
                await Promise.all(urls.map((href) => new Promise<void>((resolve, reject) => {
                    const link = document.createElement('link');
                    link.href = href;
                    link.rel = 'stylesheet';
                    link.type = 'text/css';
                    link.onload = () => resolve();
                    link.onerror = () => reject(new Error(`Failed to load stylesheet ${font.name}`));
                    nodes.push(link);
                    document.head.appendChild(link);
                })));
            }

            cache.fonts.push(key);
        })().catch((error) => {
            // A multi-file font is ready only when every stylesheet loads. Remove
            // partial resources so Retry starts a coherent request for this font.
            nodes.forEach((node) => node.remove());
            throw error;
        }).finally(() => { pendingFonts.delete(key); });

        pendingFonts.set(key, request);
        return request;
    })).then(() => undefined);
};

export const loadSpriteSheets = (spriteSheets: SpriteSheetResource[] | undefined): Promise<void> => {
    if (!spriteSheets?.length) {
        return Promise.resolve();
    }

    const cache = ensureCache();

    return Promise.all(spriteSheets.map((sheet) => {
        const key = sheet.namespace ?? sheet.name;
        const elementId = `icon-picker-spritesheet-${key}`;
        const pending = pendingSpriteSheets.get(key);
        if (pending) {
            return pending;
        }
        if (cache.stylesheets.includes(key) || (sheet.namespace && document.getElementById(elementId))) {
            return Promise.resolve();
        }

        const request = fetch(sheet.url)
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`Failed to load spritesheet ${sheet.name} (${response.status})`);
                }
                return response.text();
            })
            .then((text) => {
                const div = document.createElement('div');
                div.innerHTML = text;
                if (sheet.namespace) {
                    namespaceSpriteSheet(div, sheet.namespace);
                }
                div.id = elementId;
                // display:none prevents referenced gradients and nested symbols from painting.
                div.style.cssText = 'position: absolute; width: 0; height: 0; overflow: hidden;';
                div.setAttribute('aria-hidden', 'true');
                document.body.insertBefore(div, document.body.firstChild);
                cache.stylesheets.push(key);
            })
            .finally(() => { pendingSpriteSheets.delete(key); });

        pendingSpriteSheets.set(key, request);
        return request;
    })).then(() => undefined);
};

export const loadScripts = (scripts: ScriptResource[] | undefined): Promise<void> => {
    if (!scripts?.length) {
        return Promise.resolve();
    }

    const cache = ensureCache();

    return Promise.all(
        scripts.map((script) => {
            const pending = pendingScripts.get(script.name);
            if (pending) {
                return pending;
            }

            if (cache.scripts.includes(script.name) || document.getElementById(script.name)) {
                if (!cache.scripts.includes(script.name)) {
                    cache.scripts.push(script.name);
                }
                return Promise.resolve();
            }

            const request = new Promise<void>((resolve, reject) => {
                const el = document.createElement('script');
                el.id = script.name;

                if (script.type === 'remote' && script.url) {
                    el.src = script.url;
                    el.async = true;
                    el.defer = true;
                    el.onload = () => {
                        cache.scripts.push(script.name);
                        // Run legacy onload *after* the script is available (Vue used
                        // bare eval at assign-time, which fired setTimeout too early).
                        if (script.onload) {
                            try {
                                (0, eval)(script.onload);
                            } catch (error) {
                                console.error('[icon-picker] Script onload failed', script.name, error);
                            }
                        }
                        resolve();
                    };
                    el.onerror = () => {
                        // A failed element must not make a subsequent Retry look loaded.
                        el.remove();
                        reject(new Error(`Failed to load script ${script.name}`));
                    };
                    document.body.appendChild(el);
                    return;
                }

                if (script.type === 'local' && script.content) {
                    el.textContent = script.content;
                    document.body.appendChild(el);
                    cache.scripts.push(script.name);
                    resolve();
                    return;
                }

                resolve();
            }).finally(() => { pendingScripts.delete(script.name); });

            pendingScripts.set(script.name, request);
            return request;
        }),
    ).then(() => undefined);
};
