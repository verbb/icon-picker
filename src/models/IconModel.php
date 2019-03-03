<?php
namespace verbb\iconpicker\models;

use verbb\iconpicker\IconPicker;

use Craft;
use craft\base\Model;
use craft\helpers\FileHelper;

class IconModel extends Model
{
    // Properties
    // =========================================================================

    public $icon;
    public $type;
    public $width;
    public $height;


    // Public Methods
    // =========================================================================

    public function getDimensions($height = null)
    {
        return IconPicker::$plugin->getService()->getDimensions($this->icon, $height);
    }

    public function getUrl()
    {
        $iconSetsUrl = IconPicker::$plugin->getSettings()->iconSetsUrl;

        $url = FileHelper::normalizePath($iconSetsUrl . DIRECTORY_SEPARATOR . $this->icon);
        $url = Craft::getAlias($url);

        return $url;
    }

    public function getInline()
    {
        return IconPicker::$plugin->getService()->inline($this->icon);
    }

}
