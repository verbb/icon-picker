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
use craft\helpers\ArrayHelper;
use craft\helpers\ElementHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;

class m221116_000000_migrate_iconsets extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('plugins.icon-picker.schemaVersion', true);

        if (version_compare($schemaVersion, '1.2.1', '>=')) {
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
            $remoteSets = ArrayHelper::remove($settings, 'remoteSets');

             // Only handle remote sets for Font Awesome 5 (our core one), and keep relying on the `font-awesome-all` handle
            if ($remoteSets) {
                $newIconSet = $this->getOrCreateSet(registerediconsets\FontAwesome::class, 'font-awesome-all');

                if ($newIconSet) {
                    if (!is_array($iconSets)) {
                        $iconSets = [];
                    }
                    
                    $iconSets['font-awesome-all'] = $newIconSet->uid;
                }
            }

            if (is_array($iconSets)) {
                foreach ($iconSets as $key => $iconSetKey) {
                    if (strstr($iconSetKey, 'folder:')) {
                        $newIconSet = $this->getOrCreateSet(registerediconsets\SvgFolder::class, str_replace('folder:', '', $iconSetKey));

                        if ($newIconSet) {
                            $iconSets[$key] = $newIconSet->uid;
                        }
                    }

                    if (strstr($iconSetKey, 'sprite:')) {
                        $newIconSet = $this->getOrCreateSet(registerediconsets\SvgSprite::class, str_replace('sprite:', '', $iconSetKey));

                        if ($newIconSet) {
                            $iconSets[$key] = $newIconSet->uid;
                        }
                    }

                    if (strstr($iconSetKey, 'font:')) {
                        $newIconSet = $this->getOrCreateSet(registerediconsets\WebFont::class, str_replace('font:', '', $iconSetKey));

                        if ($newIconSet) {
                            $iconSets[$key] = $newIconSet->uid;
                        }
                    }
                }
            }
            
            $settings['iconSets'] = $iconSets;

            $this->update('{{%fields}}', ['settings' => Json::encode($settings)], ['id' => $fieldData['id']]);
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
        echo "m221116_000000_migrate_iconsets cannot be reverted.\n";
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

            if (get_class($allIconSet) === $type && $type === registerediconsets\FontAwesome::class) {
                if ($allIconSet->handle === $key) {
                    return $allIconSet;
                }
            }
        }

        // Create the icon set
        $name = null;
        $handle = null;
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

        if ($type === registerediconsets\FontAwesome::class) {
            $name = $key;
            $handle = $key;
            $settings['type'] = 'cdn';
            $settings['cdnCollections'] = '*';
            $settings['cdnLicense'] = 'free';
            $settings['cdnVersion'] = '5.15.4';
        }

        $iconSetData = [
            'name' => $name,
            'handle' => $handle ?? StringHelper::camelCase($name . ' ' . rand()),
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
