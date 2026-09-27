<?php
namespace verbb\iconpicker\controllers;

use verbb\iconpicker\IconPicker;

use Craft;
use craft\web\Controller;

use yii\web\Response;

class UtilityController extends Controller
{
    // Public Methods
    // =========================================================================

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
