<?php
namespace verbb\iconpicker\controllers;

use verbb\iconpicker\IconPicker;

use Craft;
use craft\helpers\Html;
use craft\web\Controller;

use yii\web\Response;

class RedactorController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        $settings = IconPicker::$plugin->getSettings();
        $request = Craft::$app->getRequest();

        $field = Craft::$app->getFields()->getFieldByHandle($settings->redactorFieldHandle);

        Craft::$app->getView()->startJsBuffer();
        $inputHtml = Html::tag('div', Html::tag('div', $field->getInputHtml(null), [
            'class' => 'input',
        ]), [
            'id' => 'iconPickerRedactor-field'
        ]);
        $footHtml = Craft::$app->getView()->clearJsBuffer();

        return $this->asJson([
            'inputHtml' => $inputHtml,
            'footHtml' => $footHtml,
        ]);
    }

}
