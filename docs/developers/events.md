# Events

Icon Picker provides events for extending its functionality. Modules and plugins can register event listeners, typically in their `init()` methods, to change Icon Picker’s behaviour.

## Icon Set Type Events

### The `registerIconSets` Event
The event that is triggered when Icon Picker builds the list of available icon set types. Append your class to `$event->iconSets` to register it. [Custom Icon Sets](docs:developers/custom-icon-sets) shows a complete icon set class.

```php
use modules\sitemodule\BrandCssIconSet;
use verbb\iconpicker\events\RegisterIconSetsEvent;
use verbb\iconpicker\services\IconSets;
use yii\base\Event;

Event::on(IconSets::class, IconSets::EVENT_REGISTER_ICON_SETS, function(RegisterIconSetsEvent $event) {
    $event->iconSets[] = BrandCssIconSet::class;
});
```

## Icon Set Events

None of the icon set events can be cancelled. `$event->iconSet` is the icon set being saved or deleted.

### The `beforeSaveIconSet` Event
The event that is triggered before an icon set is saved, and before it’s validated. A listener can change the icon set’s settings, and the changes are validated and saved with it. `$event->isNew` is `true` for a new icon set.

```php
use verbb\iconpicker\events\IconSetEvent;
use verbb\iconpicker\services\IconSets;
use yii\base\Event;

Event::on(IconSets::class, IconSets::EVENT_BEFORE_SAVE_ICON_SET, function(IconSetEvent $event) {
    $iconSet = $event->iconSet;
    $isNew = $event->isNew;
    \Craft::info("Preparing to save the {$iconSet->handle} icon set.", __METHOD__);
});
```

### The `afterSaveIconSet` Event
The event that is triggered after an icon set’s project config change has been applied, including when the change comes from deploying project config to another environment. `$event->isNew` is `true` when the icon set was just created.

```php
use verbb\iconpicker\events\IconSetEvent;
use verbb\iconpicker\services\IconSets;
use yii\base\Event;

Event::on(IconSets::class, IconSets::EVENT_AFTER_SAVE_ICON_SET, function(IconSetEvent $event) {
    $iconSet = $event->iconSet;
    \Craft::info("Saved the {$iconSet->handle} icon set.", __METHOD__);
});
```

### The `beforeDeleteIconSet` Event
The event that is triggered before an icon set is deleted from the control panel, before its removal is written to project config.

```php
use verbb\iconpicker\events\IconSetEvent;
use verbb\iconpicker\services\IconSets;
use yii\base\Event;

Event::on(IconSets::class, IconSets::EVENT_BEFORE_DELETE_ICON_SET, function(IconSetEvent $event) {
    $iconSet = $event->iconSet;
    \Craft::info("Preparing to delete the {$iconSet->handle} icon set.", __METHOD__);
});
```

### The `beforeApplyIconSetDelete` Event
The event that is triggered when Icon Picker applies an icon set’s removal from project config, before the icon set is removed from the database. It also runs when the removal comes from deploying project config to another environment.

```php
use verbb\iconpicker\events\IconSetEvent;
use verbb\iconpicker\services\IconSets;
use yii\base\Event;

Event::on(IconSets::class, IconSets::EVENT_BEFORE_APPLY_ICON_SET_DELETE, function(IconSetEvent $event) {
    $iconSet = $event->iconSet;
    \Craft::info("Removing the {$iconSet->handle} icon set.", __METHOD__);
});
```

### The `afterDeleteIconSet` Event
The event that is triggered after an icon set has been removed from the database and its cached icons cleared.

```php
use verbb\iconpicker\events\IconSetEvent;
use verbb\iconpicker\services\IconSets;
use yii\base\Event;

Event::on(IconSets::class, IconSets::EVENT_AFTER_DELETE_ICON_SET, function(IconSetEvent $event) {
    $iconSet = $event->iconSet;
    \Craft::info("Deleted the {$iconSet->handle} icon set.", __METHOD__);
});
```
