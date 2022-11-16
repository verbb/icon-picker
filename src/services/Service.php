<?php
namespace verbb\iconpicker\services;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\helpers\IconPickerHelper;
use verbb\iconpicker\queue\jobs\GenerateIconSetCache;
use verbb\iconpicker\models\Icon;

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
        $iconSets = IconPicker::$plugin->getIconSets()->getIconSetsForField($field);

        return $this->getIcons($iconSets);
    }

    public function getIcons(array $iconSets): array
    {
        // Make sure to always check the directory first, otherwise will throw errors
        foreach ($iconSets as $iconSet) {
            // Populate the icons for the icon set (either from the cache or 'live')
            $iconSet->populateIcons();

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

    public function clearAndRegenerateCache(array $iconSets = []): void
    {
        $settings = IconPicker::$plugin->getSettings();

        // Clear and regenerate caches
        if (!$iconSets) {
            $iconSets = IconPicker::$plugin->getIconSets()->getAllIconSets();
        }

        if (!$iconSets) {
            return;
        }

        foreach ($iconSets as $iconSet) {
            if ($settings->enableCache) {
                Craft::$app->getQueue()->push(new GenerateIconSetCache([
                    'iconSetUid' => $iconSet->uid,
                    'iconSetHandle' => $iconSet->handle,
                ]));
            } else {
                $iconSet->populateIcons(false);
            }
        }
    }

    public function checkToInvalidateCache(): void
    {
        $iconSetsPath = IconPicker::$plugin->getSettings()->getIconSetsPath();

        // Prevent failure when installing
        if (!is_dir($iconSetsPath)) {
            return;
        }

        // A pretty basic check on whether the root folder has been modified
        $modifiedTime = filemtime($iconSetsPath);

        $cacheKey = 'icon-picker:modified-time';

        // Has this been cached already?
        // If it has, check to see if the cache time is different to now
        if (($cache = Craft::$app->getCache()->get($cacheKey)) && $cache != $modifiedTime) {
            $this->clearAndRegenerateCache();
        }

        // Update the cache
        Craft::$app->getCache()->set($cacheKey, $modifiedTime);
    }
}