# Available Variables

The following are common methods you will want to call in your front end templates:

### `craft.iconPicker.getIcon(icon)`

Fetches an [Icon](docs:developers/icon) from the provided filename and relative path.

### `craft.iconPicker.getDimensions(icon, height)`

Returns the dimensions of an icon from the provided filename and relative path. Optionally provide a height to contrain the dimensions by.

### `craft.iconPicker.inline(icon)`

Returns the raw content from the provided filename and relative path.

### `craft.iconPicker.spritesheet(path)`

Returns the raw contents of an SVG Spritesheet for a provided path. Used to output the spritesheet on your site, inline with your template content.

### `craft.iconPicker.fontUrl(path)`

Returns the full URL to a font file, provided the given relateive path.

