<?php
namespace verbb\iconpicker\utilities;

use verbb\iconpicker\IconPicker;

use Craft;
use craft\base\Utility;

class IconsUtility extends Utility
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('icon-picker', 'Icon Picker');
    }

    public static function id(): string
    {
        return 'icon-picker';
    }

    public static function icon(): ?string
    {
        return '@verbb/iconpicker/icon-mask.svg';
    }

    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();
        $data = Craft::$app->getSession()->getFlash('iconpickerTroubleshooterData') ?? [];
        $iconSets = IconPicker::$plugin->getIconSets()->getAllIconSets();

        return $view->renderTemplate('icon-picker/_utility', [
            'iconSets' => $iconSets,
            'data' => $data,
        ]);
    }
}
