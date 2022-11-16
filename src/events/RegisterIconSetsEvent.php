<?php
namespace verbb\iconpicker\events;

use yii\base\Event;

class RegisterIconSetsEvent extends Event
{
    // Properties
    // =========================================================================

    public ?array $iconSets = [];
    
}
