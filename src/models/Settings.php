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

    public $iconSetsPath = CRAFT_BASE_PATH . '/web/icons/';
    public $iconSetsUrl = '/icons/';
    public $maxIconsShown = 100;
    public $redactorFieldHandle = '';
    public $enableCache = true;
    
    
    // Public Methods
    // =========================================================================

    public function __construct()
    {
        if ($this->iconSetsPath) {
            $this->iconSetsPath = FileHelper::normalizePath($this->iconSetsPath);
        }
    }

}
