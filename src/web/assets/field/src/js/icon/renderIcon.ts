// Renders a single Icon Picker icon into a host element (chip + grid cells).
// Four types match the PHP Icon model: svg | sprite | glyph | css.

import { startCase, toLower } from 'lodash-es';

export interface IconItem {
    value?: string | null;
    iconSet?: string | null;
    iconSetHandle?: string | null;
    type?: string | null;
    label?: string | null;
    keywords?: string | null;
    /** UI-only — SVG markup, sprite id, glyph HTML, or CSS class. Not posted on save. */
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
        const wrap = document.createElement('div');
        wrap.innerHTML = display;
        host.appendChild(wrap);
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
        span.innerHTML = display;
        host.appendChild(span);
        return;
    }

    if (item.type === 'css') {
        const span = document.createElement('span');
        span.setAttribute(cssAttribute, display);
        host.appendChild(span);
    }
};
