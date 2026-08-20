<?php
namespace verbb\iconpicker\helpers;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\web\assets\field\IconPickerAsset;

class Plugin
{
    // Static Methods
    // =========================================================================

    public static function registerAsset(string $path): void
    {
        $viteService = IconPicker::$plugin->getVite();

        $scriptOptions = [
            'depends' => [
                IconPickerAsset::class,
            ],
            'onload' => '',
        ];

        $styleOptions = [
            'depends' => [
                IconPickerAsset::class,
            ],
        ];

        $viteService->register($path, false, $scriptOptions, $styleOptions);

        // Provide nice build errors - only in dev
        if ($viteService->devServerRunning()) {
            $viteService->register('@vite/client', false);
        }
    }

    public static function registerFieldAssets(): void
    {
        // Register the Plugin Kit web components before the field app mounts, so custom
        // element upgrades are page-level asset work rather than per-field init work.
        self::registerAsset('field/src/js/plugin-kit-register.ts');
        self::registerAsset('field/src/js/icon-picker.ts');
    }

}
