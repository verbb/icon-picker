<?php
namespace verbb\iconpicker\variables;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\helpers\IconPickerHelper;
use verbb\iconpicker\models\Icon;

use craft\helpers\Template;

use Twig\Markup;

class IconPickerVariable
{
    // Public Methods
    // =========================================================================

    public function getIcon($icon): Icon
    {
        return IconPicker::$plugin->getService()->getModel($icon);
    }

    public function getDimensions($icon, $height = null): array
    {
        return $this->getIcon($icon)->getDimensions($height);
    }

    public function inline($icon): string|Markup
    {
        return $this->getIcon($icon)->getInline();
    }

    public function spritesheet($path): Markup
    {
        $url = IconPickerHelper::getIconUrl($path);
        $sheet = IconPickerHelper::getFileContents($url);

        return Template::raw($sheet);
    }

    public function fontUrl($path): string
    {
        return IconPickerHelper::getIconUrl($path);
    }
}
