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
    public int $iconItemWrapperSize = 56;
    public int $iconItemWrapperSizeLarge = 72;
    public int $iconItemSize = 32;
    public int $iconItemSizeLarge = 40;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Config normalization
        if (array_key_exists('maxIconsShown', $config)) {
            unset($config['maxIconsShown']);
        }

        if (array_key_exists('iconSources', $config)) {
            unset($config['iconSources']);
        }

        parent::__construct($config);
    }

    public function getIconSetsPath(): string
    {
        $path = $this->iconSetsPath;

        if ($path) {
            $path = FileHelper::normalizePath(App::parseEnv($path));
        }

        // Create the path if not set
        if (!is_dir($path)) {
            FileHelper::createDirectory($path);
        }

        return $path;
    }

    public function getIconSetsUrl(): bool|string|null
    {
        return App::parseEnv($this->iconSetsUrl);
    }

}
