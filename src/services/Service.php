<?php
namespace verbb\iconpicker\services;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\queue\jobs\GenerateIconSetCache;

use Craft;
use craft\base\Component;
use craft\base\Field;

class Service extends Component
{
    // Public Methods
    // =========================================================================

    public function getIconsForField(Field $field)
    {
        $iconSets = IconPicker::$plugin->getIconSets()->getIconSetsForField($field);

        // Populate the icons for the icon set (either from the cache or 'live')
        foreach ($iconSets as $iconSet) {
            $iconSet->populateIcons();
        }

        return $iconSets;
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