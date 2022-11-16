<?php
namespace verbb\iconpicker\controllers;

use verbb\iconpicker\IconPicker;

use Craft;
use craft\helpers\ArrayHelper;
use craft\web\Controller;

use yii\web\Response;

class IconsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIconsForField(): Response
    {
        $request = Craft::$app->getRequest();

        // Ensure we look at all fields, not just global ones...
        $fieldsById = ArrayHelper::index(Craft::$app->getFields()->getAllFields(false), 'id');

        $fieldId = $request->getRequiredParam('fieldId');
        $field = $fieldsById[$fieldId] ?? null;

        $fieldId = $request->getRequiredParam('fieldId');
        $field = Craft::$app->getFields()->getFieldById($fieldId);

        if (!$field) {
            return $this->asFailure('Unable to find field #' . $fieldId);
        }

        $iconSets = IconPicker::$plugin->getService()->getIconsForField($field);

        return $this->asJson($iconSets);
    }

}
