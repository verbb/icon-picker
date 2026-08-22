<?php
namespace verbb\iconpicker\iconsets;

use verbb\iconpicker\base\RemoteSvgIconSet;

use Craft;

class Heroicons extends RemoteSvgIconSet
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('icon-picker', 'Heroicons');
    }


    // Public Methods
    // =========================================================================

    protected function getVariantSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('icon-picker/icon-sets/heroicons', [
            'iconSet' => $this,
        ]);
    }


    // Protected Methods
    // =========================================================================

    protected function catalogMap(): array
    {
        return [
            'outline' => 'heroicons-outline.json',
            'solid' => 'heroicons-solid.json',
        ];
    }

    protected function defaultVariant(): string
    {
        return 'outline';
    }

    protected function defaultVersion(): string
    {
        return '2.2.0';
    }

    protected function buildSvgUrl(string $iconName, string $variant): string
    {
        $folder = $variant === 'solid' ? 'solid' : 'outline';

        return "https://cdn.jsdelivr.net/npm/heroicons@{$this->version()}/24/{$folder}/{$iconName}.svg";
    }
}
