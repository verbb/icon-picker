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
    private array $_loadedScripts = [];


    // Public Methods
    // =========================================================================

    public function getIconsForField(Field $field)
    {
        $enabledIconSets = IconPicker::$plugin->getIconSets()->getEnabledIconSets($field);
        $enabledRemoteSets = IconPicker::$plugin->getIconSets()->getEnabledRemoteSets($field);
        $sets = array_merge($enabledIconSets, $enabledRemoteSets);

        return $this->getIcons($sets);
    }

    public function getIcons(array $iconSets): array
    {
        // Make sure to always check the directory first, otherwise will throw errors
        foreach ($iconSets as $iconSet) {
            // Populate the icons for the icon set (either from the cache or 'live')
            IconPicker::$plugin->getCache()->populateIcons($iconSet);

            // Grab any additional resources like spritesheets, fonts, etc. Cached at the request level.
            $this->_loadedFonts = array_merge($this->_loadedFonts, $iconSet->fonts);
            $this->_loadedSpriteSheets = array_merge($this->_loadedSpriteSheets, $iconSet->spriteSheets);
            $this->_loadedScripts = array_merge($this->_loadedScripts, $iconSet->scripts);
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

    public function getLoadedScripts(): array
    {
        return $this->_loadedScripts;
    }
}