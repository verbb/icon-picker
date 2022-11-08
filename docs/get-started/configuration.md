# Configuration
Create a `icon-picker.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these per environment.

The below shows the defaults already used by Icon Picker, so you don't need to add these options unless you want to modify the values.

```php
<?php

return [
    '*' => [
        'enableCache' => true,
        'iconSetsPath' => '@webroot/icons/',
        'iconSetsUrl' => '@web/icons/',
        'redactorFieldHandle' => '',
    ]
];
```

## Configuration options
- `enableCache` - Whether the cache icons. This should **only** be set to `false` for testing purposes, as this can be resource-intensive.
- `iconSetsPath` - File system path to the base folder for your icons. The default is an `icons` folder in your web root directory. This also accepts environment variables or aliases.
- `iconSetsUrl` - The base URL prepended to the path and filename of the icon. The default is an `icons` folder in your web root. This also accepts environment variables or aliases.
- `redactorFieldHandle` - To enable Icon Picker for use with Redactor, supply the field handle for an Icon Picker field.

## Control Panel
You can also manage configuration settings through the Control Panel by visiting Settings → Icon Picker.
