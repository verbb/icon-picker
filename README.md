# Icon Picker Plugin for Craft CMS

Icon Picker is a field for Craft CMS to let your content editors select an icon from a selected folder for your project.

Currently only supports single SVG's, but files can be organised into multiple folders.

### Roadmap (before release)

- Support icon fonts
- Support SVG sprite maps
- Support select libraries (Font Awesome)

### Pricing
This plugin will be available on the plugin store after initial testing and development for $19.

## Installation
You can install Icon Picker via the plugin store, or through Composer.

### Craft Plugin Store
To install **Icon Picker**, navigate to the _Plugin Store_ section of your Craft control panel, search for `Icon Picker`, and click the _Try_ button.

### Composer
You can also add the package to your project using Composer.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:
    
        composer require verbb/icon-picker

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Icon Picker.

## Usage

Create your field, and optionally limit any particular folders you require. When editing an entry, use the dropdown field to select an icon, or start typing to filter icons by their filename.

## Configuration

Create an `icon-picker.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these per environment.

```php
<?php

return [
    '*' => [
        'iconSetsPath' => CRAFT_BASE_PATH . '/icons/',
        'iconSetsUrl' => '/icons/',
    ]
];
```

### Configuration options

- `iconSetsPath` - File system path to the base folder for your icons.
- `iconSetsUrl` - The base URL prepended to the path and filename of the icon.

## Icon

Whenever you're dealing with a icon in your template, you're actually working with a `Icon` object.

## Attributes

Attribute | Description
--- | ---
`id` | ID of the icon.
`icon` | The filename and relative path of the icon.
`width` | The width of the icon.
`height` | The height of the icon.

## Methods

Method | Description
--- | ---
`getDimensions(height)` | Returns an array of [width, height] for the icon. Pass in an optional height to restrict it by, while keeping the aspect ratio of the icon.
`getUrl()` | Return the full URL to the icon.
`getInline()` | Returns the raw contents of the icon.

## Available Variables

The following are common methods you will want to call in your front end templates:

### `craft.iconPicker.getIcon(icon)`

Fetches an `Icon` from the provided filename and relative path.

### `craft.iconPicker.getDimensions(icon, height)`

Returns the dimensions of an icon from the provided filename and relative path. Optionally provide a height to contrain the dimensions by.

### `craft.iconPicker.inline(icon)`

Returns the raw content from the provided filename and relative path.

### Credits
Based on [SVG Icons](https://github.com/fyrebase/svg-icons) for Craft 2.

<h2></h2>

<a href="https://verbb.io" target="_blank">
  <img width="100" src="https://verbb.io/assets/img/verbb-pill.svg">
</a>
