# Icon Sets
You can register your own Icon Sets to add support for third-party remote services, or even extend the existing Icon Set functionality.

```php
namespace modules\sitemodule;

use modules\sitemodule\IonicIconSet;
use verbb\iconpicker\events\RegisterIconSetsEvent;
use verbb\iconpicker\services\IconSets;
use yii\base\Event;

Event::on(IconSets::class, IconSets::EVENT_REGISTER_ICON_SETS, function(RegisterIconSetsEvent $event) {
    $event->iconSets[] = IonicIconSet::class;
});
```

### Example
The below shows an example of using [Ionicons](https://ionicons.com).

```php
<?php
namespace modules\sitemodule;

use verbb\iconpicker\base\IconSet;
use verbb\iconpicker\models\Icon;

use Craft;

class IonicIconSet extends IconSet
{
    public static function displayName(): string
    {
        return Craft::t('icon-picker', 'Ionicons');
    }

    public function fetchIcons(): void
    {
        // Add your icon definitions here...
        $icons = [
            'add',
            'add-circle',
            'add-circle-outline',
            // ...
        ];

        // Add them ad "Icon" models to the icon set
        foreach ($icons as $icon) {
            $this->icons[] = new Icon([
                'type' => Icon::TYPE_CSS,
                'value' => 'icon ion-md-' . $icon,
            ]);
        }

        // Add the remote CSS to rendering
        $this->fonts[] = [
            'type' => 'remote',
            'name' => 'Ionicons',
            'url' => 'https://unpkg.com/ionicons@4.4.4/dist/css/ionicons.min.css',
        ];
    }
}
```
