<?php
namespace verbb\iconpicker\migrations;

use craft\db\Migration;

class Install extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // See if we should migrate from SVG Icons plugin
        $migration = new SvgIconsPlugin();
        $migration->up();

        return true;
    }
}
