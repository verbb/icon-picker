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

}
