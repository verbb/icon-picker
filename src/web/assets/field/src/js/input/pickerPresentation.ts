export type LabelDisplay = 'hidden' | 'tooltip' | 'below';
export type IconSize = 'small' | 'default' | 'large';

interface PresentationSettings {
    settings?: { showLabels?: boolean; labelDisplay?: LabelDisplay | null; iconSize?: IconSize };
    itemSize?: number;
    itemSizeLarge?: number;
    itemWrapperSize?: number;
    itemWrapperSizeLarge?: number;
}

/** Presets scale the configured defaults while keeping below-icon labels readable. */
export function pickerPresentation(settings: PresentationSettings) {
    const labelDisplay = settings.settings?.labelDisplay ?? (settings.settings?.showLabels ? 'below' : 'tooltip');
    const showLabels = labelDisplay === 'below';
    const size = settings.settings?.iconSize;
    const scale = size === 'small' ? 0.75 : size === 'large' ? 1.5 : 1;
    const baseIcon = settings.itemSize ?? 32;
    const baseBox = showLabels ? (settings.itemSizeLarge ?? 40) : baseIcon;
    const baseCell = showLabels ? (settings.itemWrapperSizeLarge ?? 72) : (settings.itemWrapperSize ?? 56);
    const iconSize = Math.round(baseIcon * scale);
    const iconBoxSize = Math.round(baseBox * scale);
    // Labels keep their usual width/height at Small; Large adds room for the artwork.
    const cellSize = baseCell + (showLabels ? Math.max(0, iconBoxSize - baseBox) : iconBoxSize - baseBox);
    return { labelDisplay, showLabels, iconSize, iconBoxSize, cellSize };
}
