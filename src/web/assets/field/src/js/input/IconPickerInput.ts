// Icon Picker field input — Plugin Kit v2 web components.
//
// Translates `icon-picker-before/.../IconPickerInput.vue` onto `pk-input` + `pk-popover`
// + `@lit-labs/virtualizer` (grid). Plugin Kit wins on chrome/spacing/colors; behaviour
// (value contract, AJAX, four icon types, Cache dedupe) stays the same.

import { html, nothing } from 'lit';
import { ref } from 'lit/directives/ref.js';
import { unsafeHTML } from 'lit/directives/unsafe-html.js';
import '@lit-labs/virtualizer';
import { grid } from '@lit-labs/virtualizer/layouts/grid.js';
import type { LitVirtualizer } from '@lit-labs/virtualizer/LitVirtualizer.js';

import { loadFonts, loadScripts, loadSpriteSheets } from '../icon/loadResources.js';
import { humanizeLabel, renderIconInto, type IconItem } from '../icon/renderIcon.js';

export type IconValue = IconItem;

/** Field settings/config passed from PHP via `data-settings`. */
export interface IconPickerSettings {
    id: string;
    inputId: string;
    name: string;
    loadResources?: boolean;
    settings?: {
        showLabels?: boolean;
        placeholder?: string | null;
        [key: string]: unknown;
    };
    fieldId?: number | null;
    itemSize?: number;
    itemSizeLarge?: number;
    itemWrapperSize?: number;
    itemWrapperSizeLarge?: number;
}

const HIDDEN_KEYS: (keyof IconValue)[] = [
    'value',
    'iconSet',
    'iconSetHandle',
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
    private search = '';
    private cssAttribute = 'class';
    private isFetching = false;
    private isPreloadFetching = false;
    private open = false;

    private wrap!: HTMLElement;
    private chip!: HTMLElement;
    private chipPreview!: HTMLElement;
    private chipLabel!: HTMLElement;
    private chipSpinner!: HTMLElement;
    private searchInput!: HTMLElement;
    private clearButton!: HTMLElement;
    private popover!: HTMLElement;
    private pane!: HTMLElement;
    private statusEl!: HTMLElement;
    private virtualizer: LitVirtualizer<IconItem> | null = null;
    /** Last `size:gap` applied to the virtualizer — avoid rebuilding layout on every open. */
    private layoutKey: string | null = null;
    private paneRefreshQueued = false;
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
        this.triggerId = `icon-picker-${Craft.randomString(10)}`;
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
    }

    destroy(): void {
        window.removeEventListener('resize', this.onResize);
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
        // Open on focus/click of the control; typing filters the grid.
        this.wrap.addEventListener('focusin', () => this.setOpen(true));
        this.wrap.addEventListener('click', (event) => {
            // Clear button handles its own action — don't re-open after clear.
            if ((event.target as HTMLElement).closest('.ipui-icon-input-clear')) {
                return;
            }

            this.setOpen(true);
            (this.searchInput as HTMLElement & { focus?: () => void }).focus?.();
        });

        this.searchInput.addEventListener('input', () => {
            this.search = (this.searchInput as HTMLElement & { value?: string }).value ?? '';
            this.refreshPane();
        });

        this.clearButton.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            this.clear();
        });

        // Chip restore on hide *start* — waiting for `pk-after-hide` ties the valued
        // chrome to exit animation + any main-thread work from a large grid (FA ~2k
        // icons). Virtualization limits DOM, but after-hide still feels lagged.
        this.popover.addEventListener('pk-hide', () => {
            this.open = false;
            this.syncChrome();
        });

        this.popover.addEventListener('pk-after-hide', () => {
            this.open = false;
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

        this.popover.addEventListener('pk-after-show', () => {
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

        this.open = next;
        (this.popover as HTMLElement & { open: boolean }).open = next;
        this.syncChrome();
    }

    private select(icon: IconItem): void {
        this.selected = { ...icon };
        this.syncHiddenInputs();
        this.setOpen(false);
        this.syncChrome();
    }

    private clear(): void {
        this.selected = {};
        this.syncHiddenInputs();
        this.syncChrome();
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
        return Boolean(this.settings.settings?.showLabels);
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
        return this.showLabels
            ? (this.settings.itemWrapperSizeLarge ?? 72)
            : (this.settings.itemWrapperSize ?? 56);
    }

    private get iconSize(): number {
        // Webfont size — BEFORE always uses itemSize (32) for font-size/line-height,
        // even when the labeled box is itemSizeLarge (40). See show-labels CSS.
        return this.settings.itemSize ?? 32;
    }

    private get iconBoxSize(): number {
        return this.showLabels
            ? (this.settings.itemSizeLarge ?? 40)
            : (this.settings.itemSize ?? 32);
    }

    private get iconsFiltered(): IconItem[] {
        if (!this.icons.length) {
            return [];
        }

        const query = this.search.toLowerCase();

        if (!query) {
            return this.icons;
        }

        return this.icons.filter((icon) => (icon.keywords || '').toLowerCase().includes(query));
    }

    // -------------------------------------------------------------------------
    // Pane / virtualizer
    // -------------------------------------------------------------------------

    private refreshPane(): void {
        if (this.isFetching) {
            this.showStatus('loading');
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

        if (this.virtualizer) {
            this.virtualizer.hidden = false;
            // Same array ref → virtualizer no-ops; skip the write so warm re-opens
            // don’t schedule a full range rebuild mid-animation.
            if (this.virtualizer.items !== items) {
                this.virtualizer.items = items;
            }
            this.syncVirtualizerLayout();
        }
    }

    private showStatus(kind: 'loading' | 'empty'): void {
        if (this.virtualizer) {
            // Drop items so a failed `[hidden]` can't leave the previous grid painted
            // under the empty/loading message (same display-vs-hidden trap as the chip).
            this.virtualizer.items = [];
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

        this.statusEl.textContent = Craft.t('icon-picker', 'No icons match your query.');
    }

    private ensureVirtualizer(): void {
        if (this.virtualizer) {
            return;
        }

        const virtualizer = document.createElement('lit-virtualizer') as LitVirtualizer<IconItem>;
        virtualizer.className = 'ipui-icons-scroller';
        virtualizer.scroller = true;
        virtualizer.renderItem = (item: IconItem) => this.renderGridItem(item);
        this.pane.appendChild(virtualizer);
        this.virtualizer = virtualizer;
        this.syncVirtualizerLayout();
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
        // New `grid()` every call tears down layout state and reflows the active
        // range (~100 cells with the default 1000px overhang). Only rebuild when
        // cell metrics change; height/items updates below are cheap.
        if (this.layoutKey !== layoutKey) {
            this.layoutKey = layoutKey;
            this.virtualizer.layout = grid({
                itemSize: {
                    width: `${size}px`,
                    height: `${size}px`,
                },
                // Labeled cells need a hairline gutter so inset focus / 3-line labels
                // don't visually collide with the next row (screen 5).
                gap: gapPx ? `${gapPx}px` : '0px',
                // Default overhang is 1000px (~12 extra rows). Two rows of buffer is
                // enough for scroll smoothness and cuts open-paint cost sharply.
                // lit-virtualizer assigns config onto the layout instance (`_overhang`).
                ...({ _overhang: Math.max(size * 2, 160) } as object),
            });
        }

        this.root.style.setProperty('--ipui-cell-size', `${size}px`);
        // BEFORE: --icon-item-size always drives font-size; --icon-item-size-large only
        // grows the .ipui-icon-svg box when labels are on (wide FA glyphs need that slack).
        this.root.style.setProperty('--ipui-icon-size', `${this.iconSize}px`);
        this.root.style.setProperty('--ipui-icon-size-large', `${this.iconBoxSize}px`);
        this.pane.classList.toggle('show-labels', this.showLabels);

        // Size the scroller to the grid (BEFORE: min 100px / max 50vh), not a fixed 20rem box.
        // lit-virtualizer defaults minHeight to 150px — set both height + minHeight or it wins.
        // Prefer pane/wrap width over virtualizer.clientWidth: once a Y scrollbar appears,
        // clientWidth shrinks and a second pass can thrash column count. Include padding +
        // a classic scrollbar gutter when the grid will hit max-height so columns don’t
        // spill a few px and spawn an X scrollbar (looks like a misaligned Y track).
        const scrollerPad = 10; // .ipui-icons-scroller padding 5px × 2
        const scrollbarGutter = 15;
        const rawWidth = Math.max(this.pane.clientWidth || this.wrap.offsetWidth, size);
        const minHeight = 100;
        const maxHeight = Math.floor(window.innerHeight * 0.5);
        const colsFor = (innerWidth: number) =>
            Math.max(1, Math.floor(Math.max(innerWidth, size) / (size + gapPx)));
        const heightFor = (cols: number) => {
            const rows = Math.max(1, Math.ceil(this.iconsFiltered.length / cols));
            return rows * size + Math.max(0, rows - 1) * gapPx + scrollerPad;
        };

        let cols = colsFor(rawWidth - scrollerPad);
        let contentHeight = heightFor(cols);
        if (contentHeight > maxHeight) {
            cols = colsFor(rawWidth - scrollerPad - scrollbarGutter);
            contentHeight = heightFor(cols);
        }

        const height = Math.min(Math.max(contentHeight, minHeight), maxHeight);
        this.virtualizer.style.height = `${height}px`;
        this.virtualizer.style.minHeight = `${height}px`;
    }

    private renderGridItem(item: IconItem) {
        const label = humanizeLabel(item.label);
        const cssAttribute = this.cssAttribute;
        const showLabels = this.showLabels;

        return html`
            <button
                type="button"
                class="ipui-icon-wrap"
                title=${item.label || ''}
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
                    <use href=${`#${display}`}></use>
                </svg>
            `;
        }

        if (item.type === 'glyph') {
            return html`
                <span class=${`ipui-font font-face-${item.iconSet ?? ''}`}>
                    ${unsafeHTML(display)}
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
                        el.setAttribute(cssAttribute, display);
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
        this.isFetching = !preload;
        this.refreshPane();

        const data = { fieldId: this.settings.fieldId };
        const endpoint = preload
            ? 'icon-picker/icons/resources-for-field'
            : 'icon-picker/icons/icons-for-field';

        try {
            const response = await Craft.sendActionRequest('POST', endpoint, { data });
            const payload = response.data || {};

            if (payload.cssAttribute) {
                this.cssAttribute = payload.cssAttribute;
            }

            if (payload.icons) {
                this.icons = payload.icons as IconItem[];
            }

            loadSpriteSheets(payload.spriteSheets);
            loadFonts(payload.fonts);
            await loadScripts(payload.scripts);
        } catch (error) {
            console.error('[icon-picker] Failed to fetch icons', error);
            this.statusEl.hidden = false;
            this.statusEl.textContent = Craft.t('icon-picker', 'Request failed.');
        } finally {
            this.isFetching = false;
            this.isPreloadFetching = false;
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
