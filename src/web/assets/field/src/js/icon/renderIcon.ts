// Renders a single Icon Picker icon into a host element (chip + grid cells).
// Four types match the PHP Icon model: svg | sprite | glyph | css.
//
// SVG catalog/chip paint via <img src={url}> so large sets don't ship markup in
// the AJAX payload, and so Carbon-style shared .cls-* / id="icon" can't bleed
// across cells (inline SVG in the light DOM).

import { startCase, toLower } from 'lodash-es';

export interface IconItem {
    value?: string | null;
    iconSet?: string | null;
    iconSetHandle?: string | null;
    type?: string | null;
    label?: string | null;
    keywords?: string | null;
    /** Public URL for SVG files — preferred paint path for type=svg. */
    url?: string | null;
    /** Glyph entity / sprite id / CSS class. Not used for SVG catalog rows. */
    displayValue?: string | null;
    id?: string | null;
}

/** Match Vue field: lodash `startCase(toLower(label))` (e.g. `icon-home2` → `Icon Home 2`). */
export const humanizeLabel = (label?: string | null): string => {
    if (!label) {
        return '';
    }

    return startCase(toLower(label));
};

/**
 * Paint `item` into `host`, replacing previous children.
 * `cssAttribute` comes from the AJAX payload (usually `class`) for CSS icon sets.
 */
export const renderIconInto = (
    host: HTMLElement,
    item: IconItem | null | undefined,
    cssAttribute = 'class',
): void => {
    host.replaceChildren();

    if (!item?.type) {
        return;
    }

    const display = item.displayValue ?? '';

    if (item.type === 'svg') {
        // Prefer URL <img> (catalog + chip). Never paint forged displayValue markup.
        if (item.url) {
            const img = document.createElement('img');
            img.src = item.url;
            img.alt = '';
            img.decoding = 'async';
            img.loading = 'lazy';
            host.appendChild(img);
        }

        return;
    }

    if (item.type === 'sprite') {
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 1000 1000');
        const use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
        use.setAttributeNS('http://www.w3.org/1999/xlink', 'href', `#${display}`);
        use.setAttribute('href', `#${display}`);
        svg.appendChild(use);
        host.appendChild(svg);
        return;
    }

    if (item.type === 'glyph') {
        const span = document.createElement('span');
        span.className = `ipui-font font-face-${item.iconSet ?? ''}`;
        // Catalog glyphs are HTML entities (`&#xE90A;`) — allow that shape only.
        if (/^&#(?:x[0-9a-f]+|\d+);$/i.test(display.trim())) {
            span.innerHTML = display.trim();
        } else {
            span.textContent = display;
        }
        host.appendChild(span);
        return;
    }

    if (item.type === 'css') {
        // Feather catalog rows may include trusted inline SVG from server cache.
        if (display.trimStart().startsWith('<svg') && item.iconSetHandle) {
            const wrap = document.createElement('div');
            wrap.innerHTML = display;
            host.appendChild(wrap);
            return;
        }

        const span = document.createElement('span');
        span.setAttribute(cssAttribute, display);
        host.appendChild(span);
    }
};
