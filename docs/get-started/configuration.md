# Configuration
Create a `icon-picker.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these per environment.

The below shows the defaults already used by Icon Picker, so you don't need to add these options unless you want to modify the values.

```php
<?php

return [
    '*' => [
        'iconSetsPath' => '@webroot/icons/',
        'iconSetsUrl' => '@web/icons/',
        'maxIconsShown' => 100,
        'redactorFieldHandle' => '',
    ]
];
```

## Configuration options
- `iconSetsPath` - File system path to the base folder for your icons. The default is an `icons` folder in your web root directory. This also accepts environment variables or aliases.
- `iconSetsUrl` - The base URL prepended to the path and filename of the icon. The default is an `icons` folder in your web root. This also accepts environment variables or aliases.
- `maxIconsShown` - Set the maximum number of icons shown to pick from in the icon selection pane. This will not effect searching.
- `redactorFieldHandle` - To enable Icon Picker for use with Redactor, supply the field handle for an Icon Picker field.

## Control Panel
You can also manage configuration settings through the Control Panel by visiting Settings → Icon Picker.
