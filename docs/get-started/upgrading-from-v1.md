# Upgrading from v1
While the [changelog](https://github.com/verbb/icon-picker/blob/craft-4/CHANGELOG.md) is the most comprehensive list of changes, this guide provides high-level overview and organizes changes by category.

## Icon Sets
An Icon Set is the concept of a collection of icons. You can create and manage these in **Icon Picker** > **Settings** > **Icon Sets**, and are stored in project config.

Previously, Icon Picker would scan the nominated "Icons Path" for folders, sprites and fonts. It would do its best to come up with a name for the icon source based on the path or filename used. This is no longer the case, and you'll be required to create an Icon Set for every folder, sprite or font file you want to use in the plugin.

In addition, we also had the concept of Remote icon sets (or Icon Sources), to handle offsite collections like Font Awesome. These are now also Icon Sets and are handled the same way for consistency.

This change opens up a means to tailor the name of the Icon Set (shown as a heading when picking an icon), the addition of extra settings for handling these sets, and for you to register your own Icon Sets. These could even extend the existing Icon Set classes. For example, you may have a special web font that needs particular handling. You could extend the native `verbb\iconpicker\iconsets\WebFont` class, and add your own handling to how font files are treated.

Migration-wise however, Icon Picker will automatically create Icon Sets for any folder, sprite or font file in your "Icons Path" folder, and update your fields. So there shouldn't be anything you need to do when upgrading.


## Icon Sources
Icon sources were previously used to register "remote" icon sets, like Font Awesome. These have now been replaced with Icon Sets.

You'll still be able to register your own sources of icons as an Icon Set.

Read further about [Icon Sets](docs:developers/icon-sets).


## Settings
The following plugin setting default value have changed.

Setting | Old | New
--- | --- | ---
| `iconSetsPath` | | `@webroot/icons/` | `@webroot/icon-picker/`
| `iconSetsUrl` | | `@web/icons/` | `@web/icon-picker/`


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
