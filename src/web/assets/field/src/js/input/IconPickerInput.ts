// Icon Picker field input — Plugin Kit v2 web components.
//
// Translates `icon-picker-before/.../IconPickerInput.vue` onto `pk-input` + `pk-popover`
// + `@lit-labs/virtualizer` (grid). Plugin Kit wins on chrome/spacing/colors; behaviour
// (value contract, AJAX, four icon types, Cache dedupe) stays the same.

import { html, nothing } from 'lit';
import type { PkTooltip } from '@verbb/plugin-kit-web/components/tooltip/pk-tooltip.js';
import { ref } from 'lit/directives/ref.js';
import { unsafeHTML } from 'lit/directives/unsafe-html.js';
import '@lit-labs/virtualizer';
import { flow } from '@lit-labs/virtualizer/layouts/flow.js';
import type { LitVirtualizer } from '@lit-labs/virtualizer/LitVirtualizer.js';

import { loadFonts, loadScripts, loadSpriteSheets } from '../icon/loadResources.js';
import { humanizeLabel, renderIconInto, type IconItem } from '../icon/renderIcon.js';

import { adjacentIconIndex, applyIconSetGroups, buildIconRows, indexIconRows, orderIconGroups, type IconRow } from './iconGroups.js';

import { pickerPresentation, type IconSize, type LabelDisplay } from './pickerPresentation.js';

export type IconValue = IconItem;

/** Field settings/config passed from PHP via `data-settings`. */
export interface IconPickerSettings {
    id: string;
    inputId: string;
    label?: string;
    name: string;
    loadResources?: boolean;
    settings?: {
        showLabels?: boolean;
        labelDisplay?: LabelDisplay | null;
        iconSize?: IconSize;
        placeholder?: string | null;
        [key: string]: unknown;
    };
    fieldId?: number | null;
    requestController?: 'icons' | 'redactor';
    context?: string | null;
    elementType?: string | null;
    elementId?: number | null;
    siteId?: number | null;
    itemSize?: number;
    itemSizeLarge?: number;
    itemWrapperSize?: number;
    itemWrapperSizeLarge?: number;
}

const HIDDEN_KEYS: (keyof IconValue)[] = [
    'value',
    'iconSet',
    'iconSetHandle',
    'iconSetUid',
    'type',
    'label',
    'keywords',
];

const parseJson = <T>(raw: string | null | undefined, fallback: T): T => {
    if (!raw) {
        return fallback;
    }

    try {
        return JSON.parse(raw) as T;
    } catch {
        return fallback;
    }
};

export class IconPickerInput {
    private readonly root: HTMLElement;
    private readonly settings: IconPickerSettings;
    private readonly triggerId: string;

    private selected: IconValue;
    private icons: IconItem[] = [];
    private showSetHeadings = false;
    private rows: IconRow[] = [];
    private rowPositions = indexIconRows([]);
    private filteredCache: { source: IconItem[]; query: string; items: IconItem[] } | null = null;
    private rowSource: IconItem[] | null = null;
    private search = '';
    private cssAttribute = 'class';
    private isFetching = false;
    private isPreloadFetching = false;
    private requestFailed = false;
    private destroyed = false;
    private open = false;

    private wrap!: HTMLElement;
    private chip!: HTMLElement;
    private chipPreview!: HTMLElement;
    private chipLabel!: HTMLElement;
    private chipSpinner!: HTMLElement;
    private searchInput!: HTMLElement;
    private clearButton!: HTMLButtonElement;
    private popover!: HTMLElement;
    private pane!: HTMLElement;
    private statusEl!: HTMLElement;
    private virtualizer: LitVirtualizer<IconRow> | null = null;
    /** Rebuild the flow layout only when row metrics change. */
    private layoutKey: string | null = null;
    private paneRefreshQueued = false;
    private resizeObserver: ResizeObserver | null = null;
    /** Column count used to pack the visible icon rows. */
    private gridCols = 1;
    /** Roving focus index into `iconsFiltered` (−1 = focus not in the grid). */
    private activeGridIndex = -1;
    private focusGridToken = 0;
    private hiddenInputs = new Map<string, HTMLInputElement>();

    private readonly onResize = (): void => {
        if (this.open) {
            this.syncPopoverWidth();
        }

        this.syncVirtualizerLayout();
    };

    constructor(root: HTMLElement) {
        this.root = root;
        this.settings = parseJson<IconPickerSettings>(root.getAttribute('data-settings'), {
            id: '',
            inputId: '',
            name: root.getAttribute('data-name') || '',
        });
        this.selected = parseJson<IconValue>(root.getAttribute('data-value'), {});
        this.triggerId = this.settings.inputId || `icon-picker-${Craft.randomString(10)}`;
    }

    init(): void {
        this.buildDom();
        this.bindEvents();
        this.syncChrome();
        // Only write hiddens when values actually differ from SSR — avoids FormObserver
        // value-attribute noise on an otherwise untouched entry.
        this.syncHiddenInputs();

        // Non-SVG saved values need fonts/sprites before the chip can paint.
        if (this.settings.loadResources && this.selected.value) {
            this.isPreloadFetching = true;
            this.syncChrome();
            void this.fetchIcons({ preload: true });
        }

        window.addEventListener('resize', this.onResize);
        // Sidebars and slide-outs can resize the field without a window resize.
        this.resizeObserver = new ResizeObserver(this.onResize);
        this.resizeObserver.observe(this.wrap);
    }

    destroy(): void {
        this.hideTooltips();
        this.destroyed = true;
        this.focusGridToken++;
        window.removeEventListener('resize', this.onResize);
        this.resizeObserver?.disconnect();
    }

    // -------------------------------------------------------------------------
    // DOM
    // -------------------------------------------------------------------------

    private buildDom(): void {
        // Keep server-rendered value hiddens (Craft already namespaced their `name`s).
        // Wiping/recreating them is what made FormObserver think the entry changed.
        this.adoptHiddenInputs();

        // Drop any prior chrome (e.g. remount) but never the value inputs.
        [...this.root.children].forEach((child) => {
            if (child instanceof HTMLInputElement && child.dataset.iconPickerKey) {
                return;
            }

            child.remove();
        });

        this.wrap = document.createElement('div');
        this.wrap.className = 'ipui-icon-input';

        // Selected chip overlays the pk-input text (hidden while open). Chrome stays
        // on pk-input — we don't reimplement border/focus/invalid on this wrapper.
        this.chip = document.createElement('div');
        this.chip.className = 'ipui-icon-input-item';
        this.chip.hidden = true;

        this.chipPreview = document.createElement('div');
        this.chipPreview.className = 'ipui-icon-input-svg';

        this.chipSpinner = document.createElement('pk-spinner');
        this.chipSpinner.setAttribute('size', 'xxs');
        this.chipSpinner.hidden = true;

        this.chipLabel = document.createElement('span');
        this.chipLabel.className = 'ipui-icon-input-label';

        this.chip.append(this.chipPreview, this.chipSpinner, this.chipLabel);

        // Visible control = real `pk-input` (not fit-cell). Focus ring + invalid border
        // come from the kit component; chip/clear are composition only.
        // Nameless on purpose — must not participate in form serialize / FormObserver.
        this.searchInput = document.createElement('pk-input');
        this.searchInput.id = this.triggerId;
        this.searchInput.setAttribute('label', this.settings.label || Craft.t('icon-picker', 'Icon Picker'));
        this.searchInput.setAttribute('autocomplete', 'off');
        this.searchInput.setAttribute('autocorrect', 'off');
        this.searchInput.setAttribute('autocapitalize', 'off');
        this.searchInput.classList.add('ipui-icon-input-search');
        this.syncPlaceholder(false);

        // Clear in `slot=end` so it sits inside pk-input chrome (kit end adornment).
        // Native button (not pk-button icon-density) — 20×20 square like BEFORE delete.
        this.clearButton = document.createElement('button');
        this.clearButton.type = 'button';
        this.clearButton.slot = 'end';
        this.clearButton.setAttribute('aria-label', Craft.t('icon-picker', 'Clear'));
        this.clearButton.classList.add('ipui-icon-input-clear');
        this.clearButton.hidden = true;

        const clearGlyph = document.createElement('pk-icon');
        clearGlyph.setAttribute('icon', 'xmark');
        clearGlyph.setAttribute('aria-hidden', 'true');
        this.clearButton.appendChild(clearGlyph);

        this.searchInput.appendChild(this.clearButton);
        this.wrap.append(this.chip, this.searchInput);

        // Popover anchored to the pk-input (controlled `open`, not click-toggle).
        this.popover = document.createElement('pk-popover');
        this.popover.setAttribute('placement', 'bottom-start');
        this.popover.setAttribute('flush', '');
        this.popover.setAttribute('for', this.triggerId);
        this.popover.classList.add('ipui-icons-popover');

        this.pane = document.createElement('div');
        this.pane.className = 'ipui-icons-pane';

        this.statusEl = document.createElement('div');
        this.statusEl.className = 'ipui-icons-status';
        this.pane.appendChild(this.statusEl);

        this.popover.appendChild(this.pane);

        this.root.append(this.wrap, this.popover);
        this.syncInvalidFromField();
    }

    /**
     * Prefer Twig-rendered hiddens (`data-icon-picker-key`). Fallback create is only for
     * odd remounts without SSR markup — that path can still false-dirty ElementEditor,
     * so keep it rare.
     */
    private adoptHiddenInputs(): void {
        const name = this.settings.name;

        for (const key of HIDDEN_KEYS) {
            let input = this.root.querySelector<HTMLInputElement>(
                `input[data-icon-picker-key="${key}"]`,
            );

            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.dataset.iconPickerKey = key;
                input.name = `${name}[${key}]`;
                this.root.appendChild(input);
            }

            this.hiddenInputs.set(key, input);
        }
    }

    private bindEvents(): void {
        // Open on explicit intent only — not raw focusin. Craft slideouts call
        // setFocusWithin() / restore focus on close; focusin treated that as "open"
        // and auto-popped the pane (#109). Click + keyboard openers stay.
        this.wrap.addEventListener('click', (event) => {
            // Clear button handles its own action — don't re-open after clear.
            if ((event.target as HTMLElement).closest('.ipui-icon-input-clear')) {
                return;
            }

            this.setOpen(true);
            (this.searchInput as HTMLElement & { focus?: () => void }).focus?.();
        });

        this.wrap.addEventListener('keydown', (event: KeyboardEvent) => {
            // Let the native button handle Enter/Space instead of opening the picker.
            if (this.open || (event.target as HTMLElement).closest('.ipui-icon-input-clear')) {
                return;
            }

            if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                this.setOpen(true);
                (this.searchInput as HTMLElement & { focus?: () => void }).focus?.();
            }
        });

        this.searchInput.addEventListener('input', () => {
            this.search = (this.searchInput as HTMLElement & { value?: string }).value ?? '';
            // Tab into the field then type — open so filtering is visible.
            if (!this.open) {
                this.setOpen(true);
            }
            // Filter changed — drop grid focus so the next ArrowDown starts at the top.
            this.activeGridIndex = -1;
            this.refreshPane();
        });

        // From search: ArrowDown enters the grid (Tab still works; arrows are the fast path).
        this.searchInput.addEventListener('keydown', (event: KeyboardEvent) => {
            if (!this.open || event.key !== 'ArrowDown') {
                return;
            }

            if (!this.iconsFiltered.length || this.isFetching) {
                return;
            }

            event.preventDefault();
            this.focusGridIndex(this.activeGridIndex >= 0 ? this.activeGridIndex : 0);
        });

        this.clearButton.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            this.clear();
        });

        // Chip restore on hide *start* — waiting for `pk-after-hide` ties the valued
        // chrome to exit animation + any main-thread work from a large grid (FA ~2k
        // icons). Virtualization limits DOM, but after-hide still feels lagged.
        this.popover.addEventListener('pk-hide', (event) => {
            if (event.target !== this.popover) return;
            this.hideTooltips();
            this.open = false;
            this.activeGridIndex = -1;
            this.syncChrome();
        });

        this.popover.addEventListener('pk-after-hide', (event) => {
            if (event.target !== this.popover) return;
            this.open = false;
            this.activeGridIndex = -1;
            const hadSearch = this.search !== '';
            this.search = '';
            (this.searchInput as HTMLElement & { value?: string }).value = '';
            this.syncChrome();
            // Rebind the full list only after the panel is gone (BEFORE cleared search
            // on tippy `onHidden`). Keeps exit animation free of a 2k-item refresh.
            if (hadSearch) {
                this.schedulePaneRefresh();
            }
        });

        this.popover.addEventListener('pk-after-show', (event) => {
            // Tooltip lifecycle events bubble through the picker popover.
            if (event.target !== this.popover) return;
            this.open = true;
            this.syncPopoverWidth();
            this.syncChrome();

            if (!this.isFetching && this.icons.length === 0) {
                void this.fetchIcons();
            } else {
                // Enter fade runs after this event — binding the grid on the same turn
                // starves the transition (small sets still “fade”; FA looks instant).
                this.schedulePaneRefresh();
            }
        });
    }

    /** Two rAFs: let pk-popover’s enter/exit paint before virtualizer bind/layout. */
    private schedulePaneRefresh(): void {
        if (this.paneRefreshQueued) {
            return;
        }

        this.paneRefreshQueued = true;
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                this.paneRefreshQueued = false;
                if (this.destroyed) {
                    return;
                }
                this.refreshPane();
            });
        });
    }

    // -------------------------------------------------------------------------
    // State
    // -------------------------------------------------------------------------

    private setOpen(next: boolean): void {
        if (this.open === next && (this.popover as HTMLElement & { open?: boolean }).open === next) {
            return;
        }

        if (!next) this.hideTooltips();
        this.open = next;
        (this.popover as HTMLElement & { open: boolean }).open = next;
        this.syncChrome();
    }

    private select(icon: IconItem): void {
        this.selected = { ...icon };
        this.syncHiddenInputs();
        this.setOpen(false);
        this.syncChrome();
        this.searchInput.focus();
    }

    private clear(): void {
        this.selected = {};
        this.syncHiddenInputs();
        this.syncChrome();
        this.searchInput.focus();
    }

    private syncHiddenInputs(): void {
        for (const key of HIDDEN_KEYS) {
            const input = this.hiddenInputs.get(key);

            if (!input) {
                continue;
            }

            const next = (this.selected[key] as string) ?? '';

            // Skip no-ops so mount doesn't poke FormObserver's value watcher.
            if (input.value !== next) {
                input.value = next;
            }
        }
    }

    private syncChrome(): void {
        const hasValue = Boolean(this.selected.value);
        const showChip = hasValue && !this.open;

        this.chip.hidden = !showChip;
        this.wrap.classList.toggle('is-open', this.open);
        this.wrap.classList.toggle('has-value', hasValue);
        this.syncInvalidFromField();

        // Mount clear only while valued — a hidden slotted node still makes pk-input
        // treat `slot=end` as occupied and reserves end padding.
        if (hasValue) {
            this.clearButton.hidden = false;
            if (this.clearButton.parentElement !== this.searchInput) {
                this.searchInput.appendChild(this.clearButton);
            }
        } else {
            this.clearButton.remove();
            // Redactor consumes the preview markup when inserting the selected icon.
            this.chipPreview.replaceChildren();
        }

        if (showChip) {
            this.chipLabel.textContent = humanizeLabel(this.selected.label || this.selected.value);

            if (this.isPreloadFetching) {
                this.chipPreview.hidden = true;
                this.chipSpinner.hidden = false;
            } else {
                this.chipSpinner.hidden = true;
                this.chipPreview.hidden = false;
                renderIconInto(this.chipPreview, this.selected, this.cssAttribute);
            }
        }

        // Closed + chip: hide caret/typed text so the overlay is the only readable chrome.
        this.searchInput.classList.toggle('is-covered', showChip);
        this.syncPlaceholder(showChip);
    }

    /** Reflect Craft’s field error state onto `pk-input[invalid]` — kit paints the chrome. */
    private syncInvalidFromField(): void {
        const field = this.root.closest('.field');
        const invalid = Boolean(
            field?.classList.contains('has-errors')
            || field?.querySelector('.errors, .error-list, ul.errors'),
        );
        this.searchInput.toggleAttribute('invalid', invalid);
    }

    private get showLabels(): boolean {
        return pickerPresentation(this.settings).showLabels;
    }

    private get placeholder(): string {
        const raw = this.settings.settings?.placeholder;
        return typeof raw === 'string' ? raw.trim() : '';
    }

    /** Drop placeholder while the selected chip covers the control. */
    private syncPlaceholder(chipCovering: boolean): void {
        if (chipCovering || !this.placeholder) {
            this.searchInput.removeAttribute('placeholder');
            return;
        }

        this.searchInput.setAttribute('placeholder', this.placeholder);
    }

    private get cellSize(): number {
        return pickerPresentation(this.settings).cellSize;
    }

    private get iconSize(): number {
        return pickerPresentation(this.settings).iconSize;
    }

    private get iconBoxSize(): number {
        return pickerPresentation(this.settings).iconBoxSize;
    }

    private hideTooltips(): void {
        this.virtualizer?.querySelectorAll<PkTooltip>('pk-tooltip').forEach((tooltip) => { void tooltip.hide?.(); });
    }

    private get iconsFiltered(): IconItem[] {
        if (!this.icons.length) {
            return [];
        }

        const query = this.search.toLowerCase();

        if (!query) {
            return this.icons;
        }

        if (this.filteredCache?.source !== this.icons || this.filteredCache.query !== query) {
            this.filteredCache = {
                source: this.icons,
                query,
                items: this.icons.filter((icon) => `${icon.label || ''} ${icon.keywords || ''}`.toLowerCase().includes(query)),
            };
        }
        return this.filteredCache.items;
    }

    // -------------------------------------------------------------------------
    // Pane / virtualizer
    // -------------------------------------------------------------------------

    private refreshPane(): void {
        if (this.isFetching) {
            this.showStatus('loading');
            return;
        }

        if (this.requestFailed) {
            this.showStatus('error');
            return;
        }

        const items = this.iconsFiltered;

        if (!items.length) {
            this.showStatus('empty');
            return;
        }

        this.statusEl.hidden = true;
        this.statusEl.replaceChildren();
        this.ensureVirtualizer();

        if (this.activeGridIndex >= items.length) {
            this.activeGridIndex = items.length ? 0 : -1;
        }

        if (this.virtualizer) {
            this.virtualizer.hidden = false;
            this.syncVirtualizerLayout();
        }
    }

    private showStatus(kind: 'loading' | 'empty' | 'error'): void {
        if (this.virtualizer) {
            // Drop items so a failed `[hidden]` can't leave the previous grid painted
            // under the empty/loading message (same display-vs-hidden trap as the chip).
            this.hideTooltips();
            this.virtualizer.items = [];
            this.rowSource = null;
            this.rows = [];
            this.virtualizer.hidden = true;
            this.virtualizer.style.height = '';
            this.virtualizer.style.minHeight = '';
        }

        this.statusEl.hidden = false;
        this.statusEl.replaceChildren();

        if (kind === 'loading') {
            const spinner = document.createElement('pk-spinner');
            spinner.setAttribute('size', 'sm');
            spinner.setAttribute('centered', '');
            this.statusEl.appendChild(spinner);
            return;
        }

        if (kind === 'error') {
            this.statusEl.textContent = Craft.t('icon-picker', 'Request failed.');
            const retry = document.createElement('button');
            retry.type = 'button';
            retry.className = 'btn';
            retry.textContent = Craft.t('icon-picker', 'Retry');
            retry.addEventListener('click', () => { void this.fetchIcons(); });
            this.statusEl.appendChild(retry);
            return;
        }

        this.statusEl.textContent = Craft.t('icon-picker', 'No icons match your query.');
    }

    private ensureVirtualizer(): void {
        if (this.virtualizer) {
            return;
        }

        const virtualizer = document.createElement('lit-virtualizer') as LitVirtualizer<IconRow>;
        virtualizer.className = 'ipui-icons-scroller';
        virtualizer.addEventListener('scroll', () => this.hideTooltips(), { passive: true });
        virtualizer.scroller = true;
        virtualizer.renderItem = (row: IconRow, index: number) => this.renderRow(row, index);
        // Capture so arrows win over scroll-default and recycled cell focus.
        virtualizer.addEventListener(
            'keydown',
            (event) => this.onGridKeydown(event as KeyboardEvent),
            true,
        );
        this.pane.appendChild(virtualizer);
        this.virtualizer = virtualizer;
        this.syncVirtualizerLayout();
    }

    private onGridKeydown(event: KeyboardEvent): void {
        if (!this.open || this.isFetching) {
            return;
        }

        const items = this.iconsFiltered;
        if (!items.length) {
            return;
        }

        // Always trust the focused cell’s index — `activeGridIndex` can lag behind a
        // programmatic focus or a recycled lit listener until the next @focus tick.
        const focused = (event.target as HTMLElement | null)?.closest?.('.ipui-icon-wrap') as HTMLElement | null;
        const fromDom = focused?.dataset.gridIndex;
        let next =
            fromDom != null && fromDom !== ''
                ? Number(fromDom)
                : this.activeGridIndex >= 0 && this.activeGridIndex < items.length
                    ? this.activeGridIndex
                    : 0;

        const row = this.rows[this.rowPositions.rowOfIndex[next]];
        if (!row || row.kind !== 'icons') {
            return;
        }

        switch (event.key) {
            case 'ArrowRight':
                next = Math.min(items.length - 1, next + 1);
                break;
            case 'ArrowLeft':
                next = Math.max(0, next - 1);
                break;
            case 'ArrowDown':
                next = adjacentIconIndex(this.rows, this.rowPositions, next, 1);
                break;
            case 'ArrowUp': {
                const above = adjacentIconIndex(this.rows, this.rowPositions, next, -1);
                if (above < 0) {
                    event.preventDefault();
                    this.activeGridIndex = next;
                    (this.searchInput as HTMLElement & { focus?: () => void }).focus?.();
                    return;
                }
                next = above;
                break;
            }
            case 'Home':
                next = event.ctrlKey || event.metaKey ? 0 : row.startIndex;
                break;
            case 'End': {
                if (event.ctrlKey || event.metaKey) {
                    next = items.length - 1;
                } else {
                    const rowEnd = row.startIndex + row.items.length - 1;
                    next = Math.min(items.length - 1, rowEnd);
                }
                break;
            }
            default:
                return;
        }

        event.preventDefault();
        this.focusGridIndex(next);
    }

    /**
     * Scroll the virtualized cell into view, then focus its button.
     * `element(i)` is only a scroll proxy — focus comes from the painted DOM node.
     */
    private focusGridIndex(index: number): void {
        const items = this.iconsFiltered;
        if (!this.virtualizer || !items.length) {
            return;
        }

        const next = Math.max(0, Math.min(items.length - 1, index));
        this.activeGridIndex = next;
        const token = ++this.focusGridToken;

        this.virtualizer.scrollToIndex(this.rowPositions.rowOfIndex[next] ?? 0, 'nearest');

        const tryFocus = (): void => {
            if (token !== this.focusGridToken || !this.virtualizer) {
                return;
            }

            const button = this.virtualizer.querySelector(
                `.ipui-icon-wrap[data-grid-index="${next}"]`,
            ) as HTMLButtonElement | null;

            if (!button) {
                // Cell not painted yet (scroll pin / recycle) — retry once layout settles.
                const settle = this.virtualizer.layoutComplete ?? Promise.resolve();
                void settle.then(() => {
                    if (token !== this.focusGridToken) {
                        return;
                    }

                    requestAnimationFrame(() => {
                        if (token !== this.focusGridToken || !this.virtualizer) {
                            return;
                        }

                        const retry = this.virtualizer.querySelector(
                            `.ipui-icon-wrap[data-grid-index="${next}"]`,
                        ) as HTMLButtonElement | null;
                        this.applyGridFocus(retry, next);
                    });
                });
                return;
            }

            this.applyGridFocus(button, next);
        };

        requestAnimationFrame(tryFocus);
    }

    private applyGridFocus(button: HTMLButtonElement | null, index: number): void {
        if (!button || !this.virtualizer) {
            return;
        }

        // Roving tabindex: only the active cell stays in the Tab cycle.
        this.virtualizer.querySelectorAll<HTMLButtonElement>('.ipui-icon-wrap').forEach((el) => {
            el.tabIndex = -1;
        });
        button.tabIndex = 0;
        button.focus({ preventScroll: true });
        this.activeGridIndex = index;
    }

    /** Match BEFORE tippy: panel width = field width (kit flush max is ~360px otherwise). */
    private syncPopoverWidth(): void {
        const width = Math.max(this.wrap.offsetWidth, 0);
        this.root.style.setProperty('--ipui-popover-width', `${width}px`);
    }

    private syncVirtualizerLayout(): void {
        if (!this.virtualizer) {
            return;
        }

        const size = this.cellSize;
        const gapPx = this.showLabels ? 4 : 0;
        const layoutKey = `${size}:${gapPx}`;
        if (this.layoutKey !== layoutKey) {
            this.layoutKey = layoutKey;
            // Retain the existing small offscreen buffer: flow's 1000px default
            // paints many extra icon rows when a picker first opens.
            this.virtualizer.layout = flow({
                ...({ _overhang: Math.max(size * 2, 160) } as object),
            });
            this.rowSource = null;
        }

        this.root.style.setProperty('--ipui-cell-size', `${size}px`);
        // BEFORE: --icon-item-size always drives font-size; --icon-item-size-large only
        // grows the .ipui-icon-svg box when labels are on (wide FA glyphs need that slack).
        this.root.style.setProperty('--ipui-icon-size', `${this.iconSize}px`);
        this.root.style.setProperty('--ipui-icon-size-large', `${this.iconBoxSize}px`);
        this.pane.classList.toggle('show-labels', this.showLabels);

        this.root.style.setProperty('--ipui-row-gap', `${gapPx}px`);
        const scrollerPad = 10;
        const rawWidth = Math.max(this.pane.clientWidth || this.wrap.offsetWidth, size);
        const maxHeight = Math.floor(window.innerHeight * 0.5);
        const items = this.iconsFiltered;
        const colsFor = (width: number) => Math.max(1, Math.floor((width + gapPx) / (size + gapPx)));
        const heightFor = (rows: IconRow[]) => rows.reduce((height, row, index) =>
            height + (row.kind === 'heading' ? (index === 0 ? 24 : 32) : size + gapPx), scrollerPad);

        let cols = colsFor(rawWidth - scrollerPad);
        let rows = buildIconRows(items, cols, this.showSetHeadings);
        let contentHeight = heightFor(rows);
        if (contentHeight > maxHeight) {
            // Reserve room for a classic scrollbar before packing rows.
            cols = colsFor(rawWidth - scrollerPad - 15);
            rows = buildIconRows(items, cols, this.showSetHeadings);
            contentHeight = heightFor(rows);
        }

        if (this.rowSource !== items || this.gridCols !== cols) {
            const restoreFocus = this.open && this.virtualizer.contains(document.activeElement);
            this.gridCols = cols;
            this.rowSource = items;
            this.hideTooltips();
            this.rows = rows;
            this.rowPositions = indexIconRows(rows);
            this.virtualizer.items = rows;
            if (restoreFocus) {
                this.focusGridIndex(this.activeGridIndex);
            }
        }

        const height = Math.min(Math.max(contentHeight, 100), maxHeight);
        this.virtualizer.style.height = `${height}px`;
        this.virtualizer.style.minHeight = `${height}px`;
    }

    private renderRow(row: IconRow, index: number) {
        if (row.kind === 'heading') {
            return html`<div class="ipui-group-heading" ?data-first=${index === 0} role="heading" aria-level=${row.level} title=${row.label}>${row.label}</div>`;
        }
        return html`<div class="ipui-icon-row" role="group" aria-label=${row.label}>
            ${row.items.map((item, offset) => this.renderGridItem(item, row.startIndex + offset))}
        </div>`;
    }

    private renderGridItem(item: IconItem, index: number) {
        const label = humanizeLabel(item.label || item.value);
        const cssAttribute = this.cssAttribute;
        const showLabels = this.showLabels;
        const tooltip = pickerPresentation(this.settings).labelDisplay === 'tooltip';
        // One tab stop in the grid; arrows move focus (see onGridKeydown).
        // When nothing has been arrow-focused yet, keep index 0 in the Tab cycle.
        const tabIndex =
            index === (this.activeGridIndex >= 0 ? this.activeGridIndex : 0) ? 0 : -1;

        const button = html`
            <button
                type="button"
                class="ipui-icon-wrap"
                slot=${tooltip ? 'trigger' : nothing}
                aria-label=${label}
                title=${showLabels ? label : nothing}
                data-grid-index=${String(index)}
                tabindex=${tabIndex}
                @focus=${() => {
                    this.activeGridIndex = index;
                }}
                @click=${(event: Event) => {
                    event.preventDefault();
                    this.select(item);
                }}
            >
                <div class="ipui-icon-svg">
                    ${this.renderIconTemplate(item, cssAttribute)}
                </div>
                ${showLabels ? html`<span class="ipui-icon-label">${label}</span>` : nothing}
            </button>
        `;

        return tooltip ? html`<pk-tooltip content=${label}>${button}</pk-tooltip>` : button;
    }

    private renderIconTemplate(item: IconItem, cssAttribute: string) {
        const display = item.displayValue ?? '';

        if (item.type === 'svg') {
            // URL <img> keeps catalog payloads small and isolates SVG CSS/ids (Carbon).
            if (item.url) {
                return html`<img src=${item.url} alt="" decoding="async" loading="lazy" />`;
            }

            if (display) {
                return html`<div>${unsafeHTML(display)}</div>`;
            }

            return nothing;
        }

        if (item.type === 'sprite') {
            return html`
                <svg viewBox="0 0 1000 1000">
                    <use href=${`#${item.spriteId ?? display}`}></use>
                </svg>
            `;
        }

        if (item.type === 'glyph') {
            // Same entity allowlist as renderIconInto — never dump arbitrary markup.
            const raw = display.trim();
            const entity = /^&#(?:x[0-9a-f]+|\d+);?$/i.test(raw)
                ? (raw.endsWith(';') ? raw : `${raw};`)
                : '';

            return html`
                <span class=${`ipui-font ${item.fontClass || `font-face-${item.iconSet ?? ''}`}`}>
                    ${entity ? unsafeHTML(entity) : nothing}
                </span>
            `;
        }

        if (item.type === 'css') {
            // Inline SVG cached as displayValue (Feather) — same as svg markup path.
            if (display.trimStart().startsWith('<svg')) {
                return html`<div>${unsafeHTML(display)}</div>`;
            }

            // AJAX may name the attribute (`class`, etc.) — set it after the node exists.
            return html`
                <span ${ref((el) => {
                    if (el instanceof HTMLElement) {
                        el.setAttribute(item.cssAttribute || cssAttribute, display);
                    }
                })}></span>
            `;
        }

        return nothing;
    }

    // -------------------------------------------------------------------------
    // Fetch / resources
    // -------------------------------------------------------------------------

    private async fetchIcons({ preload = false }: { preload?: boolean } = {}): Promise<void> {
        if (!preload) {
            this.isFetching = true;
            this.requestFailed = false;
        }
        this.refreshPane();

        const data = {
            fieldId: this.settings.fieldId,
            context: this.settings.context,
            elementType: this.settings.elementType,
            elementId: this.settings.elementId,
            siteId: this.settings.siteId,
        };
        const controller = this.settings.requestController === 'redactor' ? 'redactor' : 'icons';
        const endpoint = `icon-picker/${controller}/${preload ? 'resources-for-field' : 'icons-for-field'}`;

        try {
            const response = await Craft.sendActionRequest('POST', endpoint, { data });
            if (this.destroyed) {
                return;
            }
            const payload = response.data || {};

            if (payload.cssAttribute) {
                this.cssAttribute = payload.cssAttribute;
            }

            if (!preload && payload.icons) {
                this.icons = orderIconGroups(applyIconSetGroups(payload.icons as IconItem[], payload.iconSets));
                this.showSetHeadings = payload.showSetHeadings === true;
            }

            await Promise.all([
                loadSpriteSheets(payload.spriteSheets),
                loadFonts(payload.fonts),
                loadScripts(payload.scripts),
            ]);
        } catch (error) {
            console.error('[icon-picker] Failed to fetch icons', error);
            if (!preload) {
                this.requestFailed = true;
            }
        } finally {
            if (this.destroyed) {
                return;
            }
            if (preload) {
                this.isPreloadFetching = false;
            } else {
                this.isFetching = false;
            }
            this.syncChrome();
            // Warm the grid while the popover is still closed when possible; if open,
            // defer so the enter transition isn’t competing with the first bind.
            if (this.open) {
                this.schedulePaneRefresh();
            } else {
                this.refreshPane();
            }
        }
    }

}
