<?php
namespace verbb\iconpicker\models;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\base\IconSourceInterface;
use verbb\iconpicker\helpers\IconPickerHelper;

use craft\base\Model;
use craft\helpers\FileHelper;
use craft\helpers\Json;

use Cake\Utility\Xml as XmlParser;
use Cake\Utility\Hash;
use FontLib\Font;

use Throwable;

class IconSet extends Model implements \JsonSerializable
{
    // Constants
    // =========================================================================

    public const TYPE_ROOT = 'root';
    public const TYPE_SPRITE = 'sprite';
    public const TYPE_FOLDER = 'folder';
    public const TYPE_FONT = 'font';
    public const TYPE_REMOTE = 'remote';


    // Properties
    // =========================================================================

    public ?string $key = null;
    public ?string $name = null;
    public ?string $type = null;
    public ?string $remoteSet = null;
    public array $icons = [];
    public array $fonts = [];
    public array $spriteSheets = [];
    public array $scripts = [];
    

    // Public Methods
    // =========================================================================

    public function jsonSerialize(): mixed
    {
        return $this->getAttributes();
    }

    public function fetchIcons(): void
    {
        $filePath = explode(':', $this->key)[1] ?? null;

        // Special-case for root folder
        if ($this->type === self::TYPE_ROOT) {
            $this->_fetchIconsForFolder('', false);
        }

        if ($this->type === self::TYPE_FOLDER) {
            $this->_fetchIconsForFolder($filePath);
        }

        if ($this->type === self::TYPE_SPRITE) {
            $this->_fetchIconsForSprite($filePath);
        }

        if ($this->type === self::TYPE_FONT) {
            $this->_fetchIconsForFont($filePath);
        }

        if ($this->type === self::TYPE_REMOTE) {
            if ($remoteSet = $this->getRemoteIconSource()) {
                $remoteSet->getIcons($this);
            }
        }
    }

    public function getFieldSettingLabel(): string
    {
        if ($this->type === self::TYPE_REMOTE) {
            if ($remoteSet = $this->getRemoteIconSource()) {
                if ($label = $remoteSet->getFieldSettingLabel($this)) {
                    return $label;
                }
            }
        }

        return $this->name . ' (' . ucwords($this->type) . ')';
    }

    public function getRemoteIconSource(): ?IconSourceInterface
    {
        return IconPicker::$plugin->getIconSources()->getRegisteredIconSourceByClass($this->remoteSet);
    }


    // Private Methods
    // =========================================================================

    private function _fetchIconsForFolder($folderName, $recursive = true): void
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->getIconSetsPath();

        // We only store a reference to the outer folder name without the path. Add it here
        $folderPath = FileHelper::normalizePath($iconSetsPath . DIRECTORY_SEPARATOR . $folderName);

        $files = $this->_getFiles($folderPath, [
            'only' => ['*.svg'],
            'except' => ['*-sprites.svg'],
            'recursive' => $recursive,
        ]);

        foreach ($files as $key => $file) {
            $item = str_replace($iconSetsPath, '', $file);

            $this->icons[] = new Icon([
                'type' => Icon::TYPE_SVG,
                'iconSet' => $folderName,
                'value' => $item,
            ]);
        }
    }

    private function _fetchIconsForSprite($spriteFile): void
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->getIconSetsPath();

        $spriteFilename = pathinfo($spriteFile, PATHINFO_FILENAME);
        $iconSetName = str_replace('-sprites', '', $spriteFilename);

        $spriteSheets = $this->_getFiles($iconSetsPath, [
            'only' => [$spriteFile],
            'recursive' => false,
        ]);

        foreach ($spriteSheets as $spriteSheet) {
            $files = $this->_fetchSvgsFromSprites($spriteSheet);

            foreach ($files as $i => $file) {
                $this->icons[] = new Icon([
                    'type' => Icon::TYPE_SPRITE,
                    'iconSet' => $iconSetName,
                    'value' => $file['@id'],
                ]);
            }

            $this->spriteSheets[$spriteSheet] = $files;
        }
    }

    private function _fetchIconsForFont($fontFile): void
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->getIconSetsPath();

        $fontFilename = pathinfo($fontFile, PATHINFO_FILENAME);

        $fonts = $this->_getFiles($iconSetsPath, [
            'only' => [$fontFile],
            'recursive' => false,
        ]);

        foreach ($fonts as $key => $file) {
            $glyphs = $this->_fetchFontGlyphs($file);

            foreach ($glyphs as $i => $glyph) {
                $this->icons[] = new Icon([
                    'type' => Icon::TYPE_GLYPH,
                    'iconSet' => $fontFilename,
                    'value' => $glyph['name'] . ':' . $glyph['glyphId'],
                ]);
            }

            $this->fonts[] = [
                'type' => 'local',
                'name' => 'font-face-' . $fontFilename,
                'url' => IconPickerHelper::getUrlForPath($file),
            ];
        }
    }

    private function _getFiles($path, $options): array
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->getIconSetsPath();

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

    private function _fetchSvgsFromSprites(string $file): array
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
        } catch (Throwable $e) {
            // Get a more useful error from parsing - if available
            $parseErrors = libxml_get_errors();
            IconPicker::error('Error processing SVG spritesheet ' . $file . ': ' . Json::encode($parseErrors) . ': ' . $e->getMessage());
        }

        // Normalise the sprites - there might only be a single sprite.
        if (Hash::dimensions($items) === 1) {
            $items = [$items];
        }

        return $items;
    }

    private function _fetchFontGlyphs(string $file): array
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
                if (!$names && $font->getFontName() == 'Material Icons') {
                    // Fetch the glyphId-keyed map
                    $names = IconPicker::$plugin->getIconSources()->getJsonData('material.json');

                    // There's also a bunch of things we want to exclude
                    $exclusions = [3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39];
                }

                foreach ($glyphs as $id => $gid) {
                    if (in_array($gid, $exclusions)) {
                        continue;
                    }

                    $items[] = [
                        'name' => $names[$gid] ?? sprintf("uni%04x", $id),
                        'glyphIndex' => $gid,
                        'glyphId' => $id,
                    ];
                }
            }
        } catch (Throwable $e) {
            IconPicker::error('Error processing icon font ' . $file . ': ' . $e->getMessage());
        }

        return $items;
    }

}
