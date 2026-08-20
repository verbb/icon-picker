// Font / spritesheet / script loaders for Icon Picker.
// Dedupes via Craft.IconPicker.Cache (always registered on the CP by IconPickerCacheAsset).

type FontResource = {
    name: string;
    id?: string;
    type: 'local' | 'proxy' | 'remote' | string;
    url?: string | string[];
};

type SpriteSheetResource = {
    name: string;
    url: string;
};

type ScriptResource = {
    name: string;
    type: 'local' | 'remote' | string;
    url?: string;
    content?: string;
    onload?: string;
};

const ensureCache = (): { stylesheets: string[]; fonts: string[]; scripts: string[] } => {
    Craft.IconPicker = Craft.IconPicker || {};
    Craft.IconPicker.Cache = Craft.IconPicker.Cache || { stylesheets: [], fonts: [], scripts: [] };
    Craft.IconPicker.Cache.scripts = Craft.IconPicker.Cache.scripts || [];

    return Craft.IconPicker.Cache;
};

export const loadFonts = (fonts: FontResource[] | undefined): void => {
    if (!fonts?.length) {
        return;
    }

    const cache = ensureCache();

    for (const font of fonts) {
        if (cache.fonts.includes(font.name)) {
            continue;
        }

        cache.fonts.push(font.name);

        if (font.type === 'local' && font.url) {
            const url = Array.isArray(font.url) ? font.url[0] : font.url;
            const style = document.createElement('style');
            style.textContent = [
                `@font-face { font-family: "${font.name}"; src: url("${url}"); font-weight: normal; font-style: normal; }`,
                `.${font.name} { font-family: "${font.name}" !important; }`,
            ].join('\n');
            document.head.appendChild(style);
        } else if (font.type === 'proxy' && font.id) {
            const style = document.createElement('style');
            // Escape dots in generated class selectors (e.g. FontAwesome ids).
            style.textContent = `.${font.id.replace('.', '\\.')} { font-family: "${font.name}" !important; }`;
            document.head.appendChild(style);
        } else if (font.type === 'remote' && font.url) {
            const urls = Array.isArray(font.url) ? font.url : [font.url];

            for (const href of urls) {
                const link = document.createElement('link');
                link.href = href;
                link.rel = 'stylesheet';
                link.type = 'text/css';
                document.head.appendChild(link);
            }
        }
    }
};

export const loadSpriteSheets = (spriteSheets: SpriteSheetResource[] | undefined): void => {
    if (!spriteSheets?.length) {
        return;
    }

    const cache = ensureCache();

    for (const sheet of spriteSheets) {
        if (cache.stylesheets.includes(sheet.name)) {
            continue;
        }

        cache.stylesheets.push(sheet.name);

        fetch(sheet.url)
            .then((response) => response.text())
            .then((text) => {
                const div = document.createElement('div');
                div.innerHTML = text;
                div.id = `icon-picker-spritesheet-${sheet.name}`;
                div.style.display = 'none';
                document.body.insertBefore(div, document.body.firstChild);
            })
            .catch((error) => {
                console.error('[icon-picker] Failed to load spritesheet', sheet.name, error);
            });
    }
};

export const loadScripts = (scripts: ScriptResource[] | undefined): Promise<void> => {
    if (!scripts?.length) {
        return Promise.resolve();
    }

    const cache = ensureCache();

    return Promise.all(
        scripts.map((script) => {
            if (cache.scripts.includes(script.name) || document.getElementById(script.name)) {
                if (!cache.scripts.includes(script.name)) {
                    cache.scripts.push(script.name);
                }
                return Promise.resolve();
            }

            return new Promise<void>((resolve, reject) => {
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
            });
        }),
    ).then(() => undefined);
};
