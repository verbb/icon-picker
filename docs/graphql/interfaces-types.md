# Interfaces & Types

Each Icon Picker field adds a `{fieldHandle}_Icon` object type to Craft’s GraphQL schema, and every one of those types implements the `IconInterface` interface. [Queries](docs:graphql/queries) shows complete requests and responses.

## Interfaces

### The `IconInterface` Interface

`IconInterface` holds the fields shared by every Icon Picker field’s type. Use it for a fragment that works across different field handles, as shown in [Share a Fragment Across Fields](docs:graphql/queries#share-a-fragment-across-fields). All fields are nullable.

::: reference
#### `glyph`

**Type:** `String`

The glyph’s hexadecimal HTML character reference, such as `&#xf081;`, for a web font icon. Other icons return `null`.
:::

::: reference
#### `glyphName`

**Type:** `String`

The glyph’s name for a web font icon. Other icons return `null`.
:::

::: reference
#### `iconSet`

**Type:** `String`

The folder, collection or variant the icon belongs to within its icon set, such as `/brands` for an SVG folder.
:::

::: reference
#### `inline`

**Type:** `String`

The SVG markup of an icon from a local SVG folder. Icons from remote SVG sets and other types return `null`, so use `url` for those.
:::

::: reference
#### `isEmpty`

**Type:** `Boolean`

Whether no icon is selected. The field returns an object even when it’s empty.
:::

::: reference
#### `keywords`

**Type:** `String`

The words editors can search to find the icon, which default to its `label`.
:::

::: reference
#### `label`

**Type:** `String`

The icon’s name. When the icon set doesn’t supply one, Icon Picker uses the SVG filename, glyph name or CSS value.
:::

::: reference
#### `type`

**Type:** `String`

The kind of icon, which is `svg`, `sprite`, `glyph` or `css`.
:::

::: reference
#### `url`

**Type:** `String`

The full URL of an SVG icon. Other types of icon return `null`.
:::

::: reference
#### `value`

**Type:** `String`

The stored value that identifies the icon within its set. Its format depends on `type`, such as a relative path for an SVG or a class name for a CSS icon.
:::

## Types

### The `{fieldHandle}_Icon` Type

The selected icon in an Icon Picker field. For a field with the handle `iconPickerField`, the type is `iconPickerField_Icon`. It implements `IconInterface` and has exactly the same fields, so you can select them directly or through a fragment on the interface.

```graphql
iconPickerField {
  ... on IconInterface {
    type
    value
    url
  }
}
```

```json
{
  "iconPickerField": {
    "type": "svg",
    "value": "/brands/accessible-icon.svg",
    "url": "/assets/icons-testing/brands/accessible-icon.svg"
  }
}
```
