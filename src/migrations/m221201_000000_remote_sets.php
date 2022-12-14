<?php
namespace verbb\iconpicker\migrations;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\fields\IconPickerField;
use verbb\iconpicker\models\Icon;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\ElementHelper;
use craft\helpers\Json;

class m221201_000000_remote_sets extends Migration
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
            // Fetch the field model because we'll need it later
            $field = Craft::$app->getFields()->getFieldByHandle($fieldData['handle'], $fieldData['context']);

            if ($field) {
                $column = ElementHelper::fieldColumnFromField($field);

                // Handle global field content
                if ($field->context === 'global') {
                    $content = (new Query())
                        ->select([$column, 'id', 'elementId'])
                        ->from('{{%content}}')
                        ->where(['not', [$column => null]])
                        ->andWhere(['not', [$column => '']])
                        ->all();

                    foreach ($content as $row) {
                        $settings = $this->convertModel($field, Json::decodeIfJson($row[$column]));

                        if ($settings) {
                            $this->update('{{%content}}', [$column => Json::encode($settings)], ['id' => $row['id']]);

                            echo 'Migrating content #' . $row['id'] . ' for element #' . $row['elementId'] . PHP_EOL;
                        }
                    }
                }

                // Handle Matrix field content
                if (strstr($field->context, 'matrixBlockType')) {
                    // Get the Matrix field, and the content table
                    $blockTypeUid = explode(':', $field->context)[1];

                    $matrixInfo = (new Query())
                        ->select(['fieldId', 'handle'])
                        ->from('{{%matrixblocktypes}}')
                        ->where(['uid' => $blockTypeUid])
                        ->one();

                    if ($matrixInfo) {
                        $matrixFieldId = $matrixInfo['fieldId'];
                        $matrixBlockTypeHandle = $matrixInfo['handle'];

                        $matrixField = Craft::$app->getFields()->getFieldById($matrixFieldId, false);

                        if ($matrixField) {
                            $column = ElementHelper::fieldColumn($field->columnPrefix, $matrixBlockTypeHandle . '_' . $field->handle, $field->columnSuffix);

                            $content = (new Query())
                                ->select([$column, 'id', 'elementId'])
                                ->from($matrixField->contentTable)
                                ->where(['not', [$column => null]])
                                ->andWhere(['not', [$column => '']])
                                ->all();

                            foreach ($content as $row) {
                                $settings = $this->convertModel($field, Json::decodeIfJson($row[$column]));
                                
                                if ($settings) {
                                    $this->update($matrixField->contentTable, [$column => Json::encode($settings)], ['id' => $row['id']]);
                                
                                    echo 'Migrating Matrix content (' . $matrixField->contentTable  . '_' . $column . ') #' . $row['id'] . ' for element #' . $row['elementId'] . PHP_EOL;
                                }
                            }
                        }
                    }
                }

                // Handle Super Table field content
                if (strstr($field->context, 'superTableBlockType')) {
                    // Get the Super Table field, and the content table
                    $blockTypeUid = explode(':', $field->context)[1];

                    $superTableFieldId = (new Query())
                        ->select(['fieldId'])
                        ->from('{{%supertableblocktypes}}')
                        ->where(['uid' => $blockTypeUid])
                        ->scalar();

                    $superTableField = Craft::$app->getFields()->getFieldById($superTableFieldId, false);

                    if ($superTableField) {
                        $column = ElementHelper::fieldColumn($field->columnPrefix, $field->handle, $field->columnSuffix);

                        $content = (new Query())
                            ->select([$column, 'id', 'elementId'])
                            ->from($superTableField->contentTable)
                            ->where(['not', [$column => null]])
                            ->andWhere(['not', [$column => '']])
                            ->all();

                        foreach ($content as $row) {
                            $settings = $this->convertModel($field, Json::decodeIfJson($row[$column]));

                            if ($settings) {
                                $this->update($superTableField->contentTable, [$column => Json::encode($settings)], ['id' => $row['id']]);
                            
                                echo 'Migrating Super Table content (' . $superTableField->contentTable . '_' . $column . ') #' . $row['id'] . ' for element #' . $row['elementId'] . PHP_EOL;
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m221201_000000_remote_sets cannot be reverted.\n";
        return false;
    }

    private function convertModel($field, $settings)
    {
        if (is_array($settings)) {
            $type = $settings['type'] ?? null;
            $iconSet = $settings['iconSet'] ?? null;
            $value = $settings['value'] ?? null;

            // Check if Font Awesome remote icon set
            if ($type === 'css' && $iconSet === 'font-awesome-all') {
                if (!str_contains($value, 'fa fa-')) {
                    $settings['value'] = 'fa fa-' . $value;
                } else {
                    $settings['value'] = $value;
                }
            }
        }

        return $settings;
    }
}
