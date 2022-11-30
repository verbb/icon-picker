<?php
namespace verbb\iconpicker\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\MigrationHelper;

class Install extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->createTables();

        return true;
    }

    public function safeDown(): bool
    {
        $this->removeTables();
        $this->dropProjectConfig();

        return true;
    }

    public function createTables(): void
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
    }

    public function removeTables(): void
    {
        $this->dropTableIfExists('{{%iconpicker_iconsets}}');
    }

    public function dropProjectConfig(): void
    {
        Craft::$app->getProjectConfig()->remove('icon-picker');
    }
}
