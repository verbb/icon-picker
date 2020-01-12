<?php
namespace verbb\iconpicker\services;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\fields\IconPickerField;
use verbb\iconpicker\helpers\IconPickerHelper;
use verbb\iconpicker\queue\jobs\GenerateIconSetCache;
use verbb\iconpicker\models\IconModel;

use Craft;
use craft\base\Component;
use craft\helpers\FileHelper;
use craft\helpers\Image;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use craft\helpers\UrlHelper;

use yii\base\Event;
use Cake\Utility\Xml as XmlParser;
use Cake\Utility\Hash;
use FontLib\Font;

class Cache extends Component
{
    // Public Methods
    // =========================================================================

    public function clearAndRegenerate()
    {
        // Clear and regenerate caches
        $iconSets = IconPicker::$plugin->getService()->getIconSets();

        if (!$iconSets) {
            return;
        }

        foreach ($iconSets as $iconSetKey => $iconSetName) {
            Craft::$app->getQueue()->push(new GenerateIconSetCache([
                'iconSetKey' => $iconSetKey,
            ]));

            // Testing
            // IconPicker::$plugin->getCache()->generateIconSetCache($iconSetKey);
        }
    }

    public function generateIconSetCache($iconSetKey)
    {
        $icons = [];
        
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
        } else {
            return $this->generateIconSetCache($iconSetKey);
        }

        return [];
    }

    public function checkToInvalidate()
    {
        $iconSetsPath = IconPicker::$plugin->getSettings()->iconSetsPath;

        // Prevent failure when installing
        if (!is_dir($iconSetsPath)) {
            return;
        }

        // A pretty basic check on whether the root folder has been modified
        $modifiedTime = filemtime($iconSetsPath);

        $cacheKey = 'icon-picker:modified-time';

        // Has this been cached already?
        if ($cache = Craft::$app->getCache()->get($cacheKey)) {
            // If it has, check to see if the cache time is different to now
            if ($cache != $modifiedTime) {
                $this->clearAndRegenerate();
            }
        }

        // Update the cache
        Craft::$app->getCache()->set($cacheKey, $modifiedTime);
    }
}