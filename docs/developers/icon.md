# Icon
An Icon Picker field returns an `Icon` object when it has a selection. The object identifies the selected icon and provides the output helpers used by [Rendering Icons](docs:template-guides/rendering-icons).

<span id="attributes"></span>

## Properties

::: reference
### `value`

**Type:** `string|null`

The value of the icon. This will vary depending on the type of icon.
:::

::: reference
### `iconSet`

**Type:** `string|null`

The icon set this icon belongs to.
:::

::: reference
### `label`

**Type:** `string|null`

The named representation of the icon.
:::

::: reference
### `keywords`

**Type:** `string|null`

The keywords used to search for the icon by. Defaults to the `label`.
:::

::: reference
### `type`

**Type:** `string|null`

What type of icon this is: `svg`, `sprite`, `glyph` or `css`.
:::

::: reference
### `length`

**Type:** `int`

The character length of the value returned when the icon is converted to a string.
:::


### Stored Values
The `value` property identifies the icon within its set. Its format depends on the Icon Set type.

Type | Description | Example
--- | --- | ---
`svg` | The filename and relative path of the icon. | `/my-folder/twitter-square.svg`
`sprite` | The name of the sprite within the spritesheet. | `twitter-square`
`glyph` | The font glyph name and glyph decimal. | `twitter-square:61569`
`css` | The name of the icon for the remote icon source. Commonly a CSS class. | `twitter-square`

## Methods

::: reference
### `isEmpty()`

**Returns:** `bool`

Returns `true` when no icon is selected and `false` when the object has an icon value.
:::

::: reference
### `getUrl()`

**Returns:** `string|null`

Returns the image URL for a local SVG Folder or remote SVG set. Returns `null` for sprites and font or CSS icons. Use this URL as an image source, as shown in [Rendering Icons](docs:template-guides/rendering-icons#render-an-image).
:::

::: reference
### `getPath()`

**Returns:** `string`

Return the full path to the icon. [SVG Icons](docs:feature-tour/icon-sets#svg-folders) only.
:::

::: reference
### `getInline()`

**Returns:** `Twig\Markup|null`

Returns SVG markup for a local SVG Folder or remote SVG set, or `null` when no SVG can be resolved. A remote SVG may require a server request. Use inline output only with trusted icon sources; [Rendering Inline SVG](docs:template-guides/rendering-icons#render-inline-svg) shows where to place it.
:::

::: reference
### `getGlyph(format = 'charHex')`

**Returns:** `string|null`

Returns an icon-font glyph in `decimal`, `hex`, `char`, or `charHex` format. The default is `charHex`. This applies only to [Web Fonts](docs:feature-tour/icon-sets#web-fonts).
:::

::: reference
### `getGlyphName()`

**Returns:** `string|null`

Returns the named representation of a font glyph. [Icon Font](docs:feature-tour/icon-sets#web-fonts) only.
:::


### Glyph Formats

Format | Example
--- | ---
`getGlyph('decimal')` | Get the icon unicode (decimal).
`getGlyph('hex')` | Get the icon unicode (hexadecimal).
`getGlyph('char')` | Return a decimal HTML character reference such as `&#61569;`.
`getGlyph('charHex')` | Return a hexadecimal HTML character reference such as `&#xf081;`. This is the default.
