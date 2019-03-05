<?php
namespace verbb\iconpicker\variables;

use verbb\iconpicker\IconPicker;

use Craft;
use craft\helpers\FileHelper;
use craft\helpers\Template;

class IconPickerVariable
{
    // Public Methods
    // =========================================================================

    public function getIcon($icon)
    {
        return IconPicker::$plugin->getService()->getModel($icon);
    }

    public function getDimensions($icon, $height = null)
    {
        return $this->getIcon($icon)->getDimensions($height);
    }

    public function inline($icon)
    {
        return $this->getIcon($icon)->getInline();
    }

    public function spritesheet($path)
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsUrl = $settings->iconSetsUrl;

        $url = FileHelper::normalizePath($iconSetsUrl . DIRECTORY_SEPARATOR . $path);
        $url = Craft::getAlias($url);

        $sheet = file_get_contents($url);

        return Template::raw($sheet);
    }

    public function fontUrl($path)
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsUrl = $settings->iconSetsUrl;

        $url = FileHelper::normalizePath($iconSetsUrl . DIRECTORY_SEPARATOR . $path);
        $url = Craft::getAlias($url);

        return $url;
    }
}
