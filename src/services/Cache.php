<?php
namespace verbb\iconpicker\services;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\queue\jobs\GenerateIconSetCache;

use Craft;
use craft\base\Component;
use craft\helpers\Json;

class Cache extends Component
{
    // Public Methods
    // =========================================================================

    public function clearAndRegenerate(): void
    {
        $settings = IconPicker::$plugin->getSettings();

        // Clear and regenerate caches
        $iconSets = IconPicker::$plugin->getService()->getIconSets();

        if (!$iconSets) {
            return;
        }

        foreach ($iconSets as $iconSetKey => $iconSetName) {
            if ($settings->enableCache) {
                Craft::$app->getQueue()->push(new GenerateIconSetCache([
                    'iconSetKey' => $iconSetKey,
                ]));
            } else {
                IconPicker::$plugin->getCache()->generateIconSetCache($iconSetKey);
            }
        }
    }

    public function generateIconSetCache($iconSetKey): array
    {
        // Special-case for root folder
        if ($iconSetKey === '[root]') {
            $icons = IconPicker::$plugin->getService()->fetchIconsForFolder('', false);
        } else {
            $iconSetType = explode(':', $iconSetKey);

            $functionName = 'fetchIconsFor' . $iconSetType[0];
            $filePath = $iconSetType[1];

            $icons = IconPicker::$plugin->getService()->$functionName($filePath);
        }

        // Save to the cache
        $cacheKey = 'icon-picker:' . $iconSetKey;

        Craft::$app->getCache()->set($cacheKey, Json::encode($icons));

        return $icons;
    }

    public function getFilesFromCache($iconSetKey)
    {
        if (!$iconSetKey) {
            return [];
        }

        $cacheKey = 'icon-picker:' . $iconSetKey;

        // Fetch from the cache, otherwise, fetch it (which saves to cache for next time)
        if ($cache = Craft::$app->getCache()->get($cacheKey)) {
            return Json::decode($cache);
        }

        return $this->generateIconSetCache($iconSetKey);
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
}