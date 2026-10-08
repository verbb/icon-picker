import { expect, it } from 'vitest';
import { adjacentIconIndex, applyIconSetGroups, buildIconRows, indexIconRows, orderIconGroups } from './iconGroups.js';
import type { IconItem } from '../icon/renderIcon.js';

const icon = (value: string, id = 'svg', path = '', label = 'Custom SVGs'): IconItem => ({
    value, browseGroup: { id, label, path },
});

it('keeps sets distinct even with the same label and puts root icons before nested folders', () => {
    const nested = icon('nested', 'svg', 'social/brands');
    const root = icon('root');
    const other = icon('font', 'font', '', 'Custom SVGs');
    const ordered = orderIconGroups([nested, other, root]);
    expect(ordered).toEqual([root, nested, other]);
    expect(buildIconRows(ordered, 5, true).filter((row) => row.kind === 'heading').map((row) => row.label))
        .toEqual(['Custom SVGs', 'Social / Brands', 'Custom SVGs']);
});

it('omits a single-set heading but retains relative folder paths and separate partial rows', () => {
    const rows = buildIconRows([icon('root'), icon('a', 'svg', 'social/brands'), icon('b', 'svg', 'commerce/brands')], 5, false);
    expect(rows.map((row) => row.kind)).toEqual(['icons', 'heading', 'icons', 'heading', 'icons']);
    expect(rows.filter((row) => row.kind === 'heading').map((row) => row.label)).toEqual(['Social / Brands', 'Commerce / Brands']);
});

it('retains source context for search results and does not emit empty groups', () => {
    const catalog = orderIconGroups([icon('one'), icon('two', 'fonts'), icon('three', 'svg', 'social')]);
    const matches = catalog.filter((item) => item.value === 'three');
    expect(buildIconRows(matches, 4, true).map((row) => row.kind === 'heading' ? row.label : row.items[0].value))
        .toEqual(['Custom SVGs', 'Social', 'three']);
    expect(buildIconRows([], 4, true)).toEqual([]);
});

it('moves vertically across partial rows and headings without skipping icons', () => {
    const items = [icon('1'), icon('2'), icon('3'), icon('4', 'fonts'), icon('5', 'fonts'), icon('6', 'fonts')];
    const rows = buildIconRows(items, 2, true);
    expect(adjacentIconIndex(rows, indexIconRows(rows), 1, 1)).toBe(2);
    expect(adjacentIconIndex(rows, indexIconRows(rows), 2, 1)).toBe(3);
    expect(adjacentIconIndex(rows, indexIconRows(rows), 4, -1)).toBe(2);
    expect(adjacentIconIndex(rows, indexIconRows(rows), 0, -1)).toBe(-1);
    expect(adjacentIconIndex(rows, indexIconRows(rows), 5, 1)).toBe(5);
    const narrower = buildIconRows(items, 1, true);
    expect(adjacentIconIndex(narrower, indexIconRows(narrower), 2, 1)).toBe(3);
});

it('resolves shared set metadata without merging duplicate names or humanized paths', () => {
    const catalog: IconItem[] = [
        { value: 'one', iconSetHandle: 'first', browsePath: 'social-icons' },
        { value: 'two', iconSetHandle: 'first', browsePath: 'social_icons' },
        { value: 'three', iconSetHandle: 'second', browsePath: 'social-icons' },
    ];
    const items = orderIconGroups(applyIconSetGroups(catalog, {
        first: { id: 'first-uid', label: 'Custom' },
        second: { id: 'second-uid', label: 'Custom' },
    }));
    const rows = buildIconRows(items, 5, true);
    expect(rows.filter((row) => row.kind === 'icons')).toHaveLength(3);
    expect(rows.filter((row) => row.kind === 'heading').map((row) => row.label))
        .toEqual(['Custom', 'Social Icons', 'Social Icons', 'Custom', 'Social Icons']);
    expect(catalog.every((item) => item.browseGroup === undefined)).toBe(true);
});

it('navigates past consecutive set and folder headings after filtering or resizing', () => {
    const items = [icon('1'), icon('2'), icon('3', 'fonts', 'brands'), icon('4', 'fonts', 'brands')];
    const rows = buildIconRows(items, 2, true);
    const positions = indexIconRows(rows);
    expect(adjacentIconIndex(rows, positions, 1, 1)).toBe(3);
    expect(adjacentIconIndex(rows, positions, 3, -1)).toBe(1);
    const filtered = buildIconRows(items.slice(2), 1, true);
    const filteredPositions = indexIconRows(filtered);
    expect(adjacentIconIndex(filtered, filteredPositions, 0, 1)).toBe(1);
    expect(adjacentIconIndex(filtered, filteredPositions, 1, -1)).toBe(0);
    expect(adjacentIconIndex(filtered, filteredPositions, 0, -1)).toBe(-1);
});
