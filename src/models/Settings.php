<?php
namespace verbb\iconpicker\models;

use verbb\iconpicker\IconPicker;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    // Public Properties
    // =========================================================================

    public $iconSetsPath = CRAFT_BASE_PATH . '/web/icons/';
    public $iconSetsUrl = '/icons/';
    public $maxIconsShown = 100;
    public $redactorFieldHandle = '';

}
