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

class m221115_000000_iconsets extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->archiveTableIfExists('{{%iconpicker_iconsets}}');
        $this->createTable('{{%iconpicker_iconsets}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string(64)->notNull(),
            'type' => $this->string()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'enabled' => $this->string()->notNull()->defaultValue('true'),
            'settings' => $this->text(),
            'dateDeleted' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m221115_000000_iconsets cannot be reverted.\n";
        return false;
    }
}
