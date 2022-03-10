<?php
namespace verbb\iconpicker\models;

use Craft;
use craft\base\Model;
use craft\helpers\App;
use craft\helpers\FileHelper;

class Settings extends Model
{
    // Properties
    // =========================================================================

    public bool $enableCache = true;
    public string $iconSetsPath = '@webroot/icons/';
    public string $iconSetsUrl = '@web/icons/';
    public int $maxIconsShown = 100;
    public string $redactorFieldHandle = '';


    // Public Methods
    // =========================================================================

    public function getIconSetsPath(): string
    {
        if ($this->iconSetsPath) {
            return FileHelper::normalizePath(App::parseEnv($this->iconSetsPath));
        }

        return $this->iconSetsPath;
    }

    public function getIconSetsUrl(): bool|string|null
    {
        return App::parseEnv($this->iconSetsUrl);
    }

}
