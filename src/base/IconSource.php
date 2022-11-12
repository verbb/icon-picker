<?php
namespace verbb\iconpicker\base;

use verbb\iconpicker\models\IconSet;

use craft\base\Component;

abstract class IconSource extends Component implements IconSourceInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('icon-picker', 'Icon Source');
    }

    public static function hasSettings(): bool
    {
        return false;
    }


    // Public Methods
    // =========================================================================

    public function getSettingsHtml(): ?string
    {
        return null;
    }

    public function getIconSets(): array
    {
        return [];
    }

    public function getIcons(IconSet $iconSet): void
    {
        return;
    }
}