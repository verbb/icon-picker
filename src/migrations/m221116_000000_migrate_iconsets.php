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
use craft\helpers\Json;
use craft\helpers\StringHelper;

class m221116_000000_migrate_iconsets extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $fields = (new Query())
            ->from('{{%fields}}')
            ->where(['type' => IconPickerField::class])
            ->all();

        foreach ($fields as $fieldData) {
            $settings = Json::decode($fieldData['settings']);
            $iconSets = $settings['iconSets'] ?? [];

            // Can't really be migrated = too different
            unset($settings['remoteSets']);

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
