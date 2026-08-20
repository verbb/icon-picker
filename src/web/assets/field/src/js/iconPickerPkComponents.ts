// Plugin Kit web components the Icon Picker field relies on. The register bundle
// defines these before the app entry queries them, and the app entry `allDefined()`-gates
// on this list so we never touch a `pk-*` element before it has upgraded.
export const ICON_PICKER_PK_COMPONENTS = [
    'pk-icon',
    'pk-button',
    'pk-input',
    'pk-popover',
    'pk-spinner',
] as const;
