<?php
namespace verbb\iconpicker\iconsets;

use verbb\iconpicker\base\RemoteCssIconSet;

use Craft;

class CssGg extends RemoteCssIconSet
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('icon-picker', 'css.gg');
    }


    // Protected Methods
    // =========================================================================

    protected function catalogFiles(): array
    {
        return ['' => 'css-gg.json'];
    }

    protected function defaultVersion(): string
    {
        return '2.1.4';
    }

    protected static function cssFontName(): string
    {
        return 'css.gg';
    }

    protected function cssUrls(): array|string
    {
        // css.gg 2.x no longer ships icons/all.css in the npm tarball; jsDelivr still
        // serves this legacy path for class-based `gg-*` icons used in saved values.
        return 'https://cdn.jsdelivr.net/npm/css.gg/icons/all.css';
    }

    protected function buildCssValue(string $label, ?string $variant = null): string
    {
        return 'gg-' . $label;
    }
}
