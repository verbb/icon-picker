<?php
namespace verbb\iconpicker\utilities;

use verbb\iconpicker\IconPicker;

use Craft;
use craft\base\Utility;

class CacheUtility extends Utility
{
    // Static
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('icon-picker', 'Icon Picker');
    }

    public static function id(): string
    {
        return 'icon-picker-cache';
    }

    public static function iconPath()
    {
        $iconPath = Craft::getAlias('@vendor/verbb/icon-picker/src/icon-mask.svg');

        if (!is_string($iconPath)) {
            return null;
        }

        return $iconPath;
    }

    public static function contentHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('icon-picker/_utility');
    }
}
