<?php
namespace verbb\iconpicker\iconsets;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\base\IconSet;
use verbb\iconpicker\helpers\IconPickerHelper;
use verbb\iconpicker\models\Icon;

use Craft;
use craft\helpers\FileHelper;

use Throwable;

use FontLib\Font;

class WebFont extends IconSet
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('icon-picker', 'Web Font');
    }


    // Properties
    // =========================================================================

    public ?string $fontFile = null;


    // Public Methods
    // =========================================================================

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['fontFile'], 'required'];

        return $rules;
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('icon-picker/icon-sets/web-font', [
            'iconSet' => $this,
        ]);
    }

    public function getFolderOptions(): array
    {
        $options = [];
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->getIconSetsPath();

        // Get all icon fonts
        $fonts = FileHelper::findFiles($iconSetsPath, [
            'only' => ['*.ttf', '*.woff', '*.otf', '*.woff2'],
            'recursive' => false,
        ]);

        foreach ($fonts as $font) {
            $path = str_replace($iconSetsPath, '', $font);
            $filename = basename($font);

            $options[] = ['label' => $path, 'value' => $filename];
        }

        return $options;
    }

    public function fetchIcons(): void
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->getIconSetsPath();

        $fontFilename = pathinfo($this->fontFile, PATHINFO_FILENAME);

        $fonts = IconPickerHelper::getFiles($iconSetsPath, [
            'only' => [$this->fontFile],
            'recursive' => false,
        ]);

        $metadataPath = $iconSetsPath . DIRECTORY_SEPARATOR . $fontFilename . '-metadata.json';

        foreach ($fonts as $key => $file) {
            $glyphs = $this->_fetchFontGlyphs($file);

            foreach ($glyphs as $i => $glyph) {
                // Find any metadata alongside the icons
                $keywords = $this->getMetadata($metadataPath, $glyph['name']);

                $this->icons[] = new Icon([
                    'type' => Icon::TYPE_GLYPH,
                    'iconSet' => $fontFilename,
                    'value' => $glyph['name'] . ':' . $glyph['glyphId'],
                    'keywords' => $keywords,
                ]);
            }

            $this->fonts[] = [
                'type' => 'local',
                'name' => 'font-face-' . $fontFilename,
                'url' => IconPickerHelper::getUrlForPath($file),
            ];
        }
    }


    // Private Methods
    // =========================================================================

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