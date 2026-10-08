import { humanizeLabel, type IconItem } from '../icon/renderIcon.js';

export type IconRow =
    | { kind: 'heading'; label: string; level: number }
    | { kind: 'icons'; items: IconItem[]; startIndex: number; label: string };

const setKey = (icon: IconItem): string =>
    icon.browseGroup?.id ?? icon.iconSetUid ?? icon.iconSetHandle ?? icon.iconSet ?? '';

export type IconSetGroups = Record<string, { id: string; label: string }>;

/** Resolve shared response metadata without changing the stored icon identity. */
export function applyIconSetGroups(icons: IconItem[], sets: IconSetGroups = {}): IconItem[] {
    return icons.map((icon) => {
        const set = sets[icon.iconSetHandle ?? icon.iconSet ?? ''];
        return set ? { ...icon, browseGroup: { ...set, path: icon.browsePath ?? '' } } : icon;
    });
}

/** Keep set order, put root icons first, and keep each relative folder together. */
export function orderIconGroups(icons: IconItem[]): IconItem[] {
    const sets = new Map<string, Map<string, IconItem[]>>();
    for (const icon of icons) {
        const key = setKey(icon);
        const folders = sets.get(key) ?? new Map<string, IconItem[]>();
        const path = icon.browseGroup?.path ?? '';
        const items = folders.get(path) ?? [];
        items.push(icon);
        folders.set(path, items);
        sets.set(key, folders);
    }

    return [...sets.values()].flatMap((folders) => [...folders.keys()]
        .sort((a, b) => a === '' ? -1 : b === '' ? 1 : a.localeCompare(b, undefined, { numeric: true }))
        .flatMap((path) => folders.get(path)!));
}

/** Virtualize rows so headings span the picker without rendering entire sets. */
export function buildIconRows(icons: IconItem[], columns: number, showSetHeadings: boolean): IconRow[] {
    const rows: IconRow[] = [];
    let previousSet: string | undefined;
    let previousPath: string | undefined;
    let row: Extract<IconRow, { kind: 'icons' }> | undefined;
    const cols = Math.max(1, columns);

    icons.forEach((icon, index) => {
        const key = setKey(icon);
        const path = icon.browseGroup?.path ?? '';
        const setLabel = icon.browseGroup?.label ?? icon.iconSetHandle ?? icon.iconSet ?? '';
        const folderLabel = path.split('/').filter(Boolean).map(humanizeLabel).join(' / ');
        const newSet = key !== previousSet;
        const newFolder = newSet || path !== previousPath;

        if (newSet && showSetHeadings) {
            rows.push({ kind: 'heading', label: setLabel, level: 3 });
        }
        if (newFolder && folderLabel) {
            rows.push({ kind: 'heading', label: folderLabel, level: showSetHeadings ? 4 : 3 });
        }
        if (newFolder || !row || row.items.length === cols) {
            row = { kind: 'icons', items: [], startIndex: index, label: [setLabel, folderLabel].filter(Boolean).join(' / ') };
            rows.push(row);
        }
        row.items.push(icon);
        previousSet = key;
        previousPath = path;
    });

    return rows;
}

export interface IconRowPositions {
    rowOfIndex: number[];
    colOfIndex: number[];
}

/** Build once per layout so focus and arrow navigation avoid scanning the catalog. */
export function indexIconRows(rows: IconRow[]): IconRowPositions {
    const rowOfIndex: number[] = [];
    const colOfIndex: number[] = [];
    rows.forEach((row, rowIndex) => {
        if (row.kind === 'icons') {
            row.items.forEach((_item, column) => {
                rowOfIndex[row.startIndex + column] = rowIndex;
                colOfIndex[row.startIndex + column] = column;
            });
        }
    });
    return { rowOfIndex, colOfIndex };
}

/** Navigate real icon rows, including partial rows before a heading. */
export function adjacentIconIndex(rows: IconRow[], positions: IconRowPositions, index: number, direction: -1 | 1): number {
    const rowIndex = positions.rowOfIndex[index];
    const column = positions.colOfIndex[index];
    if (rowIndex !== undefined) {
        for (let r = rowIndex + direction; r >= 0 && r < rows.length; r += direction) {
            const row = rows[r];
            if (row.kind === 'icons') {
                return row.startIndex + Math.min(column, row.items.length - 1);
            }
        }
    }
    return direction === -1 ? -1 : index;
}
