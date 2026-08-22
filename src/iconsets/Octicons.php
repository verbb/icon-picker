<?php
namespace verbb\iconpicker\iconsets;

use verbb\iconpicker\base\RemoteSvgIconSet;

use Craft;

class Octicons extends RemoteSvgIconSet
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('icon-picker', 'Octicons');
    }


    // Protected Methods
    // =========================================================================

    protected function catalogMap(): array
    {
        return [
            '24' => 'octicons-24.json',
        ];
    }

    protected function defaultVariant(): string
    {
        return '24';
    }

    protected function defaultVersion(): string
    {
        return '19.12.0';
    }

    protected function buildSvgUrl(string $iconName, string $variant): string
    {
        return "https://cdn.jsdelivr.net/npm/@primer/octicons@{$this->version()}/build/svg/{$iconName}.svg";
    }
}
