<?php
namespace verbb\iconpicker\assetbundles;

use craft\redactor\assets\redactor\RedactorAsset;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

use verbb\base\assetbundles\CpAsset as VerbbCpAsset;

class IconPickerRedactorAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->sourcePath = "@verbb/iconpicker/resources/dist";

        $this->depends = [
            VerbbCpAsset::class,
            CpAsset::class,
            RedactorAsset::class,
        ];

        $this->js = [
            'js/icon-picker.js',
        ];

        $this->css = [
            'css/icon-picker.css',
        ];

        parent::init();
    }
}
