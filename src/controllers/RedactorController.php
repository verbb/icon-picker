<?php
namespace verbb\iconpicker\controllers;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\migrations\SvgIconsPlugin;

use Craft;
use craft\web\Controller;

use yii\web\Response;

class RedactorController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex()
    {
        $settings = IconPicker::$plugin->getSettings();
        $request = Craft::$app->getRequest();

        $field = Craft::$app->getFields()->getFieldByHandle($settings->redactorFieldHandle);

        Craft::$app->getView()->startJsBuffer();
        $inputHtml = $field->getInputHtml(null);
        $footHtml = Craft::$app->getView()->clearJsBuffer();

        return $this->asJson([
            'inputHtml' => $inputHtml,
            'footHtml' => $footHtml,
        ]);
    }

}
