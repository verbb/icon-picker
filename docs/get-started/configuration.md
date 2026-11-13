# Configuration

You can customise Icon Picker’s settings using a PHP configuration file. This is optional: each setting has a default, so you only need to include the values you want to change.

To override a setting, create `icon-picker.php` in your Craft project’s `/config` directory and return an array of setting names and values. For example, the following will set the icon item wrapper size to 64 pixels:

```php
<?php

return [
    'iconItemWrapperSize' => 64,
];
```

All other settings keep their defaults. Add any further settings you want to change to the same array. The options below explain the available settings and their defaults. You can also manage many of these settings in the control panel under **Settings → Icon Picker**.

## Configuration Options

::: reference
### `enableCache`

**Type:** `bool` · **Default:** `true`

Whether to cache icons. This should **only** be set to `false` for testing purposes, as this can be resource-intensive.
:::

::: reference
### `iconSetsPath`

**Type:** `string` · **Default:** `'@webroot/icon-picker/'`

File system path to the base folder for your icons. The default is an `icon-picker` folder in your web root directory. This also accepts environment variables or aliases.
:::

::: reference
### `iconSetsUrl`

**Type:** `string` · **Default:** `'@web/icon-picker/'`

The base URL prepended to the path and filename of the icon. The default is an `icon-picker` folder in your web root. This also accepts environment variables or aliases.
:::

::: reference
### `redactorFieldHandle`

**Type:** `string` · **Default:** `''`

To enable Icon Picker for use with Redactor, supply the field handle for an Icon Picker field.
:::

The field’s **Default** icon size uses the following dimensions. **Small** and **Large** scale the artwork and adjust the selectable area, keeping space for below-icon labels.

::: reference
### `iconItemWrapperSize`

**Type:** `int` · **Default:** `56`

The number (in pixels) for the width and height of the icon wrapper when shown in the icon-selector dropdown. This represents the selectable square for the icon.
:::

::: reference
### `iconItemWrapperSizeLarge`

**Type:** `int` · **Default:** `72`

The number (in pixels) for the width and height of the icon wrapper when shown in the icon-selector dropdown, when **Labels** is set to **Below Icon** in the field settings. This represents the selectable square for the icon.
:::

::: reference
### `iconItemSize`

**Type:** `int` · **Default:** `32`

The number (in pixels) for the width and height of the inner icon when shown in the icon-selector dropdown. This represents the actual icon glyph within the wrapper.
:::

::: reference
### `iconItemSizeLarge`

**Type:** `int` · **Default:** `40`

The number (in pixels) for the width and height of the inner icon when shown in the icon-selector dropdown, when **Labels** is set to **Below Icon** in the field settings. This represents the actual icon glyph within the wrapper.
:::
