// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';

import { humanizeLabel } from './renderIcon.js';

describe('humanizeLabel', () => {
    it('preserves the picker label contract for machine-style names', () => {
        expect(humanizeLabel('icon-home2')).toBe('Icon Home 2');
        expect(humanizeLabel('ARROW_LEFT')).toBe('Arrow Left');
        expect(humanizeLabel(null)).toBe('');
    });
});

it('uses the catalog font identifier for a glyph with a spaced filename', async () => {
    const { renderIconInto } = await import('./renderIcon.js');
    const host = document.createElement('div');
    renderIconInto(host, {
        type: 'glyph', iconSet: 'Custom Icons', fontClass: 'font-face-437573746f6d2049636f6e73', displayValue: '&#xE817;',
    });
    expect(host.firstElementChild?.classList.contains('font-face-437573746f6d2049636f6e73')).toBe(true);
    expect(host.firstElementChild?.textContent).toBe('\uE817');
});
