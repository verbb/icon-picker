<?php
namespace verbb\iconpicker\controllers;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\models\Settings;

use yii\web\Response;

use verbb\base\controllers\SettingsController as BaseSettingsController;

class SettingsController extends BaseSettingsController
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        $settings = IconPicker::$plugin->getSettings();

        return $this->renderTemplate('icon-picker/settings/general', [
            'settings' => $settings,
        ]);
    }

}
