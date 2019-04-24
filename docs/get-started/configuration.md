# Configuration

Create an `icon-picker.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these per environment.

```php
<?php

return [
    '*' => [
        'iconSetsPath' => CRAFT_BASE_PATH . '/icons/',
        'iconSetsUrl' => '/icons/',
        'maxIconsShown' => 100,
    ]
];
```

### Configuration options

- `iconSetsPath` - File system path to the base folder for your icons.
- `iconSetsUrl` - The base URL prepended to the path and filename of the icon.
- `maxIconsShown` - Set the maximum number of icons shown to pick from in the icon selection pane. This will not effect searching.

## Control Panel

You can also manage configuration settings through the Control Panel by visiting Settings → Icon Picker.

