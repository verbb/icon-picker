<?php
namespace verbb\iconpicker\events;

use verbb\iconpicker\base\IconSet;

use yii\base\Event;

class IconSetEvent extends Event
{
    // Properties
    // =========================================================================

    public ?IconSet $iconSet = null;
    public bool $isNew = false;
    
}
