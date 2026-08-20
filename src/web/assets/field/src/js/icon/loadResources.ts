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

const ensureCache = (): { stylesheets: string[]; fonts: string[] } => {
    Craft.IconPicker = Craft.IconPicker || {};
    Craft.IconPicker.Cache = Craft.IconPicker.Cache || { stylesheets: [], fonts: [] };

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

export const loadScripts = (scripts: ScriptResource[] | undefined): void => {
    if (!scripts?.length) {
        return;
    }

    for (const script of scripts) {
        if (document.getElementById(script.name)) {
            continue;
        }

        const el = document.createElement('script');
        el.id = script.name;

        if (script.type === 'remote' && script.url) {
            el.src = script.url;
            el.async = true;
            el.defer = true;

            // Preserve legacy onload string evaluation from the Vue field.
            if (script.onload) {
                // Indirect eval — preserves legacy remote-script onload strings from the Vue field
                // without tripping Rollup's direct-eval warning.
                el.onload = (0, eval)(script.onload) as (this: GlobalEventHandlers, ev: Event) => void;
            }
        }

        if (script.type === 'local' && script.content) {
            el.textContent = script.content;
        }

        document.body.appendChild(el);
    }
};
