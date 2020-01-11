<?php
namespace verbb\iconpicker\helpers;

use verbb\iconpicker\IconPicker;

use Craft;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;

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

        return UrlHelper::siteUrl($url);
    }

    public static function getFileContents($url)
    {
        try {
            $options = [];

            // Disable any SSL errors locally (or, when devMode is on)
            if (Craft::$app->getConfig()->getGeneral()->devMode) {
                $options['verify'] = false;
            }

            $client = Craft::createGuzzleClient($options);

            $response = $client->get($url);

            return $response->getBody()->getContents();
        } catch (\Throwable $e) {
            IconPicker::error('Error getting file content for ' . $url . ': ' . $e->getMessage());
        }

        return '';
    }
}