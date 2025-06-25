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
        $metadata = file_get_contents(
            'https://fonts.google.com/metadata/icons/Material%20Symbols%20Outlined?key=material_symbols&incomplete=true'
        );

        $icons = json_decode(
            substr($metadata, strpos($metadata, "\n") + 1),
            true
        )['icons'];

        foreach ($icons as $icon) {
            $this->icons[] = new Icon([
                'type' => Icon::TYPE_GLYPH,
                'iconSetHandle' => $this->handle,
                'iconSet' => 'material-symbols-outlined',
                'value' => $icon['name'] . ':' . $icon['codepoint'],
                'keywords' => implode(' ', $icon['tags']),
            ]);
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
