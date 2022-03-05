<?php
namespace verbb\iconpicker\migrations;

use verbb\iconpicker\IconPicker;

use craft\db\Migration;

class m200112_000000_init_cache extends Migration
{
    public function safeUp(): bool
    {
        IconPicker::$plugin->getCache()->clearAndRegenerate();

        return true;
    }

    public function safeDown(): bool
    {
        echo "m200112_000000_init_cache cannot be reverted.\n";
        return false;
    }
}