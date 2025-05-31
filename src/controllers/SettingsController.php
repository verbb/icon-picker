<?php
namespace verbb\iconpicker\controllers;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\models\Settings;

use Craft;
use craft\web\Controller;

use yii\web\Response;

class SettingsController extends Controller
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

    public function actionSaveSettings(): ?Response
    {
        $this->requirePostRequest();

        /* @var Settings $settings */
        $settings = IconPicker::$plugin->getSettings();
        $settings->setAttributes($this->request->getParam('settings'), false);

        if (!$settings->validate()) {
            Craft::$app->getSession()->setError(Craft::t('icon-picker', 'Couldn’t save settings.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings,
            ]);

            return null;
        }

        $pluginSettingsSaved = Craft::$app->getPlugins()->savePluginSettings(IconPicker::$plugin, $settings->toArray());

        if (!$pluginSettingsSaved) {
            Craft::$app->getSession()->setError(Craft::t('icon-picker', 'Couldn’t save settings.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('icon-picker', 'Settings saved.'));

        return $this->redirectToPostedUrl();
    }

    public function actionClearCache(): Response
    {
        IconPicker::$plugin->getService()->clearAndRegenerateCache();

        Craft::$app->getSession()->setNotice(Craft::t('icon-picker', 'Icon set cache re-generation started.'));

        return $this->redirectToPostedUrl();
    }

    public function actionTroubleshoot(): ?Response
    {
        $selectedHandles = Craft::$app->getRequest()->getBodyParam('iconSets', []);
        $allIconSets = IconPicker::$plugin->getIconSets()->getAllIconSets();

        // Filter icon sets by selected handles
        if ($selectedHandles === '*' || (is_array($selectedHandles) && in_array('*', $selectedHandles))) {
            $iconSets = $allIconSets;
        } else {
            $iconSets = array_filter($allIconSets, fn($handle) => in_array($handle, $selectedHandles), ARRAY_FILTER_USE_KEY);
        }

        $data = IconPicker::$plugin->getTroubleshoot()->runDiagnostics($iconSets);

        // The only way to get data returned in a utility
        Craft::$app->getSession()->setFlash('iconpickerTroubleshooterData', $data);

        return $this->redirectToPostedUrl();
    }

}
