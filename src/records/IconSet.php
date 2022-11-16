<?php
namespace verbb\iconpicker\records;

use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;

class IconSet extends ActiveRecord
{
    // Traits
    // =========================================================================

    use SoftDeleteTrait;


    // Static Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%iconpicker_iconsets}}';
    }
}
