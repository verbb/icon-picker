<?php
namespace verbb\iconpicker\controllers;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\fields\IconPickerField;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\web\Controller;

use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class IconsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requireCpRequest();
        $this->requirePostRequest();

        return true;
    }

    public function actionIconsForField(): ?Response
    {
        return $this->_getIconSetData();
    }

    public function actionResourcesForField(): ?Response
    {
        return $this->_getIconSetData(false);
    }


    // Protected Methods
    // =========================================================================

    protected function getField(): IconPickerField
    {
        $fieldId = (int)$this->request->getRequiredParam('fieldId');
        $field = Craft::$app->getFields()->getFieldById($fieldId);

        if (!$field instanceof IconPickerField) {
            throw new BadRequestHttpException(Craft::t('icon-picker', 'Invalid Icon Picker field.'));
        }

        $elementId = (int)$this->request->getRequiredParam('elementId');
        $siteId = (int)($this->request->getParam('siteId') ?: Craft::$app->getSites()->getCurrentSite()->id);
        $element = Craft::$app->getElements()->getElementById($elementId, null, $siteId);

        if (!$element instanceof ElementInterface || !Craft::$app->getElements()->canView($element)) {
            throw new ForbiddenHttpException(Craft::t('icon-picker', 'You are not permitted to browse icons for this element.'));
        }

        if (!$this->_elementLayoutContainsField($element, $field)) {
            throw new ForbiddenHttpException(Craft::t('icon-picker', 'This field is not part of the element being edited.'));
        }

        return $field;
    }


    // Private Methods
    // =========================================================================

    private function _getIconSetData(bool $includeIcons = true): ?Response
    {
        $field = $this->getField();

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

    private function _elementLayoutContainsField(ElementInterface $element, FieldInterface $field): bool
    {
        $layout = $element->getFieldLayout();

        if (!$layout) {
            return false;
        }

        foreach ($layout->getCustomFields() as $layoutField) {
            if ((int)$layoutField->id === (int)$field->id) {
                return true;
            }
        }

        return false;
    }

}
