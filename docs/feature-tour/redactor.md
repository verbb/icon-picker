# Redactor
Icon Picker has native support for [Redactor](https://plugins.craftcms.com/redactor). You're required to nominate an Icon Picker field to be used in your Redactor fields, so that you can configure the appropriate icon sets available.

Firstly, you'll want to edit your [Redactor config files](https://github.com/craftcms/redactor#redactor-configs), and be sure to add `icon-picker` to the plugins array.

```json
{
    "plugins": ["icon-picker"]
}
```

Next, create an Icon Picker field with the appropriate icon sets you'd like to use. Take note of this field's handle. Either head to Settings > Icon Picker in the CP, or add this to the [configuration](docs:get-started/configuration) (`redactorFieldHandle`).

You'll now have an Icon Picker button in every Redactor field that uses the config.

For sprites, load the matching spritesheet in your front-end layout as shown in [Loading Icon Assets](docs:template-guides/loading-icon-assets). Redactor content keeps the original symbol IDs; the control panel loads separate preview resources automatically.

The control panel’s icon styles are not included on your website. Add sizing to your front-end stylesheet, for example:

```css
.icon-picker-redactor-icon {
    display: inline-flex;
    width: 1em;
    height: 1em;
}

.icon-picker-redactor-icon svg {
    width: 100%;
    height: 100%;
}
```
