<?php
namespace verbb\iconpicker\services;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\helpers\IconPickerHelper;
use verbb\iconpicker\models\IconModel;

use Craft;
use craft\base\Component;
use craft\helpers\FileHelper;
use craft\helpers\Image;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use craft\helpers\UrlHelper;

use yii\base\Event;
use Cake\Utility\Xml as XmlParser;
use Cake\Utility\Hash;
use FontLib\Font;

class Service extends Component
{
    // Properties
    // =========================================================================

    private $_loadedFonts = [];
    private $_loadedSpriteSheets = [];


    // Public Methods
    // =========================================================================

    public function getIcons($iconSets, $remoteSets)
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->iconSetsPath;
        $iconSetsUrl = $settings->iconSetsUrl;

        $icons = [];

        // Make sure to always check the directory first, otherwise will throw errors
        if (is_dir($iconSetsPath) && $iconSets) {
            foreach ($iconSets as $iconSetKey => $iconSetName) {
                $iconSetName = $this->_prettyIconSetName($iconSetName);

                $cachedIconData = IconPicker::$plugin->getCache()->getFilesFromCache($iconSetKey);

                if ($cachedIconData) {
                    // Grab any additional resources like spritesheets, fonts, etc
                    $loadedFonts = $cachedIconData['loadedFonts'] ?? [];
                    $loadedSpriteSheets = $cachedIconData['loadedSpriteSheets'] ?? [];

                    $this->_loadedFonts = array_merge($this->_loadedFonts, $loadedFonts);
                    $this->_loadedSpriteSheets = array_merge($this->_loadedSpriteSheets, $loadedSpriteSheets);

                    // Return the actual icon info
                    $icons[$iconSetName] = $cachedIconData['icons'] ?? [];
                }
            }
        }

        if ($remoteSets) {
            foreach ($remoteSets as $remoteSetKey => $remoteSet) {
                if (is_array($remoteSet['icons'])) {
                    foreach ($remoteSet['icons'] as $i => $icon) {
                        $name = pathinfo($remoteSet['url'], PATHINFO_FILENAME);

                        // Return with `getSerializedValues` for a minimal IconModel
                        $icons[$remoteSet['label']][] = (new IconModel([
                            'type' => 'css',
                            'iconSet' => $remoteSetKey,
                            'css' => $icon,
                        ]))->getSerializedValues();
                    }
                }

                $this->_loadedFonts[] = [
                    'type' => 'remote',
                    'name' => $remoteSet['fontName'],
                    'url' => $remoteSet['url'],
                ];
            }
        }

        return $icons;
    }

    public function fetchIconsForFolder($folderName, $recursive = true)
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->iconSetsPath;

        // We only store a reference to the outer folder name without the path. Add it here
        $folderPath = FileHelper::normalizePath($iconSetsPath . DIRECTORY_SEPARATOR . $folderName);

        $data = [];

        $files = $this->_getFiles($folderPath, [
            'only' => ['*.svg'],
            'except' => ['*-sprites.svg'],
            'recursive' => $recursive,
        ]);

        foreach ($files as $key => $file) {
            $url = $this->_getUrlForPath($file);

            $path = $this->_getRelativePathForFile($file);
            $iconSet = ($path) ? FileHelper::normalizePath($path) : 'root';

            $item = str_replace($iconSetsPath, '', $file);

            // Return with `getSerializedValues` for a minimal IconModel
            $data['icons'][] = (new IconModel([
                'type' => 'svg',
                'icon' => $item,
            ]))->getSerializedValues();
        }

        return $data;
    }

    public function fetchIconsForSprite($spriteFile)
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->iconSetsPath;

        $data = [];

        $spriteSheets = $this->_getFiles($iconSetsPath, [
            'only' => [$spriteFile],
            'recursive' => false,
        ]);

        foreach ($spriteSheets as $spriteSheet) {
            $files = $this->_fetchSvgsFromSprites($spriteSheet);

            $iconSet = pathinfo($spriteFile, PATHINFO_FILENAME);

            foreach ($files as $i => $file) {
                // Return with `getSerializedValues` for a minimal IconModel
                $data['icons'][] = (new IconModel([
                    'type' => 'sprite',
                    'iconSet' => $iconSet,
                    'sprite' => $file['@id'],
                ]))->getSerializedValues();
            }

            $data['loadedSpriteSheets'][$spriteSheet] = $files;
        }

        return $data;
    }

    public function fetchIconsForFont($fontFile)
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->iconSetsPath;

        $data = [];

        $fonts = $this->_getFiles($iconSetsPath, [
            'only' => [$fontFile],
            'recursive' => false,
        ]);

        foreach ($fonts as $key => $file) {
            $glyphs = $this->_fetchFontGlyphs($file);

            $iconSet = pathinfo($fontFile, PATHINFO_FILENAME);

            foreach ($glyphs as $i => $glyph) {
                // Return with `getSerializedValues` for a minimal IconModel
                $data['icons'][] = (new IconModel([
                    'type' => 'glyph',
                    'iconSet' => $iconSet,
                    'glyphId' => $glyph['glyphId'],
                    'glyphName' => $glyph['name'],
                ]))->getSerializedValues();
            }

            $data['loadedFonts'][] = [
                'type' => 'local',
                'name' => 'font-face-' . $iconSet,
                'url' => $this->_getUrlForPath($file),
            ];
        }

        return $data;
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

    public function getSpriteSheets()
    {
        $spriteSheets = [];

        foreach ($this->_loadedSpriteSheets as $spriteSheet => $sprites) {
            $spriteSheets[] = [
                'url' => $this->_getUrlForPath($spriteSheet),
                'name' => pathinfo($spriteSheet, PATHINFO_FILENAME),
                'sprites' => $sprites,
            ];
        }

        return $spriteSheets;
    }

    public function getLoadedFonts()
    {
        return $this->_loadedFonts;
    }

    public function getIconSets()
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->iconSetsPath;

        $iconSets = [];

        if (is_dir($iconSetsPath)) {
            // Always return the root
            $iconSets['[root]'] = '[root]';

            $folders = FileHelper::findDirectories($iconSetsPath, [
                'recursive' => false,
            ]);
            
            foreach ($folders as $folder) {
                $path = str_replace($iconSetsPath, '', $folder);
                $iconSets['folder:' . $path] = $path;
            }

            // Get all sprites
            $spriteSheets = FileHelper::findFiles($iconSetsPath, [
                'only' => ['*-sprites.svg'],
                'recursive' => false,
            ]);

            foreach ($spriteSheets as $spriteSheet) {
                $name = pathinfo($spriteSheet, PATHINFO_FILENAME);
                $name = str_replace('-sprites', '', $name);
                $filename = basename($spriteSheet);

                $iconSets['sprite:' . $filename] = $name;
            }

            // // Get all icon fonts
            $fonts = FileHelper::findFiles($iconSetsPath, [
                'only' => ['*.ttf', '*.woff', '*.otf', '*.woff2'],
                'recursive' => false,
            ]);

            foreach ($fonts as $font) {
                $name = pathinfo($font, PATHINFO_FILENAME);
                $filename = basename($font);

                $iconSets['font:' . $filename] = $name;
            }
        }

        // Sort alphabetically
        uasort($iconSets, function($a, $b) {
            return strcmp(basename($a), basename($b));
        });

        return $iconSets;
    }

    public function getEnabledIconSets($field)
    {
        $allIconSets = IconPicker::$plugin->getService()->getIconSets();

        if ($field->iconSets === '') {
            return [];
        }
        
        // For each enabled icon set, generate a cache
        if ($field->iconSets === '*') {
            return $allIconSets;
        }

        // Always include '[root]'
        $iconSets = ['[root]' => '[root]'];

        foreach ($allIconSets as $allIconSetKey => $allIconSetName) {
            if (in_array($allIconSetKey, $field->iconSets)) {
                $iconSets[$allIconSetKey] = $allIconSetName;
            }
        }

        return $iconSets;
    }

    public function getEnabledRemoteSets($field)
    {
        $allRemoteSets = IconPicker::$plugin->getIconSources()->getRegisteredIconSources();

        if ($field->remoteSets === '') {
            return [];
        }
        
        // For each enabled icon set, generate a cache
        if ($field->remoteSets === '*') {
            return $allRemoteSets;
        }

        $remoteSets = [];

        foreach ($allRemoteSets as $allRemoteSetKey => $allRemoteSetName) {
            if (in_array($allRemoteSetKey, $field->remoteSets)) {
                $remoteSets[$allRemoteSetKey] = $allRemoteSetName;
            }
        }

        return $remoteSets;
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

    private function _fetchSvgsFromSprites($file)
    {
        $items = [];

        try {
            $data = @file_get_contents($file);
            $error = error_get_last();

            // Allow parsing errors to be caught
            libxml_use_internal_errors(true);

            $xml = XmlParser::toArray(XmlParser::build($data));

            $items = Hash::get($xml, 'svg.defs.symbol');

            if (!$items) {
                $items = Hash::get($xml, 'svg.symbol');
            }

            if (!$items) {
                $items = Hash::get($xml, 'svg.defs');
            }

        } catch (\Throwable $e) {
            // Get a more useful error from parsing - if available
            $parseErrors = libxml_get_errors();
            IconPicker::error('Error processing SVG spritesheet ' . $file . ': ' . $parseErrors . ': ' . $e->getMessage());
        }

        return $items;
    }

    private function _fetchFontGlyphs($file)
    {
        $items = [];
        $exclusions = [];

        try {
            $font = Font::load($file);
            $font->parse();

            if ($font) {
                $glyphs = $font->getUnicodeCharMap();
                $names = $font->getData('post', 'names');

                // Support specific icon kits where they don't contain names
                if (!$names) {
                    if ($font->getFontName() == 'Material Icons') {
                        // Fetch the glyphId-keyed map
                        $names = IconPicker::$plugin->getIconSources()->getJsonData('material.json');

                        // There's also a bunch of things we want to exclude
                        $exclusions = [3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39];
                    }
                }

                foreach ($glyphs as $id => $gid) {
                    if (in_array($gid, $exclusions)) {
                        continue;
                    }

                    $items[] = [
                        'name' => isset($names[$gid]) ? $names[$gid] : sprintf("uni%04x", $id),
                        'glyphIndex' => $gid,
                        'glyphId' => $id,
                    ];
                }
            }
        } catch (\Throwable $e) {
            IconPicker::error('Error processing icon font ' . $file . ': ' . $e->getMessage());
        }

        return $items;
    }

    private function _getUrlForPath($file)
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->iconSetsPath;

        // This is the path, relative to the config variable, including the filename
        $relativeFilePath = str_replace($iconSetsPath, '', $file);

        // Get the resulting URL
        $url = IconPickerHelper::getIconUrl($relativeFilePath);

        return $url;
    }

    private function _getRelativePathForFile($file)
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->iconSetsPath;

        $filename = basename($file);

        return str_replace([$filename, $iconSetsPath], ['', ''], $file);
    }

    private function _getFiles($path, $options)
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->iconSetsPath;

        if (!is_dir($iconSetsPath)) {
            return [];
        }

        $files = FileHelper::findFiles($path, $options);

        // Sort alphabetically
        uasort($files, function($a, $b) {
            return strcmp(basename($a), basename($b));
        });

        return $files;
    }

    private function _prettyIconSetName($string)
    {
        $string = str_replace('-', ' ', $string);
        $string = str_replace('/', ' - ', $string);
        $string = str_replace('[root]', '', $string);

        return $string;
    }
}