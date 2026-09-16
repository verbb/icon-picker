# Icon Sets

Register an Icon Set when your project needs a collection that the built-in sets cannot provide. This example adds three brand icons supplied by your own stylesheet.

Start with a bootstrapped [Craft module](https://craftcms.com/docs/5.x/extend/module-guide.html) whose namespace is `modules\sitemodule`. Create `BrandCssIconSet.php` beside its `Module.php` file. Put the class from the example below in that file, then add the following imports at the top of `Module.php` and register the listener inside `Module::init()`, after `parent::init()`:

The registration snippet is partial module code:

```php
use modules\sitemodule\BrandCssIconSet;
use verbb\iconpicker\events\RegisterIconSetsEvent;
use verbb\iconpicker\services\IconSets;
use yii\base\Event;

Event::on(IconSets::class, IconSets::EVENT_REGISTER_ICON_SETS, function(RegisterIconSetsEvent $event) {
    $event->iconSets[] = BrandCssIconSet::class;
});
```

## Example

The following class supplies the selectable values. Replace the example stylesheet URL with a stylesheet you host that defines `brand-icon` and the three `brand-icon--…` classes. The same stylesheet must be loaded by your site to render these icons publicly.

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
        ];

        foreach ($icons as $icon) {
            $this->icons[] = new Icon([
                'type' => Icon::TYPE_CSS,
                'iconSetHandle' => $this->handle,
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

Each icon’s `iconSetHandle` identifies its source set so Icon Picker can load its styles for element previews and render it alongside other sets.

Create an Icon Set of type **Brand Icons** under **Icon Picker → Settings → Icon Sets**, then enable it on a test Icon Picker field. Select each icon and confirm the control-panel preview appears. Save the entry and [render its CSS classes](docs:template-guides/rendering-icons#remote-css-icons) on a page that loads your brand stylesheet.
