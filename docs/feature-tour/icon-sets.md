# Icon Sets
An Icon Set is the concept of a collection of icons. You can create and manage these in **Icon Picker** > **Settings** > **Icon Sets**, and are stored in project config. These are selected in your Icon Picker field for which collection of icons to actually show in the field, for users to pick from.

There are a few different types of Icon Sets you can create:

- SVG Folders
- SVG Sprites
- Web Fonts
- [Font Awesome 5/6](https://fontawesome.com/)
- [Feather Icons](https://feathericons.com/)
- [Ionicons](https://ionic.io/ionicons)
- [CSS.gg](https://css.gg/)
- [Material Symbols](https://fonts.google.com/icons)

## SVG Folders
Creating a **SVG Folder** Icon Set allows you to pick from a sub-folder (or the root folder) where a collection of `.svg` files sit. This is relative to your **Icons Path** plugin setting. Icon Picker will scan all `.svg` files in that folder, to be a pickable icon.

Read more about templating with [SVG Icons](docs:template-guides/rendering-icons#svg-folders)

:::tip
You can also use **SVG Sprites** instead of, or an addition to single SVGs.
:::

## SVG Sprites
Creating an **SVG Sprites** Icon Set allows you to pick a sprite file with definitions of SVG sprites. This is relative to your **Icons Path** plugin setting. These will need to be at the root level of your **Icons Path**, and named with the suffix `-sprites.svg` (for example `ui-icons-sprites.svg`). This will be so we can differentiate it between single SVG icons.

SVG Sprites are a slightly more advanced method of combining your icons into a single SVG file - but there are a number of benefits to this method. To read more about SVG Sprite, see [https://css-tricks.com/svg-sprites-use-better-icon-fonts](https://css-tricks.com/svg-sprites-use-better-icon-fonts).

Read more about templating with [SVG Sprites](docs:template-guides/rendering-icons#svg-sprites)

## Web Fonts
Creating a **Web Fonts** Icon Set allows you to pick a font file with definitions of font glyphs that represent icons. This is relative to your **Icons Path** plugin setting. These will need to be at the root level of your **Icons Path**.

Icon Picker supports `*.tff`, `*.woff`, `*.woff2` and `*.oft` files.

Read more about templating with [Icon Fonts](docs:template-guides/rendering-icons#icon-fonts)

## Font Awesome
Creating a **Font Awesome** Icon Set allows you to use the [Font Awesome](https://fontawesome.com/account) API or CDN as icons to pick. These don't require you to maintain the icon kits as part of your project. There are two options for how to use Font Awesome icons, and they will depend on your license

Read more about templating with [Font Awesome](docs:template-guides/rendering-icons#css-icons)

### Kits
With [kits](https://fontawesome.com/kits) you can create collections of icons (and even upload your own) and provide an easy means to use them across multiple sites. This will require a paid subscription to Font Awesome.

Adding the provided API key to the settings, you'll be able to select which kits to include in your Icon Set, to in turn be able to be picked from in the field. 

:::warning
You'll need to set your Font Awesome Kit **Technology** settings to use **Web Fonts** in order for Icon Picker to display them properly. **SVG** kits are not currently supported.
:::

This is also the only method to use **Font Awesome 6 Pro**.

### CDN
Using the Font Awesome CDN is another way to use these icons, particularly if you don't have a subscription to Font Awesome. Both the **Font Awesome 5 Free** and **Font Awesome 6 Free** versions are supported.

You can pick the version (5 or 6) you wish to use, along with the license (Free or Pro). You can also enable specific collections to be added, from the following:

- Solid
- Regular
- Light
- Duotone
- Brands

So, for example, you may only wish for your users to be able to pick from **Solid** or **Regular** icons, but none of the others.

Using **Font Awesome 5 Pro** is supported, but will require a Font Awesome subscription, and your domain name added to the allowed domains in your Font Awesome account. **Font Awesome 6 Pro** is not supported on the CDN by any method.

## Metadata
Sometimes, your icon pack of choice - be it collection of SVGs or a Web Font - might come with additional metadata used for descriptions of the icons. For example, a `heart` icon could likely fit under multiple keywords like `love`, `blood`, `medical`, etc. Unfortunately, it's difficult to embed this extra information in the filename of a SVG, SVG Spritesheet, or Web Font - which is where metadata comes in. Depending on your icons of choice, some might be available already, or you can create your own.

Simply put, metadata is a JSON file that's a key-value of the name of your icon and keyword.

```json
{
    "heart": ["love", "blood", "medical"]
}

// or

{
    "heart": "love blood medical"
}
```

Here, you define the keywords (either as a space-delimited string, or an array) with a reference to the individual icon. Icon Picker will pick up this metadata file, and pull in any keywords from it to be used when searching for an icon.

### Metadata Usage
Depending on what sort of icon set you're using will depend where you place, and what you name your metadata file. In all instances however, you must include the `-metadata.json` suffix.

#### Metadata with SVG Folders
You should place the `metadata.json` file alongside your icons. This would either be in the root of your icons folder, or in the folder of your icons. They should be alongside your SVGs.

#### Metadata with SVG Sprites
You should place the `-metadata.json` file alongside your SVG Sprites. This would be in the root of your icons folder. You must name the metadata file the same as your sprites file. For example, `ui-icons-sprites.svg` and `ui-icons-sprites-metadata.json`.

#### Metadata with Web Fonts
You should place the `-metadata.json` file alongside your Web Font. This would be in the root of your icons folder. You must name the metadata file the same as your web font file. For example, `icomoon.ttf` and `icomoon-metadata.json`.
