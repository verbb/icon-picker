<?php
namespace verbb\iconpicker\iconsets;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\base\IconSet;
use verbb\iconpicker\models\Icon;

use Craft;
use craft\helpers\Json;

class Ionicons extends IconSet
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('icon-picker', 'Ionicons');
    }


    // Properties
    // =========================================================================

    public ?array $variants = null;


    // Public Methods
    // =========================================================================

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('icon-picker/icon-sets/ionicons', [
            'iconSet' => $this,
        ]);
    }

    public function fetchIcons(): void
    {
        $icons = [];

        // Because we can pick individual collections of icons, we want to fetch them all first, order them
        // alphabetically, and then create the icons.
        foreach ($this->variants as $variant) {
            $variantName = ($variant === '*') ? 'all' : $variant;
            $iconPath = __DIR__ . "/../json/ionicons-{$variantName}.json";

            if (file_exists($iconPath)) {
                $json = Json::decode(file_get_contents($iconPath));

                foreach ($json as $definition) {
                    $icons[] = $definition;
                }
            }
        }

        // Order icons alphabetically, as we might've added them in order of collection
        usort($icons, fn($a, $b) => strcmp($a['label'], $b['label']));

        foreach ($icons as $icon) {
            $this->icons[] = new Icon([
                'type' => Icon::TYPE_CSS,
                'value' => 'icon-' . $icon['label'],
                'label' => $icon['label'],
                'keywords' => $icon['keywords'],
            ]);
        }

        $this->fonts[] = [
            'type' => 'remote',
            'name' => 'Ionicons',
            'url' => 'https://unpkg.com/ionicons-css@5.2.1/dist/icon.css',
        ];
    }
}
