<?php
namespace verbb\iconpicker\helpers;

use Craft;
use craft\helpers\Json;

class IconPickerHelper
{
    // Public Methods
    // =========================================================================

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