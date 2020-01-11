<?php
namespace verbb\iconpicker\migrations;

use verbb\iconpicker\IconPicker;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\queue\jobs\ResaveElements;

class m200112_000000_init_cache extends Migration
{
    public function safeUp()
    {
        IconPicker::$plugin->getCache()->clearAndRegenerate();
    }

    public function safeDown()
    {
        echo "m200112_000000_init_cache cannot be reverted.\n";
        return false;
    }
}