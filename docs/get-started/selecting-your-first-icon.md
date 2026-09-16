# Selecting Your First Icon

Start with an icon collection your project can use. Create an [Icon Set](docs:feature-tour/icon-sets) under **Icon Picker → Settings → Icon Sets**. For a first test, use an SVG Folder containing a small set of SVG files. Its filesystem location is relative to **Icons Path**; its public URL must also reach those files when rendering them as images.

Create an Icon Picker field called Feature Icon with the handle `featureIcon`. Select the icon set in the field settings, save and add it to an entry type's field layout. Open an entry, select an icon and save.

In that entry's Twig template, render this SVG icon as an image:

```twig
{% if not entry.featureIcon.isEmpty() %}
    <img src="{{ entry.featureIcon.url }}" width="24" height="24" alt="">
{% endif %}
```

This example treats the icon as decorative; put meaningful text beside it when it conveys an action. Open the page and confirm the file loads. If the editor can find it but the browser cannot, check the public URL as well as the filesystem path.

[Rendering Icons](docs:template-guides/rendering-icons) covers inline SVG, sprites and fonts. Those formats need different output, so choose the instructions matching your set.
