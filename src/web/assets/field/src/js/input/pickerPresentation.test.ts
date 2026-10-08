import { expect, it } from 'vitest';
import { pickerPresentation } from './pickerPresentation.js';

it('preserves legacy field appearance and global size overrides', () => {
    expect(pickerPresentation({ settings: { showLabels: true }, itemSize: 36, itemSizeLarge: 44, itemWrapperSizeLarge: 80 }))
        .toEqual({ labelDisplay: 'below', showLabels: true, iconSize: 36, iconBoxSize: 44, cellSize: 80 });
    expect(pickerPresentation({ settings: { showLabels: false }, itemSize: 28, itemWrapperSize: 52 }))
        .toEqual({ labelDisplay: 'tooltip', showLabels: false, iconSize: 28, iconBoxSize: 28, cellSize: 52 });
});

it('keeps Small labels readable and lets explicit label settings override legacy values', () => {
    const small = pickerPresentation({ settings: { iconSize: 'small', labelDisplay: 'below' } });
    expect(small).toMatchObject({ iconSize: 24, iconBoxSize: 30, cellSize: 72 });
    expect(pickerPresentation({ settings: { showLabels: true, labelDisplay: 'hidden', iconSize: 'small' } }))
        .toMatchObject({ labelDisplay: 'hidden', showLabels: false, iconSize: 24, cellSize: 48 });
});

it('grows the selectable area to fit Large artwork', () => {
    expect(pickerPresentation({ settings: { iconSize: 'large', labelDisplay: 'below' } }))
        .toMatchObject({ iconSize: 48, iconBoxSize: 60, cellSize: 92 });
    expect(pickerPresentation({ settings: { iconSize: 'large', labelDisplay: 'tooltip' } }))
        .toMatchObject({ iconSize: 48, iconBoxSize: 48, cellSize: 72 });
});
