# Changelog

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
