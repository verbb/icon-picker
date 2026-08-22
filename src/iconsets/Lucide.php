<?php
namespace verbb\iconpicker\iconsets;

use verbb\iconpicker\base\RemoteSvgIconSet;

use Craft;

class Lucide extends RemoteSvgIconSet
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('icon-picker', 'Lucide');
    }


    // Protected Methods
    // =========================================================================

    protected function catalogMap(): array
    {
        return [
            'default' => 'lucide.json',
        ];
    }

    protected function defaultVariant(): string
    {
        return 'default';
    }

    protected function defaultVersion(): string
    {
        return '1.33.0';
    }

    protected function buildSvgUrl(string $iconName, string $variant): string
    {
        return "https://cdn.jsdelivr.net/npm/lucide-static@{$this->version()}/icons/{$iconName}.svg";
    }
}
