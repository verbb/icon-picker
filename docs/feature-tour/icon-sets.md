# Icon Sets
An Icon Set is the concept of a collection of icons. You can create and manage these in **Icon Picker** > **Settings** > **Icon Sets**, and are stored in project config. These are selected in your Icon Picker field for which collection of icons to actually show in the field, for users to pick from.

There are a few different types of Icon Sets you can create:

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

