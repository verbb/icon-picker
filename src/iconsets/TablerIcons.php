<?php
namespace verbb\iconpicker\iconsets;

use verbb\iconpicker\base\RemoteSvgIconSet;

use Craft;

class TablerIcons extends RemoteSvgIconSet
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('icon-picker', 'Tabler Icons');
    }


    // Public Methods
    // =========================================================================

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('icon-picker/icon-sets/tabler-icons', [
            'iconSet' => $this,
        ]);
    }


    // Protected Methods
    // =========================================================================

    protected function catalogMap(): array
    {
        return [
            'outline' => 'tabler-outline.json',
            'filled' => 'tabler-filled.json',
        ];
    }

    protected function defaultVariant(): string
    {
        return 'outline';
    }

    protected function defaultVersion(): string
    {
        return '3.28.1';
    }

    protected function buildSvgUrl(string $iconName, string $variant): string
    {
        $folder = $variant === 'filled' ? 'filled' : 'outline';

        return "https://cdn.jsdelivr.net/npm/@tabler/icons@{$this->version()}/icons/{$folder}/{$iconName}.svg";
    }
}
