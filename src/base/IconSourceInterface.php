<?php
namespace verbb\iconpicker\base;

use verbb\iconpicker\models\IconSet;

use craft\base\ComponentInterface;

interface IconSourceInterface extends ComponentInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string;
    public static function hasSettings(): bool;


    // Public Methods
    // =========================================================================

    public function getSettingsHtml(): ?string;
    public function getIconSets(): array;
    public function getIcons(IconSet $iconSet): void;
}
