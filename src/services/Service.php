<?php
namespace verbb\iconpicker\services;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\helpers\IconPickerHelper;
use verbb\iconpicker\models\Icon;
use verbb\iconpicker\models\IconSet;

use Craft;
use craft\base\Component;
use craft\base\Field;
use craft\helpers\FileHelper;
use craft\helpers\Image;
use craft\helpers\Template;

use Twig\Markup;

class Service extends Component
{
    // Properties
    // =========================================================================

    private array $_loadedFonts = [];
    private array $_loadedSpriteSheets = [];


    // Public Methods
    // =========================================================================

    public function getIconsForField(Field $field)
    {
        $enabledIconSets = IconPicker::$plugin->getIconSets()->getEnabledIconSets($field);
        $enabledRemoteSets = IconPicker::$plugin->getIconSets()->getEnabledRemoteSets($field);
        
        return $this->getIcons($enabledIconSets, $enabledRemoteSets);
    }

    public function getIcons($iconSets, $remoteSets): array
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->getIconSetsPath();
        $iconSetsUrl = $settings->getIconSetsUrl();

        // Make sure to always check the directory first, otherwise will throw errors
        foreach ($iconSets as $iconSet) {
            // Populate the icons for the icon set (either from the cache or 'live')
            IconPicker::$plugin->getCache()->populateIcons($iconSet);

            // Grab any additional resources like spritesheets, fonts, etc. Cached at the request level.
            $this->_loadedFonts = array_merge($this->_loadedFonts, $iconSet->fonts);
            $this->_loadedSpriteSheets = array_merge($this->_loadedSpriteSheets, $iconSet->spriteSheets);
        }

        if ($remoteSets) {
            foreach ($remoteSets as $remoteSetKey => $remoteSet) {
                if (is_array($remoteSet['icons'])) {
                    $remoteSetIcons = [];

                    foreach ($remoteSet['icons'] as $i => $icon) {
                        $remoteSetIcons[] = new Icon([
                            'type' => Icon::TYPE_CSS,
                            'iconSet' => $remoteSetKey,
                            'value' => $icon,
                        ]);
                    }

                    $icons[] = new IconSet([
                        'name' => $remoteSet['label'],
                        'icons' => $remoteSetIcons,
                    ]);
                }

                $this->_loadedFonts[] = [
                    'type' => 'remote',
                    'name' => $remoteSet['fontName'],
                    'url' => $remoteSet['url'],
                ];
            }
        }

        return $iconSets;
    }

    public function getSpriteSheets(): array
    {
        $spriteSheets = [];

        foreach ($this->_loadedSpriteSheets as $spriteSheet => $sprites) {
            $spriteSheets[] = [
                'url' => IconPickerHelper::getUrlForPath($spriteSheet),
                'name' => pathinfo($spriteSheet, PATHINFO_FILENAME),
                'sprites' => $sprites,
            ];
        }

        return $spriteSheets;
    }

    public function getLoadedFonts(): array
    {
        return $this->_loadedFonts;
    }
}