# Icon

Whenever you're dealing with a icon in your template, you're actually working with a `Icon` object.

## Attributes

Attribute | Description
--- | ---
`id` | ID of the icon.
`icon` | The filename and relative path of the icon.
`sprite` | The filename and relative path of the icon. Set only if using [SVG Sprites](docs:feature-tour/svg-icons).
`glyphId` | Set only if using an [Icon Font](docs:feature-tour/icon-fonts).
`glyphName` | Set only if using an [Icon Font](docs:feature-tour/icon-fonts).
`css` | Set only if using an [Remote Icon](docs:feature-tour/remote-icon-sets).
`iconSet` | The icon set this icon belongs to.
`type` | What type of icon this is: `svg`, `sprite`, `glyph` or `css`.
`width` | The width of the icon.
`height` | The height of the icon.

## Methods

Method | Description
--- | ---
`getDimensions(height)` | Returns an array of [width, height] for the icon. Pass in an optional height to restrict it by, while keeping the aspect ratio of the icon.
`getUrl()` | Return the full URL to the icon.
`getPath()` | Return the full path to the icon.
`getInline()` | Returns the raw contents of the icon.
`getIconName()` | Returns the name of the icon, without the extension.
`getGlyph(format)` | Returns the character representation of an individual icon glyph, for when an icon font is used.

#### Glyph formats
Format | Example
--- | ---
`getGlyph('decimal')` | Get the icon unicode (decimal).
`getGlyph('hex')` | Get the icon unicode (hexadecimal).
`getGlyph('char')` | Display the icon as html character - `&#00000`.
`getGlyph('charHex')` | Display the icon as html character hex - `&#xf100`. Default
