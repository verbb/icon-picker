<?php
namespace verbb\iconpicker\iconsets;

use verbb\iconpicker\base\RemoteCssIconSet;

use Craft;

class MaterialDesignIcons extends RemoteCssIconSet
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('icon-picker', 'Material Design Icons');
    }


    // Protected Methods
    // =========================================================================

    protected function catalogFiles(): array
    {
        return ['' => 'mdi.json'];
    }

    protected function defaultVersion(): string
    {
        return '7.4.47';
    }

    protected static function cssFontName(): string
    {
        return 'material-design-icons';
    }

    protected function cssUrls(): array|string
    {
        return "https://cdn.jsdelivr.net/npm/@mdi/font@{$this->version()}/css/materialdesignicons.min.css";
    }

    protected function buildCssValue(string $label, ?string $variant = null): string
    {
        return 'mdi mdi-' . $label;
    }
}
