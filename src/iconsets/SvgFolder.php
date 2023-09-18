<?php
namespace verbb\iconpicker\iconsets;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\base\IconSet;
use verbb\iconpicker\helpers\IconPickerHelper;
use verbb\iconpicker\models\Icon;

use Craft;
use craft\helpers\FileHelper;

class SvgFolder extends IconSet
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('icon-picker', 'SVG Folder');
    }


    // Properties
    // =========================================================================

    public ?string $folder = null;


    // Public Methods
    // =========================================================================

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['folder'], 'required'];

        return $rules;
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('icon-picker/icon-sets/svg-folder', [
            'iconSet' => $this,
        ]);
    }

    public function getFolderOptions(): array
    {
        $options = [
            ['label' => Craft::t('icon-picker', '/'), 'value' => '[root]'],
        ];

        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->getIconSetsPath();

        if (is_dir($iconSetsPath)) {
            $folders = FileHelper::findDirectories($iconSetsPath, [
                'recursive' => true,
            ]);

            foreach ($folders as $folder) {
                $path = str_replace($iconSetsPath, '', $folder);
                $name = basename($folder);

                $options[] = ['label' => $path, 'value' => $path];
            }
        }

        return $options;
    }

    public function fetchIcons(): void
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->getIconSetsPath();

        $folder = ($this->folder === '[root]') ? '' : $this->folder;

        // We only store a reference to the outer folder name without the path. Add it here
        $folderPath = FileHelper::normalizePath($iconSetsPath . DIRECTORY_SEPARATOR . $folder);

        $files = IconPickerHelper::getFiles($folderPath, [
            'only' => ['*.svg'],
            'except' => ['*-sprites.svg', '_*'],
            'recursive' => true,
        ]);

        foreach ($files as $key => $file) {
            $item = str_replace($iconSetsPath, '', $file);
            $name = pathinfo($item, PATHINFO_FILENAME);

            // Find any metadata alongside the icons
            $keywords = $this->getMetadata($folderPath . DIRECTORY_SEPARATOR . 'metadata.json', $name);

            $this->icons[] = new Icon([
                'type' => Icon::TYPE_SVG,
                'iconSet' => $this->handle,
                'value' => $item,
                'keywords' => $keywords,
            ]);
        }
    }
}
