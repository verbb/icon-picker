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
    }

    public function getFilesFromCache($iconSetKey)
    {
        if (!$iconSetKey) {
            return [];
        }

        $cacheKey = 'icon-picker:' . $iconSetKey;

        if ($cache = Craft::$app->getCache()->get($cacheKey)) {
            return Json::decode($cache);
        }

        return [];
    }
}