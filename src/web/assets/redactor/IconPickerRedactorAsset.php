<?php
namespace verbb\iconpicker\web\assets\redactor;

use verbb\iconpicker\helpers\Plugin;

use craft\redactor\assets\redactor\RedactorAsset;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

use verbb\base\web\assets\cp\CpAsset as VerbbCpAsset;

class IconPickerRedactorAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->sourcePath = '@verbb/iconpicker/web/assets/redactor/dist';

        $this->depends = [
            VerbbCpAsset::class,
            CpAsset::class,
            RedactorAsset::class,
        ];

        $this->js = [
            'icon-picker.js',
        ];

        $this->css = [
            'icon-picker.css',
        ];

        parent::init();

        Plugin::registerFieldAssets();
    }
}
