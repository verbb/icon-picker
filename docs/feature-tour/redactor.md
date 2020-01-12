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