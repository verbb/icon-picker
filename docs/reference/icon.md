# Icon
An Icon Picker field returns an `Icon` object, including when no icon is selected. Use `not icon.isEmpty()` in Twig to check for a selection. The object identifies the selected icon and provides the output helpers used by [Rendering Icons](docs:template-guides/rendering-icons).

<span id="attributes"></span>

## Properties

::: reference
### `value`

**Type:** `string|null`

The stored value that identifies the icon within its set. Its format depends on the icon’s `type`, as shown in [Stored Values](#stored-values).
:::

::: reference
### `iconSet`

**Type:** `string|null`

The folder, collection or variant the icon belongs to within its icon set, such as `/brands` for an SVG folder.
:::

::: reference
### `iconSetHandle`

**Type:** `string|null`

The current handle of the icon set the icon came from.
:::

::: reference
### `iconSetUid`

**Type:** `string|null`

The stable ID of the icon set the icon came from. Saved selections keep this ID when a set is renamed, and a deleted set’s selections aren’t attached to another set that reuses its handle.
:::

::: reference
### `label`

**Type:** `string|null`

The icon’s name, such as `twitter-square`. When the icon set doesn’t supply one, Icon Picker uses the SVG filename, glyph name or CSS value.
:::

::: reference
### `keywords`

**Type:** `string|null`

The words editors can search to find the icon, which default to its `label`.
:::

::: reference
### `type`

**Type:** `string|null`

The kind of icon, which is `svg`, `sprite`, `glyph` or `css`. It decides which output method to use.
:::

## Stored Values
The `value` property identifies the icon within its set. Its format depends on the Icon Set type.

Type | Description | Example
--- | --- | ---
`svg` | The filename and relative path of the icon. | `/my-folder/twitter-square.svg`
`sprite` | The name of the sprite within the spritesheet. | `twitter-square`
`glyph` | The font glyph name and glyph decimal. | `twitter-square:61569`
`css` | The name of the icon for the remote icon source. Commonly a CSS class. | `twitter-square`

## Methods

::: reference
### `count()`

**Returns:** `int`

Returns the length of the icon’s string output, which is `0` when no icon is selected. In Twig, use the `icon|length` filter.
:::

::: reference
### `isEmpty()`

**Returns:** `bool`

Returns whether no icon is selected. Use `not icon.isEmpty()` to check for a selection before rendering.
:::

::: reference
### `getUrl()`

**Returns:** `string|null`

Returns the image URL for an icon from a local SVG folder or a remote SVG set, or `null` for sprites, glyphs and CSS icons. Use it as an image source, as shown in [Rendering Icons](docs:template-guides/rendering-icons#render-an-image).
:::

::: reference
### `getPath()`

**Returns:** `string`

Returns the full server path of an icon from a local [SVG folder](docs:feature-tour/icon-sets#svg-folders), or an empty string for any other icon or a file outside the icons folder.
:::

::: reference
### `getInline()`

**Returns:** `Twig\Markup|null`

Returns the SVG markup for an icon from a local SVG folder or remote SVG set, or `null` when no SVG can be found. A remote SVG may need a server request. Use inline output only with icon sources you trust, and see [Rendering Inline SVG](docs:template-guides/rendering-icons#render-inline-svg) for where to place it.
:::

::: reference
### `getGlyph()`

**Arguments:** `format` (`string`, optional, defaults to `charHex`)

**Returns:** `string|null`

Returns a [web font](docs:feature-tour/icon-sets#web-fonts) glyph in the format you give, as listed in [Glyph Formats](#glyph-formats). Returns `null` for any other type of icon.
:::

::: reference
### `getGlyphName()`

**Returns:** `string|null`

Returns the name of a [web font](docs:feature-tour/icon-sets#web-fonts) glyph, or `null` for any other type of icon.
:::

## Glyph Formats

Format | Returns
--- | ---
`getGlyph('decimal')` | The glyph’s Unicode code point in decimal, such as `61569`.
`getGlyph('hex')` | The glyph’s Unicode code point in hexadecimal, such as `f081`.
`getGlyph('char')` | A decimal HTML character reference such as `&#61569;`.
`getGlyph('charHex')` | A hexadecimal HTML character reference such as `&#xf081;`. This is the default.
