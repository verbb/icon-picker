<?php
namespace verbb\iconpicker\helpers;

use verbb\iconpicker\IconPicker;

use Craft;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;

use URL\Normalizer;
use Throwable;

class IconPickerHelper
{
    // Public Methods
    // =========================================================================

    public static function getFiles($path, $options): array
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->getIconSetsPath();

        if (!is_dir($iconSetsPath) || !is_dir($path)) {
            return [];
        }

        $files = FileHelper::findFiles($path, $options);

        // Sort alphabetically
        uasort($files, function($a, $b) {
            return strcmp(basename($a), basename($b));
        });

        return $files;
    }

    public static function getIconUrl($path): string
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsUrl = $settings->getIconSetsUrl();

        // Deal with Windows paths
        $path = str_replace('\\', '/', $path);

        // This is for a URL - a string `/` is okay
        $normalizer = new Normalizer($iconSetsUrl . '/' . $path);
        $url = $normalizer->normalize();

        return UrlHelper::siteUrl($url);
    }

    public static function getFileContents($url): string
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
        } catch (Throwable $e) {
            IconPicker::error('Error getting file content for ' . $url . ': ' . $e->getMessage());
        }

        return '';
    }

    public static function getUrlForPath(string $file): string
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->getIconSetsPath();

        // This is the path, relative to the config variable, including the filename
        $relativeFilePath = str_replace($iconSetsPath, '', $file);

        // Get the resulting URL
        return self::getIconUrl($relativeFilePath);
    }
}