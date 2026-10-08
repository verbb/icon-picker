# Icon Sets

An Icon Set tells Icon Picker where a collection of icons comes from and how those icons should be displayed. Create and manage sets under **Icon Picker → Settings → Icon Sets**, then choose which sets editors can use in each Icon Picker field. Icon Set settings are stored in project config so they can be deployed with the rest of your Craft configuration.

There are a few different types of Icon Sets you can create:

- SVG Folders
- SVG Sprites
- Web Fonts
- [Font Awesome 5/6](https://fontawesome.com/)
- [Feather Icons (Legacy)](https://feathericons.com/)
- [Ionicons (Legacy)](https://ionic.io/ionicons)
- [Ionicons](https://ionic.io/ionicons)
- [CSS.gg](https://css.gg/)
- [Material Symbols](https://fonts.google.com/icons)
- [Bootstrap Icons](https://icons.getbootstrap.com/)
- [Remix Icon](https://remixicon.com/)
- [Material Design Icons](https://pictogrammers.com/library/mdi/)
- [Phosphor](https://phosphoricons.com/)
- [Lucide](https://lucide.dev/)
- [Tabler Icons](https://tabler.io/icons)
- [Heroicons](https://heroicons.com/)
- [Octicons](https://primer.style/octicons/)

## SVG Folders
Use an **SVG Folder** set when your project keeps individual `.svg` files in one directory. Choose the root or a subfolder relative to the **Icons Path** plugin setting. Icon Picker scans that location and makes each SVG available in the field.

Enable **Search Subfolders** to include `.svg` files nested under the selected folder (on by default). Turn it off to limit the catalogue to files directly in that folder.

Enable **Group by Subfolder** to separate icons with folder headings in the picker. For example, `social/brands` appears as **Social / Brands**, relative to the folder selected for the set. Icons directly inside the selected folder appear first, without a subfolder heading. Grouping is off by default and does not change which files are included.

When you are ready to display a selection, follow [Rendering SVG Icons](docs:template-guides/rendering-icons#svg-icons).

:::tip
You can use **SVG Sprites** instead of, or alongside, individual SVG files.
:::

## SVG Sprites
Use an **SVG Sprites** set when one SVG file contains several named symbols. Put the file at the root of **Icons Path** and give it a `-sprites.svg` suffix, such as `ui-icons-sprites.svg`. The suffix distinguishes a spritesheet from an individual icon.

Sprites let a page load the shared SVG definitions once and refer to them by ID wherever an icon appears. [This introduction to SVG sprites](https://css-tricks.com/svg-sprites-use-better-icon-fonts) explains the underlying technique.

Before rendering a sprite selection, [load the spritesheet and reference its icon ID](docs:template-guides/rendering-icons#svg-sprites).

## Web Fonts
Use a **Web Fonts** set for a font whose glyphs represent icons. Put the font file at the root of **Icons Path** so Icon Picker can index it.

Icon Picker can index glyphs from `*.ttf`, `*.woff`, and `*.otf` files. **`.woff2` is not supported for indexing** (the glyph parser cannot read WOFF2). Prefer a `.ttf` or `.woff` sibling from your kit; you can still load `.woff2` yourself on the front end if needed.

To display a selected glyph, follow [Rendering Icon Fonts](docs:template-guides/rendering-icons#icon-fonts).

## Font Awesome
A **Font Awesome** set loads icons through a [Font Awesome](https://fontawesome.com/account) kit or its CDN, so you do not need to keep the icon files in your project. Choose the method that matches your Font Awesome licence.

After configuring the set, [load the matching stylesheet and render its CSS classes](docs:template-guides/rendering-icons#remote-css-icons).

### Kits
[Font Awesome kits](https://fontawesome.com/kits) can contain standard and custom icons for use across multiple sites. Kits require an appropriate paid Font Awesome plan.

Enter the API token in the Icon Set settings, then choose which kit the field should use.

:::warning
Set your Font Awesome Kit **Technology** to **Web Fonts with CSS**, not **SVG + JS**.

**SVG + JS** kits load a script that runs across the **entire Control Panel page**, not only the Icon Picker field. Font Awesome will replace many CSS-based icons (for example `<i class="fa fa-bold">`–style markup) with inline SVG. Other plugins that rely on those elements—such as markdown editors whose toolbar uses Font Awesome classes—can end up with broken layout or **non-clickable** toolbar buttons after the kit loads.

**Web Fonts** keeps icons as normal CSS glyphs, so Icon Picker and other Control Panel fields can coexist without that document-wide replacement behaviour.
:::

This is also the only method to use **Font Awesome 6 Pro**.

### CDN
The CDN option supports the free Font Awesome 5 and Font Awesome 6 collections without requiring a kit.

You can pick the version (5 or 6) you wish to use, along with the licence (Free or Pro). You can also enable specific collections to be added, from the following:

- Solid
- Regular
- Light
- Duotone
- Brands

For example, choose only **Solid** and **Regular** when editors should not use the other styles.

Using **Font Awesome 5 Pro** requires a Font Awesome subscription and your domain in the account's allowed domains. **Font Awesome 6 Pro** is not supported through the CDN option.

## Metadata
Metadata adds search terms that are not present in an icon's filename or glyph name. For example, an icon named `heart` might also need to match searches for `love`, `blood`, or `medical`. Add those terms in a JSON file beside the icon assets.

```json
{
    "heart": ["love", "blood", "medical"]
}
```

Each key identifies an icon and its value supplies either an array of keywords, as above, or a space-delimited string. Icon Picker includes those keywords when editors search the field.

## Metadata Usage
The required filename and location depend on the type of Icon Set.

### Metadata with SVG Folders
Place a `metadata.json` file in the same folder as your SVGs (the folder you selected for the Icon Set). Keys are icon filenames without the `.svg` extension.

### Metadata with SVG Sprites
Place the `-metadata.json` file beside the spritesheet at the root of your icons folder. Match the spritesheet name, such as `ui-icons-sprites.svg` and `ui-icons-sprites-metadata.json`.

### Metadata with Web Fonts
Place the `-metadata.json` file beside the Web Font at the root of your icons folder. Match the font name, such as `icomoon.ttf` and `icomoon-metadata.json`.

## Variants and Package Versions

Many remote sets expose **variants** (for example Outline and Solid on Heroicons, or weight styles on Phosphor). Leave **All** selected, or narrow the catalogue to the styles editors should pick from.

Built-in sets that load icons from npm or a CDN pin a **Package version** in the plugin. The icon-name catalogue is generated for that package release. Leave the Icon Set's **Package version** blank to use the compatible plugin default.

:::warning
If you override the package version, CDN URLs may point at a different release than the bundled catalogue. Some icon names may be missing or broken until the configured package and catalogue match.
:::
