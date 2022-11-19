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
    public string $iconSetsPath = '@webroot/icon-picker/';
    public string $iconSetsUrl = '@web/icon-picker/';
    public string $redactorFieldHandle = '';
    public array $iconSources = [];


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Config normalization
        if (array_key_exists('maxIconsShown', $config)) {
            unset($config['maxIconsShown']);
        }

        parent::__construct($config);
    }

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
