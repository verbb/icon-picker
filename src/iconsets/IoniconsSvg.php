<?php
namespace verbb\iconpicker\iconsets;

use verbb\iconpicker\base\RemoteSvgIconSet;

use Craft;

class IoniconsSvg extends RemoteSvgIconSet
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('icon-picker', 'Ionicons');
    }


    // Protected Methods
    // =========================================================================

    protected function getVariantSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('icon-picker/icon-sets/ionicons-svg', [
            'iconSet' => $this,
        ]);
    }

    protected function catalogMap(): array
    {
        return [
            'default' => 'ionicons-modern-default.json',
            'outline' => 'ionicons-modern-outline.json',
            'sharp' => 'ionicons-modern-sharp.json',
        ];
    }

    protected function defaultVariant(): string
    {
        return 'default';
    }

    protected function defaultVersion(): string
    {
        return '8.1.0';
    }

    protected function buildSvgUrl(string $iconName, string $variant): string
    {
        return "https://cdn.jsdelivr.net/npm/ionicons@{$this->version()}/dist/ionicons/svg/{$iconName}.svg";
    }
}
