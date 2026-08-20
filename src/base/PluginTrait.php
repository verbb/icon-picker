<?php
namespace verbb\iconpicker\base;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\services\IconSets;
use verbb\iconpicker\services\IconSources;
use verbb\iconpicker\services\Service;
use verbb\iconpicker\services\Troubleshoot;
use verbb\iconpicker\web\assets\field\IconPickerAsset;

use verbb\base\LogTrait;
use verbb\base\helpers\Plugin;

use craft\helpers\App;

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
                'troubleshoot' => Troubleshoot::class,
                'vite' => [
                    'class' => VitePluginService::class,
                    'assetClass' => IconPickerAsset::class,
                    'useDevServer' => App::parseBooleanEnv('$ICON_PICKER_USE_VITE_DEV_SERVER') ?? false,
                    'devServerPublic' => 'http://localhost:4005/',
                    'errorEntry' => 'field/src/js/icon-picker.ts',
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

    public function getTroubleshoot(): Troubleshoot
    {
        return $this->get('troubleshoot');
    }

    public function getVite(): VitePluginService
    {
        return $this->get('vite');
    }

}