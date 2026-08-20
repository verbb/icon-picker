import '@verbb/plugin-kit-web/plugin-kit.css';

// Named deep imports — importing a component module runs its `@customElement`
// registration side effect. Family barrels only re-export `dist/chunks/*` (outside
// package `sideEffects`), so bare side-effect imports get tree-shaken; referencing the
// classes from the registrar keeps the decorator modules in the bundle.
import { PkButton } from '@verbb/plugin-kit-web/components/button/pk-button.js';
import { PkIcon } from '@verbb/plugin-kit-web/components/icon/pk-icon.js';
import { PkInput } from '@verbb/plugin-kit-web/components/input/pk-input.js';
import { PkPopover } from '@verbb/plugin-kit-web/components/popover/pk-popover.js';
import { PkSpinner } from '@verbb/plugin-kit-web/components/spinner/pk-spinner.js';

// Opt-in glyphs for `<pk-icon icon="…">` (JS camelCase keys → kebab lookup names).
import {
    check,
    chevronDown,
    ellipsis,
    plus,
    registerIcons,
    search,
    xmark,
} from '@verbb/plugin-kit-icons';

import { ICON_PICKER_PK_COMPONENTS } from './iconPickerPkComponents.js';

registerIcons({
    check,
    chevronDown,
    ellipsis,
    plus,
    search,
    xmark,
});

/** Constructors whose modules run `@customElement` — must stay reachable so Rollup can't DCE them. */
const ICON_PICKER_PK_CTORS = [PkButton, PkIcon, PkInput, PkPopover, PkSpinner] as const;

let registered = false;

/** Entry hook for the plugin-kit-register bundle. */
export async function registerIconPickerPluginKit(): Promise<void> {
    if (registered) {
        return;
    }

    for (const Ctor of ICON_PICKER_PK_CTORS) {
        if (typeof Ctor !== 'function') {
            throw new Error('Icon Picker Plugin Kit constructor missing from bundle');
        }
    }

    await Promise.all(ICON_PICKER_PK_COMPONENTS.map((tag) => customElements.whenDefined(tag)));
    registered = true;
}
