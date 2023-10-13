<?php
namespace verbb\iconpicker\base;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\services\IconSets;
use verbb\iconpicker\services\IconSources;
use verbb\iconpicker\services\Service;
use verbb\iconpicker\web\assets\field\IconPickerAsset;

use verbb\base\LogTrait;
use verbb\base\helpers\Plugin;

use nystudio107\pluginvite\services\VitePluginService;

trait PluginTrait
{
    // Properties
    // =========================================================================

    public static ?IconPicker $plugin = null;


    // Traits
    // =========================================================================

    use LogTrait;
    

    // Static Methods
    // =========================================================================

    public static function config(): array
    {
        Plugin::bootstrapPlugin('icon-picker');

        return [
            'components' => [
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
            ],
        ];
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

}