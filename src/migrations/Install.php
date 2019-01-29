<?php
namespace verbb\iconpicker\migrations;

use Craft;
use craft\db\Migration;

class Install extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp()
    {
        // See if we should migrate from SVG Icons plugin
        $migration = new SvgIconsPlugin();
        $migration->up();
    }
}
