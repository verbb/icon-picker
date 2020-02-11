<?php
namespace verbb\iconpicker\assetbundles;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

use verbb\base\assetbundles\CpAsset as VerbbCpAsset;

class IconPickerAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->sourcePath = "@verbb/iconpicker/resources/dist";

        $this->depends = [
            VerbbCpAsset::class,
            CpAsset::class,
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
