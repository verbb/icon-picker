<?php
namespace verbb\iconpicker\base;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\services\IconSets;
use verbb\iconpicker\services\IconSources;
use verbb\iconpicker\services\Service;
use verbb\iconpicker\web\assets\field\IconPickerAsset;
use verbb\base\BaseHelper;

use Craft;

use yii\log\Logger;

use nystudio107\pluginvite\services\VitePluginService;

trait PluginTrait
{
    // Properties
    // =========================================================================

    public static IconPicker $plugin;


    // Static Methods
    // =========================================================================

    public static function log(string $message, array $params = []): void
    {
        $message = Craft::t('icon-picker', $message, $params);

        Craft::getLogger()->log($message, Logger::LEVEL_INFO, 'icon-picker');
    }

    public static function error(string $message, array $params = []): void
    {
        $message = Craft::t('icon-picker', $message, $params);

        Craft::getLogger()->log($message, Logger::LEVEL_ERROR, 'icon-picker');
    }


    // Public Methods
    // =========================================================================

    public function getIconSets(): IconSets
    {
        return $this->get('iconSets');
    }

    public function getIconSources(): IconSources
    {
        return $this->get('iconSources');
    }

    public function getService(): Service
    {
        return $this->get('service');
    }

    public function getVite(): VitePluginService
    {
        return $this->get('vite');
    }


    // Private Methods
    // =========================================================================

    private function _registerComponents(): void
    {
        $this->setComponents([
            'iconSets' => IconSets::class,
            'iconSources' => IconSources::class,
            'service' => Service::class,
            'vite' => [
                'class' => VitePluginService::class,
                'assetClass' => IconPickerAsset::class,
                'useDevServer' => true,
                'devServerPublic' => 'http://localhost:4005/',
                'errorEntry' => 'js/main.js',
                'cacheKeySuffix' => '',
                'devServerInternal' => 'http://localhost:4005/',
                'checkDevServer' => true,
                'includeReactRefreshShim' => false,
            ],
        ]);

        BaseHelper::registerModule();
    }

    private function _registerLogTarget(): void
    {
        BaseHelper::setFileLogging('icon-picker');
    }

}