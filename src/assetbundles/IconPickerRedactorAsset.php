<?php
namespace verbb\iconpicker\assetbundles;

use Craft;
use craft\redactor\assets\redactor\RedactorAsset;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class IconPickerRedactorAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->sourcePath = "@verbb/iconpicker/resources/dist";

        $this->depends = [
            CpAsset::class,
            RedactorAsset::class,
        ];

        $this->js = [
            'js/icon-picker.js',
            'js/icon-picker-redactor.js',
        ];

        $this->css = [
            'css/icon-picker.css',
            'css/icon-picker-redactor.css'
        ];

        parent::init();
    }
}
