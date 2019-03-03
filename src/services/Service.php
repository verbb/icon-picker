<?php
namespace verbb\iconpicker\services;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\models\IconModel;

use Craft;
use craft\base\Component;
use craft\helpers\FileHelper;
use craft\helpers\Image;
use craft\helpers\Template;

use yii\base\Event;

class Service extends Component
{
    // Public Methods
    // =========================================================================

    public function getIcons($iconSets)
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->iconSetsPath;
        $iconSetsUrl = $settings->iconSetsUrl;

        $icons = [];

        // Make sure to always check the directory first, otherwise will throw errors
        if (is_dir($iconSetsPath)) {
            // Fetch the SVG files
            $files = FileHelper::findFiles($iconSetsPath, [
                'only' => ['*.svg'],
                'recursive' => true,
            ]);

            foreach ($files as $key => $file) {
                // This is the path, relative to the config variable, including the filename
                $relativeFilePath = str_replace($iconSetsPath, '', $file);

                // Get the resulting URL
                $url = FileHelper::normalizePath($iconSetsUrl . DIRECTORY_SEPARATOR . $relativeFilePath);
                $url = Craft::getAlias($url);

                // Add in the folder name (if present), which helps to group things nicely
                $folder = $this->_getFolderName($relativeFilePath, false);

                if ($folder !== '.') {
                    $icons[$folder] = ['optgroup' => $folder];
                }

                $icons[$relativeFilePath] = [
                    'value' => $relativeFilePath,
                    'url' => $url,
                    'label' => pathinfo($relativeFilePath, PATHINFO_FILENAME),
                ];
            }
        } else {
            $blankUrl = Craft::$app->getAssetManager()->getPublishedUrl('@verbb/iconpicker/resources/dist', false, 'svg/icon-blank.svg');

            $icons[$blankUrl] = [
                'value' => $blankUrl,
                'url' => $blankUrl,
                'label' => pathinfo($blankUrl, PATHINFO_FILENAME),
            ];
        }

        // Reset array keys
        $icons = array_values($icons);

        return $icons;
    }

    public function getModel($icon)
    {
        if ($icon instanceof IconModel) {
            return $icon;
        }

        $model = new IconModel();
        $model->icon = $icon;

        list($model->width, $model->height) = $this->getDimensions($model);

        return $model;
    }

    public function getDimensions($icon, $height = null)
    {
        $settings = IconPicker::$plugin->getSettings();
        $model = $this->getModel($icon);

        if ($model) {
            $path = FileHelper::normalizePath($settings->iconSetsPath . DIRECTORY_SEPARATOR . $model->icon);

            if (file_exists($path)) {
                if ($height) {
                    return [
                        'width' => ceil(($model->width / $model->height) * $height),
                        'height' => $height,
                    ];
                } else {
                    return Image::imageSize($path);
                }
            }
        }

        return [0, 0];
    }

    public function inline($icon)
    {
        $settings = IconPicker::$plugin->getSettings();
        
        $path = FileHelper::normalizePath($settings->iconSetsPath . DIRECTORY_SEPARATOR . $icon);

        if (!file_exists($path)) {
            return '';
        }

        return Template::raw(@file_get_contents($path));
    }


    // Private Methods
    // =========================================================================

    private function _getFolderName($path, $fullPath = true, $suppressErrors = true)
    {
        $path = FileHelper::normalizePath($path);

        if ($fullPath) {
            $folder = FileHelper::normalizePath($suppressErrors ? @pathinfo($path, PATHINFO_DIRNAME) : pathinfo($path, PATHINFO_DIRNAME));
            return rtrim($folder, '/').'/';
        } else {
            if ($suppressErrors ? !@is_dir($path) : !is_dir($path)) {
                $path = $suppressErrors ? @pathinfo($path, PATHINFO_DIRNAME) : pathinfo($path, PATHINFO_DIRNAME);
            }

            return $suppressErrors ? @pathinfo($path, PATHINFO_BASENAME) : pathinfo($path, PATHINFO_BASENAME);
        }
    }
}