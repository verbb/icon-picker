<?php
namespace verbb\iconpicker\controllers;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\fields\IconPickerField;

use Craft;
use craft\helpers\Html;
use craft\helpers\StringHelper;

use yii\web\BadRequestHttpException;
use yii\web\Response;

class RedactorController extends IconsController
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        $view = Craft::$app->getView();

        $field = $this->getField();
        $field->renderId = Html::id($field->handle) . '-' . StringHelper::randomString(10);

        $view->startJsBuffer();

        $inputHtml = Html::tag('div', Html::tag('div', $field->getRedactorInputHtml(), [
            'class' => 'input',
        ]), [
            'id' => $field->renderId . '-field'
        ]);

        $footHtml = $view->clearJsBuffer();

        $footHtml .= IconPicker::$plugin->getVite()->script('field/src/js/plugin-kit-register.ts');
        $footHtml .= IconPicker::$plugin->getVite()->script('field/src/js/icon-picker.ts');

        return $this->asJson([
            'inputHtml' => $inputHtml,
            'footHtml' => $footHtml,
        ]);
    }

    // Protected Methods
    // =========================================================================

    protected function getField(): IconPickerField
    {
        // Redactor deliberately borrows one administrator-configured field rather
        // than requiring that field on every rich-text element's layout.
        $handle = IconPicker::$plugin->getSettings()->redactorFieldHandle;
        $field = Craft::$app->getFields()->getFieldByHandle($handle);

        if (!$field instanceof IconPickerField) {
            throw new BadRequestHttpException(Craft::t('icon-picker', 'Configure an Icon Picker field for Redactor in the plugin settings.'));
        }

        return $field;
    }
}
