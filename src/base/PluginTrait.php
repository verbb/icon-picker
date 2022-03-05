<?php
namespace verbb\iconpicker\base;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\services\Cache;
use verbb\iconpicker\services\IconSources;
use verbb\iconpicker\services\Service;

use Craft;

use yii\log\Logger;

use verbb\base\BaseHelper;

trait PluginTrait
{
    // Properties
    // =========================================================================

    public static IconPicker $plugin;


    // Static Methods
    // =========================================================================

    public static function log($message): void
    {
        Craft::getLogger()->log($message, Logger::LEVEL_INFO, 'icon-picker');
    }

    public static function error($message): void
    {
        Craft::getLogger()->log($message, Logger::LEVEL_ERROR, 'icon-picker');
    }


    // Public Methods
    // =========================================================================

    public function getCache(): Cache
    {
        return $this->get('cache');
    }

    public function getIconSources(): IconSources
    {
        return $this->get('iconSources');
    }

    public function getService(): Service
    {
        return $this->get('service');
    }


    // Private Methods
    // =========================================================================

    private function _setPluginComponents(): void
    {
        $this->setComponents([
            'cache' => Cache::class,
            'iconSources' => IconSources::class,
            'service' => Service::class,
        ]);

        BaseHelper::registerModule();
    }

    private function _setLogging(): void
    {
        BaseHelper::setFileLogging('icon-picker');
    }

}