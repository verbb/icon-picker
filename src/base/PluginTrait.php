<?php
namespace verbb\iconpicker\base;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\services\Cache;
use verbb\iconpicker\services\IconSources;
use verbb\iconpicker\services\Service;

use Craft;
use craft\log\FileTarget;
use craft\web\View;

use yii\base\Event;
use yii\log\Logger;

trait PluginTrait
{
    // Static Properties
    // =========================================================================

    public static $plugin;


    // Public Methods
    // =========================================================================

    public function getCache()
    {
        return $this->get('cache');
    }

    public function getIconSources()
    {
        return $this->get('iconSources');
    }

    public function getService()
    {
        return $this->get('service');
    }

    public static function log($message)
    {
        Craft::getLogger()->log($message, Logger::LEVEL_INFO, 'icon-picker');
    }

    public static function error($message)
    {
        Craft::getLogger()->log($message, Logger::LEVEL_ERROR, 'icon-picker');
    }


    // Private Methods
    // =========================================================================

    private function _setPluginComponents()
    {
        $this->setComponents([
            'cache' => Cache::class,
            'iconSources' => IconSources::class,
            'service' => Service::class,
        ]);
    }

    private function _setLogging()
    {
        Craft::getLogger()->dispatcher->targets[] = new FileTarget([
            'logFile' => Craft::getAlias('@storage/logs/icon-picker.log'),
            'categories' => ['icon-picker'],
        ]);
    }

}