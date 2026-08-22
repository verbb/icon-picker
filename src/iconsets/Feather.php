<?php
namespace verbb\iconpicker\iconsets;

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
        return Craft::t('icon-picker', 'Feather Icons (Legacy)');
    }


    // Properties
    // =========================================================================

    /**
     * Legacy attribute name kept for any CP code that still branches on it.
     * Icons are painted as inline SVG from `displayValue` (no remote feather.js).
     */
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
        $catalogPath = __DIR__ . '/../json/feather.json';
        $pathsPath = __DIR__ . '/../json/feather-icons.json';

        if (!file_exists($catalogPath) || !file_exists($pathsPath)) {
            return;
        }

        $catalog = Json::decode(file_get_contents($catalogPath));
        // name → inner SVG markup (paths/polylines) from the Feather icons package.
        $paths = Json::decode(file_get_contents($pathsPath));

        foreach ($catalog as $icon) {
            $name = $icon['label'] ?? null;

            if (!$name || !isset($paths[$name])) {
                continue;
            }

            // Inline SVG once at cache build — CP paints via displayValue, no
            // feather.replace() / remote script (those broke on reopen + virtualizer).
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                . $paths[$name]
                . '</svg>';

            $model = new Icon([
                'type' => Icon::TYPE_CSS,
                'iconSetHandle' => $this->handle,
                'value' => $name,
                'label' => $name,
                'keywords' => $icon['keywords'] ?? $name,
            ]);
            $model->setDisplayValue($svg);

            $this->icons[] = $model;
        }
    }
}
