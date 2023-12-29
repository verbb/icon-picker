# Changelog

## 2.0.16 - 2023-12-08

### Added
- Add logging for Font Awesome kits.

### Fixed
- Fix a PHP 8.2 warning.

## 2.0.15 - 2023-10-25

### Fixed
- Fix an error when using Icon Picker with Redactor.

## 2.0.14 - 2023-09-25

### Changed
- Hidden folders are now excluded from searches to find svg icons. (thanks @sfsmfc).

## 2.0.13 - 2023-03-21

### Added
- Add warning to field settings when no icon sets are available.

### Changed
- Only admins are now allowed to access plugin settings.

### Fixed
- Fix multiple Redactor fields with Icon Picker enabled not working.
- Fix icon sets cache not working correctly for multiple Font Awesome API keys.
- Fix `Root` default icon set not being created on install.
- Fix Icon Picker fields not working correctly in Redactor fields.

## 2.0.12 - 2022-12-24

### Fixed
- Fix SVG folder icon sets being mis-ordered when scrolling the icon picker dropdown.

## 2.0.11 - 2022-12-16

### Fixed
- Add back support for subfolders for SVG icon sets.
- Fix an error being thrown when the path to an SVG cannot be determined.

## 2.0.10 - 2022-12-14

### Fixed
- Fix an issue where Redactor-based Icon Picker fields weren’t being initialized.
- Fix remote icon sets (Font Awesome) not migrating properly.
- Fix not migrating fields in Matrix and Super Table properly.

## 2.0.9 - 2022-12-14

### Fixed
- Fix when migrating field content, not to wipe old values if invalid.
- Fix SVG icons not rendering correctly in some instances.

## 2.0.8 - 2022-12-01

### Fixed
- Fix lack of support for migrating remote icon sets.
- Fix a potential error when saving a new icon set.
- Fix an error when switching icon set type.

## 2.0.7 - 2022-12-01

### Fixed
- Fix an error when installing Icon Picker as a fresh plugin.

## 2.0.6 - 2022-11-30

### Fixed
- Fix some icon sets not migrating from previous plugin versions correctly.

## 2.0.5 - 2022-11-25

### Fixed
- Fix an error when migrating Matrix-based icons.

## 2.0.4 - 2022-11-25

### Fixed
- Fix a potential migration error where icon models haven't been migrated properly.

## 2.0.3 - 2022-11-24

### Fixed
- Fix a migration error when migration icon sets for already-applied project config.

## 2.0.2 - 2022-11-24

### Fixed
- Fix an error with incorrect Formie references included.

## 2.0.1 - 2022-11-24

### Fixed
- Fix some migration issues from 1.x.

## 2.0.0 - 2022-11-23

### Added
- Added the concept of Icon Sets to better organise collections of icons. This allows for greater flexibility, consistency and control for icons.
- Added the ability to register your own Icon Sets (even extend existing ones) for advanced handling of icons.
- Added better handling for rendering lots of icons.
- Added `vue-virtual-scroller` to improve render performance for large icon sets.
- Added icon set preloading for more performance benefits.
- Added [Font Awesome 5/6 Free and Pro](https://fontawesome.com/).
- Added [Feather Icons](https://feathericons.com/).
- Added [Ionicons](https://ionic.io/ionicons).
- Added [CSS.gg](https://css.gg/).
- Added [Material Symbols](https://fonts.google.com/icons).
- Add Feed Me support.
- Add plugin settings to configure the size of icons in the dropdown icon-picker.
- Add `metadata.json` file support for icon sets, to provide extra keywords for searching.

### Changed
- Now requires PHP `^8.0.2`.
- Now requires Craft `^4.0.0-beta.1`.
- Now requires Icon Picker `1.1.12` in order to update from Craft 3.
- Migrate to Vue 3-based field input for more control and better client-side performance.
- Reorganise `Icon` and `IconSet` models, and improve server-side performance.
- Icon Picker will no longer automatically scan the nominated "Icons Path" for folders, sprites and fonts. Create an Icon Set instead.
- Revamp remote icon sets to be class-based, and treated like regular icon sets.
- Revamp Font Awesome remote set, add 5/6 Free and Pro CDN option, ability to select version/license/type/collections, and Kit option through API.
- Lazy-load non-SVG resources (fonts, spritesheets, scripts) for icon field values to save on-load performance hit.
- Change default path from `icons` to `icon-picker` to avoid some server setup conflicts.

### Removed
- Removed Icon Sources and the concept of Remote Icons Sets. These are all now Icon Sets.
- Removed `maxIconsShown` plugin setting, as no longer required.
- Removed `craft.iconPicker.getIcon()`.
- Removed `craft.iconPicker.getDimensions()`.
- Removed `craft.iconPicker.inline()`.
- Removed `Icon::icon`. Use `Icon::value` instead.
- Removed `Icon::sprite`. Use `Icon::value` instead.
- Removed `Icon::css`. Use `Icon::value` instead.
- Removed `Icon::glyphId`. Use `Icon::getGlyph()` instead.
- Removed `Icon::glyphName`. Use `Icon::getGlyphName()` instead.
- Removed `Icon::getIconName()`. Use `Icon::label` instead.
- Removed `Icon::width` and `Icon::height`.

## 1.1.13 - 2022-06-08

### Added
- Add `icon-picker-svg` and `icon-picker-sprite` classes to Redactor-chosen icons.
- Add support for multiple URLs for remote icon sets.

### Fixed
- Fix sprite sheets getting incorrect `id` attributes.
- Fix an error when a field contained an icon from a remote icon set that no longer exists.

## 1.1.12 - 2021-11-01

### Fixed
- Fix JS Redactor plugin not installing correctly in some instances.

## 1.1.11 - 2021-09-07

### Fixed
- Fix Icons Path plugin setting not working correctly when using aliases.

## 1.1.10 - 2020-12-07

### Changed
- Update Font Awesome remote source to 5.15.1.

### Fixed
- Ensure icons load in Super Table and other nesting fields.

## 1.1.9 - 2020-08-11

### Added
- Allow `iconSetsPath` settings to use environment variables or aliases.
- Allow `iconSetsUrl` settings to use environment variables or aliases.

## 1.1.8 - 2020-06-10

### Added
- Added `iconPickerField.length` and `iconPickerField.getLength()`.
- Added `iconPickerField.isEmpty` and `iconPickerField.getIsEmpty()`.

## 1.1.7 - 2020-06-09

### Fixed
- Fix error when only a single sprite existed in a sprite sheet.

## 1.1.6 - 2020-06-08

### Fixed
- Improve performance of remote icon sets. (thanks @bertoost).

## 1.1.5 - 2020-06-06

### Added
- Add `enableCache` setting.

### Fixed
- Fix `entry.iconField` direct output showing URL, even when an icon isn’t picked.

## 1.1.4 - 2020-04-16

### Fixed
- Fix logging error `Call to undefined method setFileLogging()`.

## 1.1.3 - 2020-04-15

### Changed
- File logging now checks if the overall Craft app uses file logging.
- Log files now only include `GET` and `POST` additional variables.

## 1.1.2 - 2020-02-11

### Fixed
- Fix windows path issues.
- Fix another potential error for field settings and icon sets.

## 1.1.1 - 2020-01-12

### Fixed
- Fix error with remote sets set to “All”.
- Fix error when installing for the first time.

## 1.1.0 - 2020-01-12

### Added
- Add GraphQL support for field. Now returns an IconModel.
- Add cache for each icon set. Icons for each set are now cached when the Icon Picker field is saved.
- Add controller action to lazy-load icon set content.
- Add utility to re-generate icon set caches.
- Improve performance for large icon sets.
- Add Redactor support. See [docs](https://verbb.io/craft-plugins/icon-picker/docs/feature-tour/redactor).
- Add support for [Google Material Design Icons](https://github.com/google/material-design-icons), which need some special-handling.

### Changed
- Major refactor, bringing performance improvements and caching.
- Update the icon selection field to lazy-load icons, rather than loading on page load. This brings significant performance benefits

### Fixed
- Fix `craft.iconPicker.spritesheet` not always returning content.

## 1.0.10 - 2019-09-04

### Fixed
- Fix numerous errors when fetching remote icon sets via URL.
- Change Font Awesome icon source to use file-system fetching, rather than via HTTP.

## 1.0.9 - 2019-09-03

### Fixed
- Fix icons not propagating to other sites.

## 1.0.8 - 2019-08-17

### Added
- Add `getPath()` function to icon model.
- Add supported translation methods for field.

### Changed
- Changing to Local file read from External URL.

### Fixed
- Fix error when resolving font awesome json file path.
- Fix error when trying to access remote icons sets.
- Fix error with font-name for icon font files.

## 1.0.7 - 2019-04-27

### Fixed
- Fix Live Preview not showing icon selection.
- Fix Neo fields clipping icon selection.
- Icon selection pane now attaches itself to the field, instead of the body.

## 1.0.6 - 2019-04-24

### Added
- Add `maxIconsShown` to control how many icons should be shown in the selection pane.

### Changed
- Update default `iconSetsPath` to be relative to the web folder - `CRAFT_BASE_PATH . '/web/icons/'`.

### Fixed
- Fix compatibility with some sprite definitions.
- Fix handling when no sub-folders in icon path, only single icons.

## 1.0.5 - 2019-04-07

### Added
- Add `model.hasIcon()`.

### Fixed
- Fix normalise-url function causing issues in some cases.

## 1.0.4 - 2019-03-17

### Added
- Add settings override notices for plugin settings screen.

### Fixed
- Fix SSL errors with spritesheets and icon sources when devMode is on.

## 1.0.3 - 2019-03-07

### Fixed
- Fix overflow issue for some fields.

## 1.0.2 - 2019-03-06

### Fixed
- Fix JS error causing issues across the control panel.

## 1.0.1 - 2019-03-05

- Initial (full) release.

## 1.0.0 - 2019-01-28

- Initial (beta) release.
