<?php
namespace verbb\iconpicker\utilities;

use Craft;
use craft\base\Utility;

class CacheUtility extends Utility
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('icon-picker', 'Icon Picker');
    }

    public static function id(): string
    {
        return 'icon-picker-cache';
    }

    public static function icon(): ?string
    {
        return '@verbb/iconpicker/icon-mask.svg';
    }

    public static function contentHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('icon-picker/_utility');
    }
}
