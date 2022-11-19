<?php
namespace verbb\iconpicker\iconsets;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\base\IconSet;
use verbb\iconpicker\models\Icon;

use Craft;
use craft\helpers\Json;

class MaterialSymbols extends IconSet
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('icon-picker', 'Material Symbols');
    }


    // Public Methods
    // =========================================================================

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('icon-picker/icon-sets/material-symbols', [
            'iconSet' => $this,
        ]);
    }

    public function fetchIcons(): void
    {
        $iconPath = __DIR__ . '/../json/material-symbols.json';

        if (file_exists($iconPath)) {
            $json = Json::decode(file_get_contents($iconPath));

            foreach ($json as $icon) {
                $this->icons[] = new Icon([
                    'type' => Icon::TYPE_GLYPH,
                    'iconSet' => 'material-symbols-outlined',
                    'value' => $icon['label'] . ':' . $icon['glyph'],
                ]);
            }
        }

        $this->fonts[] = [
            'type' => 'remote',
            'name' => 'material-symbols',
            'url' => 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined',
        ];

        $this->fonts[] = [
            'type' => 'proxy',
            'id' => 'font-face-material-symbols-outlined',
            'name' => 'Material Symbols Outlined',
        ];
    }
}
