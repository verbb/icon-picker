<?php
namespace verbb\iconpicker\iconsets;

use verbb\iconpicker\base\RemoteCssIconSet;

use Craft;

class BootstrapIcons extends RemoteCssIconSet
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('icon-picker', 'Bootstrap Icons');
    }


    // Protected Methods
    // =========================================================================

    protected function catalogFiles(): array
    {
        return ['' => 'bootstrap-icons.json'];
    }

    protected function defaultVersion(): string
    {
        return '1.13.1';
    }

    protected static function cssFontName(): string
    {
        return 'bootstrap-icons';
    }

    protected function cssUrls(): array|string
    {
        return "https://cdn.jsdelivr.net/npm/bootstrap-icons@{$this->version()}/font/bootstrap-icons.min.css";
    }

    protected function buildCssValue(string $label, ?string $variant = null): string
    {
        return 'bi bi-' . $label;
    }
}
