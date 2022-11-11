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
