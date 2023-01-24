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

use Cake\Utility\Xml as XmlParser;
use Cake\Utility\Hash;

class SvgSprite extends IconSet
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('icon-picker', 'SVG Sprite');
    }


    // Properties
    // =========================================================================

    public ?string $spriteFile = null;


    // Public Methods
    // =========================================================================

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['spriteFile'], 'required'];

        return $rules;
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('icon-picker/icon-sets/svg-sprite', [
            'iconSet' => $this,
        ]);
    }

    public function getFolderOptions(): array
    {
        $options = [];
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->getIconSetsPath();

        $spriteSheets = FileHelper::findFiles($iconSetsPath, [
            'only' => ['*-sprites.svg'],
            'recursive' => false,
        ]);

        foreach ($spriteSheets as $spriteSheet) {
            $path = str_replace($iconSetsPath, '', $spriteSheet);
            $filename = basename($spriteSheet);

            $options[] = ['label' => $path, 'value' => $filename];
        }

        return $options;
    }

    public function fetchIcons(): void
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->getIconSetsPath();

        $spriteFilename = pathinfo($this->spriteFile, PATHINFO_FILENAME);
        $iconSetName = str_replace('-sprites', '', $spriteFilename);

        $spriteSheets = IconPickerHelper::getFiles($iconSetsPath, [
            'only' => [$this->spriteFile],
            'recursive' => false,
        ]);

        $fullPath = $iconSetsPath . DIRECTORY_SEPARATOR . $this->spriteFile;
        $metadataPath = str_replace('.svg', '-metadata.json', $fullPath);

        foreach ($spriteSheets as $spriteSheet) {
            $files = $this->_fetchSvgsFromSprites($spriteSheet);

            foreach ($files as $i => $file) {
                // Find any metadata alongside the icons
                $keywords = $this->getMetadata($metadataPath, $file['@id']);

                $this->icons[] = new Icon([
                    'type' => Icon::TYPE_SPRITE,
                    'iconSet' => $iconSetName,
                    'value' => $file['@id'],
                    'keywords' => $keywords,
                ]);
            }

            $this->spriteSheets[$spriteSheet] = $files;
        }
    }


    // Private Methods
    // =========================================================================

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
}
