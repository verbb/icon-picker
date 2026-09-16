<?php
namespace verbb\iconpicker\iconsets;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\base\IconSet;
use verbb\iconpicker\helpers\IconPickerHelper;
use verbb\iconpicker\models\Icon;

use Craft;
use craft\helpers\FileHelper;
use craft\helpers\Json;

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

        // Only formats FontLib can glyph-index (TTF / OTF / WOFF). WOFF2 is listed in
        // some kits but cannot be parsed for the catalog — omit it from the picker (#107).
        $fonts = FileHelper::findFiles($iconSetsPath, [
            'only' => ['*.ttf', '*.woff', '*.otf'],
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

        if (!$this->fontFile) {
            return;
        }

        // Guard saved configs that still point at a .woff2 from older docs/UI.
        if (strtolower(pathinfo($this->fontFile, PATHINFO_EXTENSION)) === 'woff2') {
            IconPicker::error('Web Font icon set “' . $this->handle . '” uses a .woff2 file. Glyph indexing requires .ttf, .woff, or .otf — use one of those beside (or instead of) the woff2.');

            return;
        }

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
                    'iconSetHandle' => $this->handle,
                    'value' => $glyph['name'] . ':' . $glyph['glyphId'],
                    'keywords' => $keywords,
                ]);
            }

            $this->fonts[] = [
                'type' => 'local',
                'name' => IconPickerHelper::getFontClass($fontFilename),
                'url' => IconPickerHelper::getUrlForPath($file),
            ];
        }
    }

    public function getDiagnosticsSummary(): array
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->getIconSetsPath();

        $fullPath = $iconSetsPath . DIRECTORY_SEPARATOR . $this->fontFile;

        $lines = [
            'Font Path: ' . $fullPath,
            'Font URL: ' . IconPickerHelper::getUrlForPath($fullPath),
        ];

        if ($this->fontFile && strtolower(pathinfo($this->fontFile, PATHINFO_EXTENSION)) === 'woff2') {
            $lines[] = 'Warning: .woff2 cannot be glyph-indexed. Use .ttf, .woff, or .otf for the Web Font icon set.';
        }

        return $lines;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['fontFile'], 'required'];
        $rules[] = [
            ['fontFile'],
            function(string $attribute) {
                if (!$this->fontFile) {
                    return;
                }

                $ext = strtolower(pathinfo($this->fontFile, PATHINFO_EXTENSION));

                if ($ext === 'woff2') {
                    $this->addError($attribute, Craft::t(
                        'icon-picker',
                        'WOFF2 files cannot be used for glyph indexing. Choose a .ttf, .woff, or .otf file (you can still serve .woff2 on the front end separately).'
                    ));
                } elseif (!in_array($ext, ['ttf', 'woff', 'otf'], true)) {
                    $this->addError($attribute, Craft::t(
                        'icon-picker',
                        'Font file must be a .ttf, .woff, or .otf file.'
                    ));
                }
            },
        ];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _fetchFontGlyphs(string $file): array
    {
        $items = [];

        try {
            $font = Font::load($file);
            $font->parse();

            if ($font) {
                $glyphs = $font->getUnicodeCharMap();
                $names = $font->getData('post', 'names');
                $materialNames = null;

                // Support specific icon kits where they don't contain names
                if (!$names && $font->getFontName() == 'Material Icons') {
                    // Unicode assignments remain stable when font releases reorder glyphs.
                    $materialNames = Json::decodeFromFile(dirname(__DIR__) . '/json/material.json');
                }

                foreach ($glyphs as $id => $gid) {
                    // Material's ASCII glyphs are ligature inputs, not selectable icons.
                    if ($materialNames !== null && (($id >= 48 && $id <= 57) || $id === 95 || ($id >= 97 && $id <= 122))) {
                        continue;
                    }

                    $items[] = [
                        'name' => ($materialNames !== null ? ($materialNames[$id] ?? null) : ($names[$gid] ?? null)) ?? sprintf("uni%04x", $id),
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
