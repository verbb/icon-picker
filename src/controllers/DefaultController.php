<?php
namespace verbb\iconpicker\controllers;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\migrations\SvgIconsPlugin;

use Craft;
use craft\web\Controller;

use yii\web\Response;

class DefaultController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionMigrate()
    {
        // Backup!
        Craft::$app->getDb()->backup();

        $migration = new SvgIconsPlugin();

        ob_start();
        $migration->up();
        $output = ob_get_contents();
        ob_end_clean();

        Craft::$app->getSession()->setNotice(Craft::t('icon-picker', 'SVG Icons fields migrated.'));

        return $this->redirect('icon-picker/settings');
    }

    public function actionClearCache()
    {
        IconPicker::$plugin->getCache()->clearAndRegenerate();

        Craft::$app->getSession()->setNotice(Craft::t('icon-picker', 'Icon Set cache re-generation started.'));

        return $this->redirectToPostedUrl();
    }

    public function actionSettings()
    {
        $settings = IconPicker::$plugin->getSettings();

        return $this->renderTemplate('icon-picker/settings', [
            'settings' => $settings,
        ]);
    }

}
