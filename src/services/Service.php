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
            if ($iconSets === '*') {
                $iconSets = array_keys(IconPicker::$plugin->getService()->getIconSets());
            }

            $files = $this->_getFiles([
                'only' => ['*.svg'],
                'except' => ['*-sprites.svg'],
                'recursive' => false,
            ]);

            foreach ($files as $key => $file) {
                $url = $this->_getUrlForPath($file);

                $path = $this->_getRelativePathForFile($file);
                $iconSet = ($path) ? FileHelper::normalizePath($path) : 'root';
                $iconSetName = $this->_prettyIconSetName($iconSet);

                $item = str_replace($iconSetsPath, '', $file);

                $icons[$iconSetName][] = [
                    'type' => 'svg',
                    'value' => $item,
                    'url' => $url,
                    'label' => pathinfo($file, PATHINFO_FILENAME),
                ];
            }

            foreach ($iconSets as $iconSet) {
                $iconSetType = explode(':', $iconSet);

                // Fetch the SVG files
                if ($iconSetType[0] === 'folder') {
                    $files = $this->_getFiles([
                        'only' => [$iconSetType[1] . '/*.svg'],
                        'except' => ['*-sprites.svg'],
                        'recursive' => true,
                    ]);

                    foreach ($files as $key => $file) {
                        $url = $this->_getUrlForPath($file);

                        $path = $this->_getRelativePathForFile($file);
                        $iconSet = ($path) ? FileHelper::normalizePath($path) : 'root';
                        $iconSetName = $this->_prettyIconSetName($iconSet);

                        $item = str_replace($iconSetsPath, '', $file);

                        $icons[$iconSetName][] = [
                            'type' => 'svg',
                            'value' => $item,
                            'url' => $url,
                            'label' => pathinfo($file, PATHINFO_FILENAME),
                        ];
                    }
                }

                // Fetch any spritesheets
                if ($iconSetType[0] === 'sprite') {
                    $spriteSheets = $this->_getFiles([
                        'only' => [$iconSetType[1]],
                        'recursive' => false,
                    ]);

                    foreach ($spriteSheets as $spriteSheet) {
                        $files = $this->_fetchSvgsFromSprites($spriteSheet);

                        $iconSet = pathinfo($iconSetType[1], PATHINFO_FILENAME);
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
                }

                if ($iconSetType[0] === 'font') {
                    $fonts = $this->_getFiles([
                        'only' => [$iconSetType[1]],
                        'recursive' => false,
                    ]);

                    foreach ($fonts as $key => $file) {
                        $glyphs = $this->_fetchFontGlyphs($file);

                        $iconSet = pathinfo($iconSetType[1], PATHINFO_FILENAME);
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
            }
        }

        if ($remoteSets) {
            if ($remoteSets === '*') {
                $remoteSets = IconPicker::$plugin->getIconSources()->getRegisteredIconSources();
            }

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

    private function _getFiles($options)
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->iconSetsPath;

        $files = FileHelper::findFiles($iconSetsPath, $options);

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

        return $string;
    }
}