<?php
namespace verbb\iconpicker\controllers;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\fields\IconPickerField;
use verbb\iconpicker\helpers\CpInputContext;
use verbb\iconpicker\models\Icon;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\web\Controller;

use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class IconsController extends Controller
{
    // Properties
    // =========================================================================

    private ?ElementInterface $_element = null;


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

        $elementIdParam = $this->request->getParam('elementId');
        $elementId = $elementIdParam !== null && $elementIdParam !== '' ? (int)$elementIdParam : null;
        $elementTypeParam = $this->request->getParam('elementType');
        $elementType = is_string($elementTypeParam) && is_subclass_of($elementTypeParam, ElementInterface::class)
            ? $elementTypeParam
            : null;
        $siteId = (int)($this->request->getParam('siteId') ?: Craft::$app->getSites()->getCurrentSite()->id);
        // Match both ID and type. Transient nested elements can deliberately
        // borrow a persisted surrogate ID, and must use their signed context.
        $element = $elementId ? Craft::$app->getElements()->getElementById($elementId, $elementType, $siteId) : null;

        if ($element instanceof ElementInterface && !Craft::$app->getElements()->canView($element)) {
            throw new ForbiddenHttpException(Craft::t('icon-picker', 'You are not permitted to browse icons for this element.'));
        }

        if ($element instanceof ElementInterface) {
            if (!$this->_elementLayoutContainsField($element, $field)) {
                throw new ForbiddenHttpException(Craft::t('icon-picker', 'This field is not part of the element being edited.'));
            }

            $this->_element = $element;

            return $field;
        }

        CpInputContext::validate(
            (string)$this->request->getParam('context'),
            (int)$field->id,
            $siteId,
            $elementId,
            $elementType,
        );

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

        // Saved choices still need their resources after available sets/styles are narrowed.
        // Read them from the authorized element, including aliased field-layout instances.
        foreach ($this->_element?->getFieldLayout()?->getCustomFields() ?? [] as $layoutField) {
            if ((int)$layoutField->id !== (int)$field->id) {
                continue;
            }

            $value = $this->_element->getFieldValue($layoutField->handle);

            if ($value instanceof Icon && $value->value && ($source = $value->getSourceIconSet())) {
                foreach ($source->getResourcesForIcon($value) as $key => $resources) {
                    $json[$key] = array_merge($json[$key], $resources);
                }
            }
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
