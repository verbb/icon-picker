# Upgrading from v1
While the [changelog](https://github.com/verbb/icon-picker/blob/craft-4/CHANGELOG.md) is the most comprehensive list of changes, this guide provides high-level overview and organizes changes by category.

## Renamed Classes
The following classes have been renamed.

Old | What to do instead
--- | ---
| `verbb\iconpicker\models\IconModel` | `verbb\iconpicker\models\Icon`


## Icon
The following changes have been made to the [Icon](docs:developers/icon) object.

Old | What to do instead
--- | ---
| `icon` | `value`
| `sprite` | `value`
| `css` | `value`
| `glyphId` | `getGlyph()`
| `glyphName` | `getGlyphName()`
| `width` | No longer included
| `height` | No longer included
| `getIconName()` | `label`


### Variables
The following variables have been removed.

Old | What to do instead
--- | ---
| `craft.iconPicker.getIcon(icon)` | No longer included
| `craft.iconPicker.getDimensions(icon, height)` | No longer included
| `craft.iconPicker.inline(icon)` | `{{ entry.iconPickerField.inline }}`
