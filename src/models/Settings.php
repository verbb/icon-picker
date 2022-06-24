<?php
namespace verbb\iconpicker\models;

use verbb\iconpicker\IconPicker;

use Craft;
use craft\base\Model;
use craft\helpers\FileHelper;

class Settings extends Model
{
    // Public Properties
    // =========================================================================

    public $iconSetsPath = '@webroot/icons/';
    public $iconSetsUrl = '@web/icons/';
    public $maxIconsShown = 100;
    public $redactorFieldHandle = '';
    public $enableCache = true;


    // Public Methods
    // =========================================================================

    public function getIconSetsPath()
    {
        if ($this->iconSetsPath) {
            return FileHelper::normalizePath(Craft::parseEnv($this->iconSetsPath));
        }

        return $this->iconSetsPath;
    }

    public function getIconSetsUrl()
    {
        return Craft::parseEnv($this->iconSetsUrl);
    }

}
