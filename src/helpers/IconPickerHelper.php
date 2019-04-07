<?php
namespace verbb\iconpicker\helpers;

use verbb\iconpicker\IconPicker;

use Craft;
use craft\helpers\FileHelper;
use craft\helpers\Json;

use URL\Normalizer;

class IconPickerHelper
{
    // Public Methods
    // =========================================================================

    public static function getIconUrl($path)
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsUrl = $settings->iconSetsUrl;

        $normalizer = new Normalizer($iconSetsUrl . DIRECTORY_SEPARATOR . $path);
        $url = $normalizer->normalize();
        $url = Craft::getAlias($url);

        return $url;
    }

    public static function getFileContents($url)
    {
        $options = [];

        // Disable any SSL errors locally (or, when devMode is on)
        if (Craft::$app->getConfig()->getGeneral()->devMode) {
            $options['verify'] = false;
        }

        $client = Craft::createGuzzleClient($options);

        $response = $client->get($url);

        return Json::decode($response->getBody()->getContents());
    }
}