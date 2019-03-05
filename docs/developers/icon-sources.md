# Icon Sources

Icon Picker can support remote sources for icons, and by default, works with [Font Awesome 5](https://fontawesome.com). When registering a remote icon set source, these icons will be available to pick within the field. You can add your own sources for icons using events.

The below shows an example of using [Ionicons](https://ionicons.com).

```php
use verbb\iconpicker\services\IconSources;
use verbb\iconpicker\events\RegisterIconSourceEvent;
use yii\base\Event;

Event::on(IconSources::class, IconSources::EVENT_REGISTER_ICON_SOURCE, function(RegisterIconSourceEvent $event) {
    $icons = [
        'add',
        'add-circle',
        'add-circle-outline',
        // ...
    ];

    $event->sources['ion-icons'] = [
        'label' => Craft::t('icon-picker', 'Ionicons'),
        'url' => 'https://unpkg.com/ionicons@4.4.4/dist/css/ionicons.min.css',
        'icons' => $icons,
        'classes' => 'icon ion-md-',
        'fontName' => 'Ionicons',
    ];
});
```