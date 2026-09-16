<?php
namespace verbb\iconpicker\helpers;

use verbb\iconpicker\fields\IconPickerField;

use Craft;
use craft\db\Query;
use craft\helpers\Json;

use yii\db\Expression;

class IconSetIdentity
{
    // Static Methods
    // =========================================================================

    public static function backfill(string $handle, string $uid): void
    {
        $fieldUids = [];

        // Include retained deleted fields/layouts so restored content keeps its identity.
        foreach ((new Query())->select(['uid', 'type'])->from('{{%fields}}')->all() as $field) {
            if (is_a($field['type'], IconPickerField::class, true)) {
                $fieldUids[$field['uid']] = true;
            }
        }

        $columns = [];

        foreach ((new Query())->select('config')->from('{{%fieldlayouts}}')->column() as $config) {
            $config = Json::decodeIfJson($config);

            foreach ($config['tabs'] ?? [] as $tab) {
                foreach ($tab['elements'] ?? [] as $element) {
                    if (isset($fieldUids[$element['fieldUid'] ?? '']) && !empty($element['uid'])) {
                        $columns[$element['uid']] = true;
                    }
                }
            }
        }

        if (!$columns) {
            return;
        }

        $db = Craft::$app->getDb();
        $query = (new Query())->select(['id', 'content'])->from('{{%elements_sites}}')
            ->where(['not', ['content' => null]])->orderBy(['id' => SORT_ASC]);

        foreach ($query->each(100) as $row) {
            if (!self::_updatedValues($row['content'], $columns, $handle, $uid)) {
                continue;
            }

            // The caller's project-config transaction keeps this lock through the rename/delete.
            // Re-read after locking, then patch only matching fields without rewriting other content.
            $content = $db->createCommand('SELECT [[content]] FROM {{%elements_sites}} WHERE [[id]] = :id FOR UPDATE', [':id' => $row['id']])->queryScalar();

            foreach (self::_updatedValues($content, $columns, $handle, $uid) as $column => $value) {
                $params = [':iconJson' => Json::encode($value)];

                if ($db->getIsMysql()) {
                    $params[':fieldPath'] = '$."' . $column . '"';
                    $expression = new Expression("JSON_SET([[content]], :fieldPath, JSON_EXTRACT(:iconJson, '$'))", $params);
                } else {
                    $params[':fieldUid'] = $column;
                    $expression = new Expression('jsonb_set([[content]], ARRAY[:fieldUid]::text[], CAST(:iconJson AS jsonb), false)', $params);
                }

                $db->createCommand()->update('{{%elements_sites}}', ['content' => $expression], ['id' => $row['id']])->execute();
            }
        }
    }

    private static function _updatedValues(mixed $content, array $columns, string $handle, string $uid): array
    {
        $content = Json::decodeIfJson($content);
        $updates = [];

        foreach (is_array($content) ? $content : [] as $column => $stored) {
            if (!isset($columns[$column])) {
                continue;
            }

            $value = Json::decodeIfJson($stored);

            if (is_array($value) && ($value['iconSetHandle'] ?? null) === $handle && empty($value['iconSetUid'])) {
                $value['iconSetUid'] = $uid;
                $updates[$column] = is_string($stored) ? Json::encode($value) : $value;
            }
        }

        return $updates;
    }
}
