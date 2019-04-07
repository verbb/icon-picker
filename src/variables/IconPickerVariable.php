<?php
namespace verbb\iconpicker\variables;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\helpers\IconPickerHelper;

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
        $url = IconPickerHelper::getIconUrl($path);
        $sheet = IconPickerHelper::getFileContents($url);

        return Template::raw($sheet);
    }

    public function fontUrl($path)
    {
        return IconPickerHelper::getIconUrl($path);
    }
}
