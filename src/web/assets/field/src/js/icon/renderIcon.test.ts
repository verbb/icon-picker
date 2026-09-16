import { describe, expect, it } from 'vitest';

import { humanizeLabel } from './renderIcon.js';

describe('humanizeLabel', () => {
    it('preserves the picker label contract for machine-style names', () => {
        expect(humanizeLabel('icon-home2')).toBe('Icon Home 2');
        expect(humanizeLabel('ARROW_LEFT')).toBe('Arrow Left');
        expect(humanizeLabel(null)).toBe('');
    });
});
