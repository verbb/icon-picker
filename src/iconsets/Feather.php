<?php
namespace verbb\iconpicker\iconsets;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\base\IconSet;
use verbb\iconpicker\models\Icon;

use Craft;
use craft\helpers\Json;

class Feather extends IconSet
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('icon-picker', 'Feather Icons');
    }


    // Properties
    // =========================================================================

    public string $cssAttribute = 'data-feather';


    // Public Methods
    // =========================================================================

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('icon-picker/icon-sets/feather', [
            'iconSet' => $this,
        ]);
    }

    public function fetchIcons(): void
    {
        $icons = [];

        // Because we can pick individual collections of icons, we want to fetch them all first, order them
        // alphabetically, and then create the icons.
        // foreach ($this->variants as $variant) {
        //     $variantName = ($variant === '*') ? 'all' : $variant;
        $iconPath = __DIR__ . '/../json/feather.json';

        if (file_exists($iconPath)) {
            $json = Json::decode(file_get_contents($iconPath));

            foreach ($json as $icon) {
                $this->icons[] = new Icon([
                    'type' => Icon::TYPE_CSS,
                    'value' => $icon['label'],
                    'label' => $icon['label'],
                    'keywords' => $icon['keywords'],
                ]);
            }
        }

        $this->scripts[] = [
            'type' => 'remote',
            'name' => 'Feather',
            'url' => 'https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js',
            'onload' => 'setTimeout(function() { feather.replace(); }, 500);',
        ];
    }
}
