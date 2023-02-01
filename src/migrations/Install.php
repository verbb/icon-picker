<?php
namespace verbb\iconpicker\migrations;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\iconsets\SvgFolder;

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
        $this->insertDefaultData();

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

    public function insertDefaultData(): void
    {
        $projectConfig = Craft::$app->projectConfig;

        // Don't make the same config changes twice
        $installed = ($projectConfig->get('plugins.icon-picker', true) !== null);
        $configExists = ($projectConfig->get('icon-picker', true) !== null);

        if (!$installed && !$configExists) {
            $this->_defaultIconSet();
        }
    }

    private function _defaultIconSet(): void
    {
        $iconSet = new SvgFolder([
            'name' => 'Root',
            'handle' => 'root',
            'enabled' => true,
            'folder' => '[root]',
        ]);

        IconPicker::$plugin->getIconSets()->saveIconSet($iconSet);
    }
}
