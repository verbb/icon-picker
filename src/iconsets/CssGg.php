<?php
namespace verbb\iconpicker\iconsets;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\base\IconSet;
use verbb\iconpicker\models\Icon;

use Craft;
use craft\helpers\Json;

class CssGg extends IconSet
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('icon-picker', 'css.gg');
    }


    // Public Methods
    // =========================================================================

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('icon-picker/icon-sets/css-gg', [
            'iconSet' => $this,
        ]);
    }

    public function fetchIcons(): void
    {
        $iconPath = __DIR__ . '/../json/css-gg.json';

        if (file_exists($iconPath)) {
            $json = Json::decode(file_get_contents($iconPath));

            foreach ($json as $icon) {
                $this->icons[] = new Icon([
                    'type' => Icon::TYPE_CSS,
                    'value' => 'gg-' . $icon['label'],
                    'label' => $icon['label'],
                ]);
            }
        }

        $this->fonts[] = [
            'type' => 'remote',
            'name' => 'css.gg',
            'url' => 'https://cdn.jsdelivr.net/npm/css.gg/icons/all.css',
        ];
    }
}
