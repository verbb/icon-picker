<?php
namespace verbb\iconpicker\helpers;

use verbb\iconpicker\IconPicker;

use Craft;
use craft\helpers\FileHelper;
use craft\helpers\Html;
use craft\helpers\UrlHelper;

use URL\Normalizer;
use Throwable;

class IconPickerHelper
{
    // Static Methods
    // =========================================================================

    public static function getFontClass(string $name): string
    {
        // Filename punctuation and whitespace must not become CSS syntax or class separators.
        return 'font-face-' . bin2hex($name);
    }

    public static function namespaceSpriteSheet(string $svg, string $namespace): string
    {
        // Craft's reference rewriter expects unquoted local url() references.
        $svg = preg_replace('/url\(\s*([\'"])#([^\'"\s)]+)\1\s*\)/', 'url(#$2)', $svg);

        // Include selectors with pseudo-classes, which Craft's HTML rewriter leaves unchanged.
        preg_match_all('/\sid=([\'"])([^\'"\s]+)\1/', $svg, $matches);
        $ids = array_fill_keys($matches[2], true);
        $svg = preg_replace_callback('/(<style\b[^>]*>)(.*?)(<\/style>)/is', function($style) use ($namespace, $ids) {
            $css = preg_replace_callback('/([^{}]+)\{/', function($rule) use ($namespace, $ids) {
                return preg_replace_callback('/#([\w-]+)/', fn($id) => isset($ids[$id[1]]) ? '#' . $namespace . '-' . $id[1] : $id[0], $rule[1]) . '{';
            }, $style[2]);

            return $style[1] . $css . $style[3];
        }, $svg);

        return Html::namespaceAttributes($svg, $namespace);
    }

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
