<?php
namespace verbb\iconpicker\events;

use yii\base\Event;

class RegisterIconSourceEvent extends Event
{
    // Properties
    // =========================================================================

    public $sources = [];
}
