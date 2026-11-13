# Queries

Icon Picker doesn’t add its own GraphQL queries. An Icon Picker field adds its value to Craft’s element types, so you query it through Craft’s own queries, such as `entries`, the same way as any other custom field. The field returns a `{fieldHandle}_Icon` object, which implements the `IconInterface` interface.

## Set Up the Schema

In **GraphQL → Schemas**, edit the schema used by your access token and allow it to read the section that contains your Icon Picker field. [Craft’s GraphQL documentation](https://craftcms.com/docs/5.x/development/graphql.html) explains schemas, tokens and endpoints.

## Query an Icon

Replace `blog_blog_Entry` and `iconPickerField` below with the generated entry type and field handle from your project.

```graphql
{
    entries(id: 1234) {
        ... on blog_blog_Entry {
            iconPickerField {
                value
                iconSet
                label
                keywords
                type
                isEmpty
                url
                inline
                glyph
                glyphName
            }
        }
    }
}
```

A selected local SVG returns data in this shape. The example uses `null` for the font-only properties.

```json
{
    "data": {
        "entries": [
            {
                "iconPickerField": {
                    "value": "/brands/accessible-icon.svg",
                    "iconSet": "/brands",
                    "label": "accessible-icon",
                    "keywords": "accessible-icon",
                    "type": "svg",
                    "isEmpty": false,
                    "url": "/assets/icons-testing/brands/accessible-icon.svg",
                    "inline": "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 448 512\"><path d=\"M423.9 255.8L411 413.1c-3.3 40.7-63.9 35.1-60.6-4.9l10-122.5-41.1 2.3c10.1 20.7 15.8 43.9 15.8 68.5 0 41.2-16.1 78.7-42.3 106.5l-39.3-39.3c57.9-63.7 13.1-167.2-74-167.2-25.9 0-49.5 9.9-67.2 26L73 243.2c22-20.7 50.1-35.1 81.4-40.2l75.3-85.7-42.6-24.8-51.6 46c-30 26.8-70.6-18.5-40.5-45.4l68-60.7c9.8-8.8 24.1-10.2 35.5-3.6 0 0 139.3 80.9 139.5 81.1 16.2 10.1 20.7 36 6.1 52.6L285.7 229l106.1-5.9c18.5-1.1 33.6 14.4 32.1 32.7zm-64.9-154c28.1 0 50.9-22.8 50.9-50.9C409.9 22.8 387.1 0 359 0c-28.1 0-50.9 22.8-50.9 50.9 0 28.1 22.8 50.9 50.9 50.9zM179.6 456.5c-80.6 0-127.4-90.6-82.7-156.1l-39.7-39.7C36.4 287 24 320.3 24 356.4c0 130.7 150.7 201.4 251.4 122.5l-39.7-39.7c-16 10.9-35.3 17.3-56.1 17.3z\"/></svg>",
                    "glyph": null,
                    "glyphName": null
                }
            }
        ]
    }
}
```

Which fields have values depends on the icon’s `type`. An SVG icon returns `url`, and `inline` when it comes from a local SVG folder. A remote SVG set returns `null` for `inline`, so use `url` for those icons. A web font icon returns `glyph` and `glyphName`, and a CSS or sprite icon returns its class or sprite name in `value`. Check `isEmpty` before rendering, as the field returns an object even when no icon is selected. [Interfaces & Types](docs:graphql/interfaces-types) describes every field.

## Query a Web Font Icon

For a field using a web font icon set, request the glyph fields to render the character in your own markup.

```graphql
{
  entries(section: "blog", limit: 1) {
    ... on blog_blog_Entry {
      iconPickerField {
        type
        iconSet
        glyph
        glyphName
      }
    }
  }
}
```

```json
{
  "data": {
    "entries": [
      {
        "iconPickerField": {
          "type": "glyph",
          "iconSet": "fontawesome-webfont",
          "glyph": "&#xf081;",
          "glyphName": "twitter-square"
        }
      }
    ]
  }
}
```

`glyph` is a hexadecimal HTML character reference. Output it with your frontend’s raw HTML method inside an element that uses the icon font.

## Share a Fragment Across Fields

Because each `{fieldHandle}_Icon` type implements `IconInterface`, you can define a single fragment and spread it on any Icon Picker field:

```graphql
fragment Icon on IconInterface {
    value
    iconSet
    label
    keywords
    type
    isEmpty
    url
    inline
    glyph
    glyphName
}

fragment Example on blog_blog_Entry {
    iconPickerField {
        ...Icon
    }
}
```
