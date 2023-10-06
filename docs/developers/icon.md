# Icon
Whenever you're dealing with an icon in your template, you're actually working with a `Icon` object.

## Attributes

Attribute | Description
--- | ---
`value` | The value of the icon. This will vary depending on the type of icon.
`iconSet` | The icon set this icon belongs to.
`label` | The named representation of the icon.
`keywords` | The keywords used to search for the icon by. Defaults to the `label`.
`type` | What type of icon this is: `svg`, `sprite`, `glyph` or `css`.
`length` | Return the string length of the icon for the field.

### Value
To normalise the behaviour of an `Icon` object, we use a `value` attribute as the main value to identify the icon within the icon set. This will vary depending on the type of icon this is.

Type | Description | Example
--- | --- | ---
`svg` | The filename and relative path of the icon. | `/my-folder/twitter-square.svg`
`sprite` | The name of the sprite within the spritesheet. | `twitter-square`
`glyph` | The font glyph name and glyph decimal. | `twitter-square:61569`
`css` | The name of the icon for the remote icon source. Commonly a CSS class. | `twitter-square`

## Methods

Method | Description
--- | ---
`isEmpty()` | Returns whether or not there's an icon selected for this field.
`getUrl()` | Return the full URL to the icon. [SVG Icons](docs:feature-tour/icon-sets#svg-folders) only.
`getPath()` | Return the full path to the icon. [SVG Icons](docs:feature-tour/icon-sets#svg-folders) only.
`getInline()` | Returns the raw contents of the icon. [SVG Icons](docs:feature-tour/icon-sets#svg-folders) only.
`getGlyph(format)` | Returns the character representation of a font glyph. [Icon Font](docs:feature-tour/icon-sets#web-fonts) only.
`getGlyphName()` | Returns the named representation of a font glyph. [Icon Font](docs:feature-tour/icon-sets#web-fonts) only.

### Glyph formats

Format | Example
--- | ---
`getGlyph('decimal')` | Get the icon unicode (decimal).
`getGlyph('hex')` | Get the icon unicode (hexadecimal).
`getGlyph('char')` | Display the icon as html character - `&#00000`.
`getGlyph('charHex')` | Display the icon as html character hex - `&#xf100`. Default

