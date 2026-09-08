# Field

Icon Picker provides a searchable field for selecting icons from one or more Icon Sets.

![Icon Picker field with search and icon grid](/_screenshots/feature-tour/field.png)

Editors can search by name or keyword, browse the grid, and navigate with the keyboard (arrow keys, Enter to select, Escape to close). Restrict which Icon Sets are available per field in the field settings, and optionally set placeholder text when nothing is selected.

## Field settings

- **Available Icon Sets** — choose which sets appear in this field (or **All**). Manage sets under **Icon Picker → Settings → Icon Sets**.
- **Show Labels** — show each icon’s label under the glyph in the picker. Uses the larger icon size settings from plugin config when enabled.
- **Placeholder** — optional empty-state text when no icon is selected.

Once an icon is selected, [render it in your templates](docs:template-guides/rendering-icons).

## Feed Me

Icon Picker fields are available in [Feed Me](https://plugins.craftcms.com/feed-me) imports. Map the feed value to the same stored icon value the field expects (typically the icon name / CSS class / path string for that set).
