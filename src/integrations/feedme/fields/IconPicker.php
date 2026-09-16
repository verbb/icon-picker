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

    public function getMappingTemplate(): string
    {
        return 'feed-me/_includes/fields/default';
    }


    // Public Methods
    // =========================================================================

    public function parseField(): string
    {
        $value = $this->fetchValue();

        // Use the same enabled collections as the field, including its All option.
        $iconSets = IconPickerPlugin::$plugin->getIconSets()->getIconSetsForField($this->field);

        foreach ($iconSets as $iconSet) {
            $iconSet->populateIcons();

            foreach ($iconSet->icons as $icon) {
                if ($icon->value === $value) {
                    return Json::encode($icon->serializeValueForDb());
                }
            }
        }

        return '';
    }
}
