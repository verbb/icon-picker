# Icon Sets

You can register your own Icon Sets to add support for third-party remote services, or even extend the existing Icon Set functionality.

```php
namespace modules\sitemodule;

use modules\sitemodule\BrandCssIconSet;
use verbb\iconpicker\events\RegisterIconSetsEvent;
use verbb\iconpicker\services\IconSets;
use yii\base\Event;

Event::on(IconSets::class, IconSets::EVENT_REGISTER_ICON_SETS, function(RegisterIconSetsEvent $event) {
    $event->iconSets[] = BrandCssIconSet::class;
});
```

### Example

Prefer extending or configuring the built-in Ionicons / Lucide / Bootstrap sets when they already cover your library. Register a custom set when you need a private brand kit or a provider Icon Picker does not ship.

```php
<?php
namespace modules\sitemodule;

use verbb\iconpicker\base\IconSet;
use verbb\iconpicker\models\Icon;

use Craft;

class BrandCssIconSet extends IconSet
{
    public static function displayName(): string
    {
        return Craft::t('site', 'Brand Icons');
    }

    public function fetchIcons(): void
    {
        $icons = [
            'logo',
            'mark',
            'spark',
            // ...
        ];

        foreach ($icons as $icon) {
            $this->icons[] = new Icon([
                'type' => Icon::TYPE_CSS,
                'value' => 'brand-icon brand-icon--' . $icon,
            ]);
        }

        // Optional: tell the CP which remote stylesheet to preview with
        $this->fonts[] = [
            'type' => 'remote',
            'name' => 'Brand Icons',
            'url' => 'https://cdn.example.com/brand-icons.css',
        ];
    }
}
```
