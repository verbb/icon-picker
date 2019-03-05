<?php
namespace verbb\iconpicker\services;

use verbb\iconpicker\IconPicker;
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
            // Fetch the SVG files
            $files = $this->_getFiles($iconSets, [
                'only' => ['*.svg'],
                'except' => ['*-sprites.svg'],
                'recursive' => true,
            ]);

            foreach ($files as $key => $file) {
                $url = $this->_getUrlForPath($file);

                $iconSet = $this->getIconsSetPath('svg', $file);
                $iconSetName = $this->_prettyIconSetName($iconSet);

                $item = str_replace($iconSetsPath, '', $file);

                $icons[$iconSetName][] = [
                    'type' => 'svg',
                    'value' => $item,
                    'url' => $url,
                    'label' => pathinfo($file, PATHINFO_FILENAME),
                ];
            }

            // Fetch any spritesheets
            $spriteSheets = $this->_getFiles($iconSets, [
                'only' => ['*-sprites.svg'],
                'recursive' => true,
            ]);

            foreach ($spriteSheets as $spriteSheet) {
                $files = $this->_fetchSvgsFromSprites($spriteSheet);

                $iconSet = $this->getIconsSetPath('spritesheet', $spriteSheet);
                $iconSetName = $this->_prettyIconSetName($iconSet);

                foreach ($files as $i => $file) {
                    $icons[$iconSetName][] = [
                        'type' => 'sprite',
                        'name' =>  pathinfo($spriteSheet, PATHINFO_FILENAME),
                        'value' => 'sprite:' . $iconSet . ':' . $file['@id'],
                        'url' => $file['@id'],
                        'label' => $file['@id'],
                    ];
                }

                $this->_loadedSpriteSheets[$spriteSheet] = $files;
            }

            // Fetch any icon fonts
            $fonts = $this->_getFiles($iconSets, [
                'only' => ['*.ttf', '*.woff', '*.otf', '*.woff2'],
                'recursive' => true,
            ]);

            foreach ($fonts as $key => $file) {
                $glyphs = $this->_fetchFontGlyphs($file);

                $iconSet = $this->getIconsSetPath('font', $file);
                $iconSetName = $this->_prettyIconSetName($iconSet);

                foreach ($glyphs as $i => $glyph) {
                    $name = pathinfo($file, PATHINFO_FILENAME);

                    $icons[$iconSetName][] = [
                        'type' => 'glyph',
                        'name' =>  $name,
                        'value' => 'glyph:' . $iconSet . ':' . $glyph['glyphId'] . ':' . $glyph['name'],
                        'url' => '&#x' . dechex($glyph['glyphId']),
                        'label' => $glyph['name'],
                    ];
                }

                $this->_loadedFonts[] = [
                    'type' => 'local',
                    'name' => 'font-face-' . $name,
                    'url' => $this->_getUrlForPath($file),
                ];
            }
        }

        if ($remoteSets) {
            foreach ($remoteSets as $remoteSetHandle) {
                $remoteSet = IconPicker::$plugin->getIconSources()->getRegisteredIconSourceByHandle($remoteSetHandle);

                if ($remoteSet) {
                    foreach ($remoteSet['icons'] as $i => $icon) {
                        $name = pathinfo($remoteSet['url'], PATHINFO_FILENAME);

                        $icons[$remoteSet['label']][] = [
                            'type' => 'css',
                            'name' =>  $remoteSet['fontName'],
                            'value' => 'css:' . $remoteSetHandle . ':' . $icon,
                            'classes' => $remoteSet['classes'] . $icon,
                            'url' => '',
                            'label' => $icon,
                        ];
                    }

                    $this->_loadedFonts[] = [
                        'type' => 'remote',
                        'name' => $remoteSet['fontName'],
                        'url' => $remoteSet['url'],
                    ];
                }
            }
        }

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
            $folders = FileHelper::findDirectories($iconSetsPath, [
                'recursive' => false,
            ]);
            
            foreach ($folders as $folder) {
                $path = $this->getIconsSetPath('folder', $folder);
                
                $iconSets[$path] = $path;
            }

            // Get all sprites
            $spriteSheets = FileHelper::findFiles($iconSetsPath, [
                'only' => ['*-sprites.svg'],
                'recursive' => true,
            ]);

            foreach ($spriteSheets as $spriteSheet) {
                $path = $this->getIconsSetPath('spritesheet', $spriteSheet);
                
                $iconSets[$path] = $path;
            }

            // Get all icon fonts
            $fonts = FileHelper::findFiles($iconSetsPath, [
                'only' => ['*.ttf', '*.woff', '*.otf', '*.woff2'],
                'recursive' => true,
            ]);

            foreach ($fonts as $font) {
                $path = $this->getIconsSetPath('font', $font);

                $iconSets[$path] = $path;
            }
        }

        // Sort alphabetically
        uasort($iconSets, function($a, $b) {
            return strcmp(basename($a), basename($b));
        });

        return $iconSets;
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

            $items = Hash::get($xml, 'svg.symbol');

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

        try {
            $font = Font::load($file);
            $font->parse();

            if ($font) {
                $glyphs = $font->getUnicodeCharMap();
                $names = $font->getData('post', 'names');

                foreach ($glyphs as $id => $gid) {
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
        $iconSetsUrl = $settings->iconSetsUrl;

        // This is the path, relative to the config variable, including the filename
        $relativeFilePath = str_replace($iconSetsPath, '', $file);

        // Get the resulting URL
        $url = FileHelper::normalizePath($iconSetsUrl . DIRECTORY_SEPARATOR . $relativeFilePath);
        $url = Craft::getAlias($url);

        return $url;
    }

    private function _fileSearchPaths($searches, $paths)
    {
        $array = [];

        if ($paths === '*') {
            return $searches;
        }

        if (!is_array($paths)) {
            return null;
        }

        foreach ($paths as $path) {
            foreach ($searches as $search) {
                $array[] = FileHelper::normalizePath($path . DIRECTORY_SEPARATOR . $search);
            }
        }

        return $array;
    }

    private function _getRelativePathForFile($file)
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->iconSetsPath;

        $filename = basename($file);

        return str_replace([$filename, $iconSetsPath], ['', ''], $file);
    }

    private function getIconsSetPath($type, $file)
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->iconSetsPath;

        $name = pathinfo($file, PATHINFO_FILENAME);
        $path = $this->_getRelativePathForFile($file);

        $name = str_replace('-sprites', '', $name);

        if ($type === 'folder') {
            return str_replace($iconSetsPath, '', $file);
        } else if ($type === 'svg') {
            return ($path) ? FileHelper::normalizePath($path) : 'root';
        } else {
            return FileHelper::normalizePath($path . DIRECTORY_SEPARATOR . $name);
        }
    }

    private function _getFiles($iconSets, $options)
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->iconSetsPath;

        $files = [];

        // From the provided 'only' options, prefix paths with the icon set path, to ensure we only
        // fetch icons from icons sets that we've allowed. 
        // Will turn something like `['*.svg']` into `['my/path/*.svg']`.
        $options['only'] = $this->_fileSearchPaths($options['only'], $iconSets);

        if ($options['only']) {
            $files = FileHelper::findFiles($iconSetsPath, $options);

            // Sort alphabetically
            uasort($files, function($a, $b) {
                return strcmp(basename($a), basename($b));
            });
        }

        return $files;
    }

    private function _prettyIconSetName($string)
    {
        $string = str_replace('-', ' ', $string);
        $string = str_replace('/', ' - ', $string);

        return $string;
    }
}