<?php
namespace verbb\iconpicker\services;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\queue\jobs\GenerateIconSetCache;
use verbb\iconpicker\models\Icon;
use verbb\iconpicker\models\IconSet;

use Craft;
use craft\base\Component;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;

class Cache extends Component
{
    // Public Methods
    // =========================================================================

    public function clearAndRegenerate(array $iconSets = []): void
    {
        $settings = IconPicker::$plugin->getSettings();

        // Clear and regenerate caches
        if (!$iconSets) {
            $iconSets = IconPicker::$plugin->getIconSets()->getIconSets();
        }

        if (!$iconSets) {
            return;
        }

        foreach ($iconSets as $iconSet) {
            if ($settings->enableCache) {
                Craft::$app->getQueue()->push(new GenerateIconSetCache([
                    'iconSetKey' => $iconSet->key,
                ]));
            } else {
                $this->generateIconSetCache($iconSet);
            }
        }
    }

    public function populateIcons(IconSet $iconSet): void
    {
        $settings = IconPicker::$plugin->getSettings();

        $cacheKey = 'icon-picker:' . $iconSet->key;

        // Check if the cache is enabled, and return that cached icon set
        if ($settings->enableCache) {
            if ($cache = Craft::$app->getCache()->get($cacheKey)) {
                $cachedSet = $this->unserializeFromCache($cache);

                if ($cachedSet) {
                    $iconSet->setAttributes($cachedSet->getAttributes(), false);

                    return;
                }
            }
        }

        // Populate the icons without the cache
        $this->generateIconSetCache($iconSet);
    }

    public function generateIconSetCache(IconSet $iconSet): void
    {
        $settings = IconPicker::$plugin->getSettings();

        $cacheKey = 'icon-picker:' . $iconSet->key;

        // Fetch the icon set's icons. Don't call this method lightly, it'll be slow
        $iconSet->fetchIcons();

        // Save the icon set to the cache, if using
        if ($settings->enableCache) {
            Craft::$app->getCache()->set($cacheKey, $this->serializeToCache($iconSet));
        }
    }

    public function checkToInvalidate(): void
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
            $this->clearAndRegenerate();
        }

        // Update the cache
        Craft::$app->getCache()->set($cacheKey, $modifiedTime);
    }


    // Private Methods
    // =========================================================================

    private function serializeToCache(IconSet $iconSet): string
    {
        $icons = [];
        $data = $iconSet->toArray();

        foreach ($iconSet->icons as $key => $icon) {
            $data['icons'][$key] = $icon->serializeValueForCache();
        }

        return Json::encode($data);
    }

    private function unserializeFromCache(string $data): IconSet
    {
        $data = Json::decode($data);
        $icons = $data['icons'] ?? [];

        foreach ($icons as $key => $icon) {
            $data['icons'][$key] = new Icon($icon);
        }

        return new IconSet($data);
    }
}