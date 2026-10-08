# Field

Use an Icon Picker field when editors need to choose an icon from a collection you provide. For example, a product feature can pair an icon with its title, while a social link can use a collection limited to brand icons.

![Icon Picker field with search and icon grid](../../screenshots/icon-picker-field.png)

## Field Settings

Create an [Icon Set](docs:feature-tour/icon-sets) before configuring the field. For a product feature, prepare a small collection of suitable symbols, then create a field under **Settings → Fields** called Feature Icon, with the handle `featureIcon`. The handle is the name you use to access the field in a template.

Choose that collection under **Available Icon Sets**. Limiting the field to relevant icons helps editors make a consistent choice; choose **All** when every enabled collection is appropriate. Manage the collections under **Icon Picker → Settings → Icon Sets**.

Choose **Small**, **Default**, or **Large** under **Icon Size** to suit the artwork. The grid adjusts its column count to the field’s width. Small keeps room for readable labels, while Large gives detailed icons more space. Default uses the sizes from [Configuration](docs:get-started/configuration#iconitemwrappersize); the other presets scale those sizes.

Use **Labels** to choose **Hidden** for an icon-only picker, **Tooltip** to show names on hover or keyboard focus, or **Below Icon** when names help editors distinguish similar icons. Hidden icons still have accessible names for screen readers. Existing fields with **Show Labels** enabled use Below Icon; other existing fields use Tooltip.

Use **Placeholder** for a prompt such as “Choose a product feature icon” when the field is empty.

Save the field and add it to the entry type's field layout.

## Select an Icon

Open an entry containing the field and open the picker. Search by icon name or keyword, or browse the grid. Use the arrow keys to move through the results, Enter to select an icon and Escape to close the picker.

When the field has multiple enabled Icon Sets, headings separate their icons using the configured set names. SVG Folder sets can also show subfolder headings when **Group by Subfolder** is enabled in the set settings. Searching keeps headings for matching icons and hides groups with no results.

Select an icon, save the entry and reopen it to confirm the selection is retained. Follow [Rendering Icons](docs:template-guides/rendering-icons) to display it beside the product feature on your site; the required markup depends on the Icon Set's format.

## Feed Me

Icon Picker fields are available in [Feed Me](https://plugins.craftcms.com/feed-me) imports. Choose the collections in the field's **Available Icon Sets** setting for the import to search, or choose **All** to search every enabled set. Map a feed value to the exact stored value of an icon in one of those sets, such as its path or CSS classes.

Test one feed item before importing the full feed. Open the resulting entry and check both its selection and rendered output. A value that does not match an icon in the selected sets produces an empty field.
