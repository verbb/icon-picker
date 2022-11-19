<?php
namespace verbb\iconpicker\controllers;

use verbb\iconpicker\IconPicker;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\web\Controller;

use yii\web\Response;

class IconsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIconsForField(): ?Response
    {
        return $this->_getIconSetData();
    }

    public function actionResourcesForField(): ?Response
    {
        return $this->_getIconSetData(false);
    }


    // Private Methods
    // =========================================================================

    private function _getIconSetData(bool $includeIcons = true): ?Response
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

        $json = [
            'icons' => [],
            'fonts' => [],
            'spriteSheets' => [],
            'scripts' => [],
        ];

        // Combine all icons, fonts, spritesheets, etc
        foreach ($iconSets as $key => $iconSet) {
            if ($includeIcons) {
                $json['icons'] = array_merge($json['icons'], $iconSet->icons);
            }

            $json['fonts'] = array_merge($json['fonts'], $iconSet->fonts);
            $json['spriteSheets'] = array_merge($json['spriteSheets'], $iconSet->getSpriteSheets());
            $json['scripts'] = array_merge($json['scripts'], $iconSet->scripts);
            $json['cssAttribute'] = $iconSet->cssAttribute;
        }

        return $this->asJson($json);
    }

}
