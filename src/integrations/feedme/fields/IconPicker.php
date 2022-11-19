<?php
namespace verbb\iconpicker\integrations\feedme\fields;

use verbb\iconpicker\IconPicker as IconPickerPlugin;
use verbb\iconpicker\fields\IconPickerField;
use verbb\iconpicker\models\Icon;

use Craft;
use craft\helpers\Json;

use craft\feedme\base\Field;
use craft\feedme\base\FieldInterface;

class IconPicker extends Field implements FieldInterface
{
    // Properties
    // =========================================================================

    public static $name = 'IconPicker';
    public static $class = IconPickerField::class;


    // Templates
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getMappingTemplate(): string
    {
        return 'feed-me/_includes/fields/default';
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function parseField(): string
    {
        $value = $this->fetchValue();

        $iconModel = '';

        // Find the provided value in the first icon set for the field
        foreach ($this->field->iconSets as $iconSetUid) {
            $iconSet = IconPickerPlugin::$plugin->getIconSets()->getIconSetByUid($iconSetUid);

            if ($iconSet) {
                $iconSet->populateIcons();

                foreach ($iconSet->icons as $icon) {
                    if ($icon->value === $value) {
                        $iconModel = $icon;

                        break 2;
                    }
                }
            }
        }

        return Json::encode($iconModel->serializeValueForDb());
    }
}
