<?php
namespace verbb\iconpicker\migrations;

use verbb\iconpicker\fields\IconPickerField;

use craft\db\Migration;

class SvgIconsPlugin extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->update('{{%fields}}', ['type' => IconPickerField::class], ['type' => 'SvgIcons']);

        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }
}
