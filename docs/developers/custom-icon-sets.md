# Custom Icon Sets

An icon set supplies the icons editors can choose in an Icon Picker field. Create a custom icon set when your project needs a collection that the built-in sets can’t provide, such as brand icons styled by your own stylesheet. These examples use a bootstrapped [Craft module](https://craftcms.com/docs/5.x/extend/module-guide.html) with the namespace `modules\sitemodule`.

## Create the Icon Set Class

An icon set class extends `verbb\iconpicker\base\IconSet` and adds its icons in `fetchIcons()`. This example creates `modules/sitemodule/BrandCssIconSet.php` with three CSS icons. Replace the example stylesheet URL with a stylesheet you host that defines `brand-icon` and the three `brand-icon--…` classes. Your site must load the same stylesheet to render these icons publicly.

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

Each icon’s `iconSetHandle` identifies the set it came from, so Icon Picker can load the set’s styles for element previews and show it alongside other sets.

## Register the Icon Set

Icon Picker finds icon sets through the `registerIconSets` event. Register your class from your module or plugin’s `init()` method.

```php
use modules\sitemodule\BrandCssIconSet;
use verbb\iconpicker\events\RegisterIconSetsEvent;
use verbb\iconpicker\services\IconSets;
use yii\base\Event;

Event::on(IconSets::class, IconSets::EVENT_REGISTER_ICON_SETS, function(RegisterIconSetsEvent $event) {
    $event->iconSets[] = BrandCssIconSet::class;
});
```

## Icons and Resources

`fetchIcons()` fills these properties, and Icon Picker caches the result unless the [`enableCache`](docs:get-started/configuration#enablecache) setting is off.

| Property | What to Add |
| --- | --- |
| `icons` | One `verbb\iconpicker\models\Icon` per selectable icon, with its `type` (`Icon::TYPE_SVG`, `TYPE_SPRITE`, `TYPE_GLYPH` or `TYPE_CSS`), `value` and `iconSetHandle`. |
| `fonts` | Stylesheets or font files the control panel loads to preview the icons, each an array with a `type` of `local` or `remote`, a `name` and a `url`. |
| `scripts` | Scripts the control panel loads to preview the icons, such as a Font Awesome kit. |
| `cssAttribute` | The HTML attribute that holds a CSS icon’s value, which is `class` unless you change it. |

## Test the Icon Set

Create an icon set of type **Brand Icons** under **Icon Picker → Settings → Icon Sets**, then enable it on a test Icon Picker field. Select each icon and confirm the control-panel preview appears. Save the entry and [render its CSS classes](docs:template-guides/rendering-icons#remote-css-icons) on a page that loads your brand stylesheet.

## Methods

::: reference
### `displayName()`

**Returns:** `string`

Returns the name shown when choosing the icon set type in the control panel. This is a static method.
:::

::: reference
### `fetchIcons()`

**Returns:** `void`

Adds the icon set’s icons to `icons`, and any preview resources to `fonts` and `scripts`. Icon Picker calls it when the icons aren’t already cached.
:::

::: reference
### `getSettingsHtml()`

**Returns:** `string|null`

Returns the settings form shown when editing an icon set of this type, or `null` when it has no settings. Add a public property for each setting so Icon Picker saves it with the icon set.
:::
