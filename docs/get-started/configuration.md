# Configuration

You can customise Icon Picker’s settings using a PHP configuration file. This is optional: each setting has a default, so you only need to include the values you want to change.

To override a setting, create `icon-picker.php` in your Craft project’s `/config` directory and return an array of setting names and values. For example, the following will set the icon item size to 64 pixels:

```php
<?php

return [
    'iconItemWrapperSize' => 64,
];
```

All other settings keep their defaults. Add any further settings you want to change to the same array. The options below explain the available settings and their defaults.

Values in `config/icon-picker.php` override the corresponding values saved in the control panel. To manage a setting through the control panel again, remove its override from the file.

## Environment Overrides

Craft can apply different settings to each environment. Use `*` for shared values and a key matching `CRAFT_ENVIRONMENT` for an environment-specific override. For example:

```php
<?php

return [
    '*' => [
        'iconItemWrapperSize' => 56,
    ],
    'dev' => [
        'iconItemWrapperSize' => 64,
    ],
];
```

This replaces the simple array above. The `dev` values apply only when `CRAFT_ENVIRONMENT` is `dev`; other environments use the shared values. Merge your own overrides into the appropriate array.

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

::: reference
### `iconItemWrapperSize`

**Type:** `int` · **Default:** `56`

The number (in pixels) for the width and height of the icon wrapper when shown in the icon-selector dropdown. This represents the selectable square for the icon.
:::

::: reference
### `iconItemWrapperSizeLarge`

**Type:** `int` · **Default:** `72`

The number (in pixels) for the width and height of the icon wrapper when shown in the icon-selector dropdown, when `showLabels` is also enabled for the field settings. This represents the selectable square for the icon.
:::

::: reference
### `iconItemSize`

**Type:** `int` · **Default:** `32`

The number (in pixels) for the width and height of the inner icon when shown in the icon-selector dropdown. This represents the actual icon glyph within the wrapper.
:::

::: reference
### `iconItemSizeLarge`

**Type:** `int` · **Default:** `40`

The number (in pixels) for the width and height of the inner icon when shown in the icon-selector dropdown, when `showLabels` is also enabled for the field settings. This represents the actual icon glyph within the wrapper.
:::


## Control Panel
You can also manage configuration settings through the Control Panel by visiting Settings → Icon Picker.
