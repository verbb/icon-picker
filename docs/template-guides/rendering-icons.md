# Rendering Icons

An Icon Picker field returns an [Icon object](docs:developers/icon). The markup you use depends on the selected Icon Set, so first confirm whether the field uses individual SVGs, a spritesheet, a Web Font or a remote CSS collection. The examples below assume an entry field with the handle `featureIcon`.

Always check that the field has a value before rendering it:

```twig
{% if entry.featureIcon %}
    {# Render the icon using the appropriate example below. #}
{% endif %}
```

## SVG Icons

### Render an Image

Use the icon URL as an image source when you do not need to style paths inside the SVG:

```twig
{% if entry.featureIcon %}
    <img src="{{ entry.featureIcon.url }}" width="20" height="20" alt="">
{% endif %}
```

An empty `alt` value marks a decorative icon. If the icon conveys information that is not present in nearby text, provide a concise text alternative instead.

### Render Inline SVG

Use `inline` when your stylesheet needs to target elements inside the SVG:

```twig
{% if entry.featureIcon %}
    <span class="feature-icon" aria-hidden="true">
        {{ entry.featureIcon.inline }}
    </span>
{% endif %}
```

Only inline SVGs from sources you trust. Inline output places the SVG markup directly in the page, and remote SVG sets may require a server-side request before they are cached.

For a local SVG Folder set, you can also use Craft's [`svg()` Twig function](https://craftcms.com/docs/5.x/reference/twig/functions.html#svg) with the file path:

```twig
{% if entry.featureIcon %}
    {{ svg(entry.featureIcon.path) | attr({ class: 'feature-icon', 'aria-hidden': 'true' }) }}
{% endif %}
```

The `path` property and Craft's `svg()` function do not apply to remote SVG sets or SVG Sprites.

## SVG Sprites

Load the spritesheet once, ideally immediately after the opening `<body>` tag. The path is relative to `iconSetsPath`:

```twig
{{ craft.iconPicker.spritesheet('regular-sprites.svg') }}
```

Then reference the selected symbol ID wherever the icon should appear:

```twig
{% if entry.featureIcon %}
    <svg width="20" height="20" aria-hidden="true">
        <use href="#{{ entry.featureIcon.value }}"></use>
    </svg>
{% endif %}
```

For example, a stored value of `address-book` produces `<use href="#address-book">`. See [Loading Icon Assets](docs:template-guides/loading-icon-assets) for a complete layout example.

## Icon Fonts

Icon Picker indexes glyphs in a supported font file, but your front-end stylesheet must load that font and apply it to the rendered element. For example:

```twig
<style>
    @font-face {
        font-family: 'Project Icons';
        font-style: normal;
        font-weight: normal;
        font-display: swap;
        src: url("{{ craft.iconPicker.fontUrl('project-icons.ttf') }}");
    }

    .project-icon {
        font-family: 'Project Icons';
    }
</style>
```

Render the selected glyph and hide it from assistive technology when it is decorative:

```twig
{% if entry.featureIcon %}
    <span class="project-icon" aria-hidden="true">{{ entry.featureIcon.glyph | raw }}</span>
{% endif %}
```

If the font package supplies a class for each glyph, use `glyphName` instead:

```twig
{% if entry.featureIcon %}
    <span class="project-icon project-icon--{{ entry.featureIcon.glyphName }}" aria-hidden="true"></span>
{% endif %}
```

## Remote SVG Icons

Remote SVG sets such as Lucide, Heroicons, Tabler and Octicons store an icon name and resolve it to a CDN URL. Render the URL as an image, or use `inline` when you trust the provider and need inline markup:

```twig
{% if entry.featureIcon %}
    <img src="{{ entry.featureIcon.url }}" width="20" height="20" alt="">
{% endif %}
```

## Remote CSS Icons

Remote CSS sets such as Font Awesome, Bootstrap Icons, css.gg, Remix and Phosphor store CSS class names. Load the provider's matching stylesheet on your front end, then output the stored classes:

```twig
{% if entry.featureIcon %}
    <span class="{{ entry.featureIcon.value }}" aria-hidden="true"></span>
{% endif %}
```

Match the stylesheet and package version loaded by your site to the Icon Set configuration. A version mismatch can leave some saved icon names without a corresponding CSS rule.
