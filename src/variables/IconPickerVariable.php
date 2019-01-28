<?php
namespace verbb\iconpicker\variables;

use verbb\iconpicker\IconPicker;

use Craft;
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
}
