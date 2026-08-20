<?php
namespace verbb\iconpicker\controllers;

use verbb\iconpicker\IconPicker;

use Craft;
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
        $fieldId = $this->request->getRequiredParam('fieldId');
        $field = Craft::$app->getFields()->getFieldById($fieldId);

        if (!$field) {
            return $this->asFailure('Unable to find field #' . $fieldId);
        }

        $json = [
            'icons' => [],
            'fonts' => [],
            'spriteSheets' => [],
            'scripts' => [],
        ];

        $iconSets = IconPicker::$plugin->getIconSets()->getIconSetsForField($field);

        foreach ($iconSets as $iconSet) {
            if ($includeIcons) {
                $iconSet->populateIcons();
                $json['icons'] = array_merge($json['icons'], $iconSet->icons);
            } else {
                // Chip preload for non-SVG values — skip catalog hydration.
                $iconSet->populateResources();
            }

            $json['fonts'] = array_merge($json['fonts'], $iconSet->fonts);
            $json['spriteSheets'] = array_merge($json['spriteSheets'], $iconSet->getSpriteSheets());
            $json['scripts'] = array_merge($json['scripts'], $iconSet->scripts);
            $json['cssAttribute'] = $iconSet->cssAttribute;
        }

        return $this->asJson($json);
    }

}
