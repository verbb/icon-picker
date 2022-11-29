<?php
namespace verbb\iconpicker\migrations;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\base\IconSet;
use verbb\iconpicker\fields\IconPickerField;
use verbb\iconpicker\iconsets as registerediconsets;
use verbb\iconpicker\models\Icon;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\ElementHelper;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;

class m221130_000000_migrate_root_iconsets extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('plugins.icon-picker.schemaVersion', true);

        if (version_compare($schemaVersion, '1.2.2', '>=')) {
            IconPicker::$plugin->getService()->clearAndRegenerateCache();

            return true;
        }

        $fields = (new Query())
            ->from('{{%fields}}')
            ->where(['type' => IconPickerField::class])
            ->all();

        foreach ($fields as $fieldData) {
            $settings = Json::decode($fieldData['settings']);
            $iconSets = $settings['iconSets'] ?? [];

            // Fix `m221116_000000_migrate_iconsets` migration not factoring in `*` saved settings
            // and also always create a `[root]` icon set for all installs. Must be a separate migration
            // to fix already-applied project config installs

            // Always create a root icon set. Used to be automatic. No need to attach it to settings.
            $this->getOrCreateSet(registerediconsets\SvgFolder::class, '[root]');

            if ($iconSets === '*') {
                $settings = IconPicker::$plugin->getSettings();
                $iconSetsPath = $settings->getIconSetsPath();

                // Find an migrate any SVG folders
                $folders = FileHelper::findDirectories($iconSetsPath, [
                    'recursive' => false,
                ]);

                foreach ($folders as $folder) {
                    $path = str_replace($iconSetsPath, '', $folder);

                    $this->getOrCreateSet(registerediconsets\SvgFolder::class, $path);
                }

                // Find an migrate any SVG sprites
                $spriteSheets = FileHelper::findFiles($iconSetsPath, [
                    'only' => ['*-sprites.svg'],
                    'recursive' => false,
                ]);

                foreach ($spriteSheets as $spriteSheet) {
                    $name = pathinfo($spriteSheet, PATHINFO_FILENAME);
                    $name = str_replace('-sprites', '', $name);
                    $filename = basename($spriteSheet);

                    $this->getOrCreateSet(registerediconsets\SvgSprite::class, $filename);
                }

                // Find an migrate any fonts
                $fonts = FileHelper::findFiles($iconSetsPath, [
                    'only' => ['*.ttf', '*.woff', '*.otf', '*.woff2'],
                    'recursive' => false,
                ]);

                foreach ($fonts as $font) {
                    $name = pathinfo($font, PATHINFO_FILENAME);
                    $filename = basename($font);

                    $this->getOrCreateSet(registerediconsets\WebFont::class, $filename);
                }
            }

            // No need to update the field settings, keep referring to `*`.
        }

        // Re-generate all the caches
        IconPicker::$plugin->getService()->clearAndRegenerateCache();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m221130_000000_migrate_root_iconsets cannot be reverted.\n";
        return false;
    }

    public function getOrCreateSet(string $type, string $key): ?IconSet
    {
        $iconSet = null;
        $allIconSets = IconPicker::$plugin->getIconSets()->getAllIconSets();

        // Find an existing set that matches exactly
        foreach ($allIconSets as $allIconSet) {
            if (get_class($allIconSet) === $type && $type === registerediconsets\SvgFolder::class) {
                if ($allIconSet->folder === $key) {
                    return $allIconSet;
                }
            }

            if (get_class($allIconSet) === $type && $type === registerediconsets\SvgSprite::class) {
                if ($allIconSet->spriteFile === $key) {
                    return $allIconSet;
                }
            }

            if (get_class($allIconSet) === $type && $type === registerediconsets\WebFont::class) {
                if ($allIconSet->fontFile === $key) {
                    return $allIconSet;
                }
            }
        }

        // Create the icon set
        $name = '';
        $settings = [];

        if ($type === registerediconsets\SvgFolder::class) {
            $name = str_replace('/', '', $key);
            $settings['folder'] = $key;
        }

        if ($type === registerediconsets\SvgSprite::class) {
            $name = pathinfo($key, PATHINFO_FILENAME);
            $settings['spriteFile'] = $key;
        }

        if ($type === registerediconsets\WebFont::class) {
            $name = pathinfo($key, PATHINFO_FILENAME);
            $settings['fontFile'] = $key;
        }

        $iconSetData = [
            'name' => $name,
            'handle' => StringHelper::camelCase($name . ' ' . rand()),
            'type' => $type,
            'enabled' => true,
            'settings' => $settings,
        ];

        $iconSet = IconPicker::$plugin->getIconSets()->createIconSet($iconSetData);

        if (!IconPicker::$plugin->getIconSets()->saveIconSet($iconSet)) {
            return null;
        }

        return $iconSet;
    }
}
