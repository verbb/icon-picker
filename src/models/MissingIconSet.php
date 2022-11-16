<?php
namespace verbb\iconpicker\models;

use verbb\iconpicker\base\IconSet;

use Craft;
use craft\base\MissingComponentInterface;
use craft\base\MissingComponentTrait;

class MissingIconSet extends IconSet implements MissingComponentInterface
{
    // Traits
    // =========================================================================

    use MissingComponentTrait;


    // Public Methods
    // =========================================================================

    public static function typeName(): string
    {
        return Craft::t('icon-picker', 'Missing Icon Set');
    }

    public function getDescription(): string
    {
        return '';
    }
}
