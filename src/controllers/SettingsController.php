<?php
namespace verbb\iconpicker\controllers;

use verbb\iconpicker\IconPicker;

use yii\web\Response;

use verbb\base\controllers\SettingsController as BaseSettingsController;

class SettingsController extends BaseSettingsController
{
    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // The shared controller authorizes admins; this plugin also treats settings as admin changes.
        $this->requireAdmin(true);

        return true;
    }

    public function actionIndex(): Response
    {
        $settings = IconPicker::$plugin->getSettings();

        return $this->renderTemplate('icon-picker/settings/general', [
            'settings' => $settings,
        ]);
    }
}
