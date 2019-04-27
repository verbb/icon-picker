# Changelog

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
