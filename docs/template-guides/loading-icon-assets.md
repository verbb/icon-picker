# Loading Icon Assets

SVG Sprites and Web Fonts need a shared asset before a selected icon can render. Load that asset once in your site layout, then render field values in the page templates that extend it.

## Load an SVG Spritesheet

Place the spritesheet at the root of `iconSetsPath` and give it a `-sprites.svg` suffix. In your base layout, output it immediately after the opening `<body>` tag:

```twig
<body>
    {{ craft.iconPicker.spritesheet('project-sprites.svg') }}

    {% block content %}{% endblock %}
</body>
```

The helper returns the spritesheet's inline SVG markup. In an entry template, reference the selected symbol:

```twig
{% if entry.featureIcon %}
    <svg class="feature-icon" aria-hidden="true">
        <use href="#{{ entry.featureIcon.value }}"></use>
    </svg>
{% endif %}
```

If no symbols render, confirm that the filename passed to `spritesheet()` matches the configured set and that the selected value matches a symbol ID in the file.

## Load a Web Font

Use `fontUrl()` in an `@font-face` rule to resolve a font relative to `iconSetsPath`:

```twig
<style>
    @font-face {
        font-family: 'Project Icons';
        font-display: swap;
        src: url("{{ craft.iconPicker.fontUrl('project-icons.woff') }}") format('woff');
    }

    .project-icon {
        font-family: 'Project Icons';
    }
</style>
```

Then render the selected glyph in an element using that font:

```twig
{% if entry.featureIcon %}
    <span class="project-icon" aria-hidden="true">{{ entry.featureIcon.glyph | raw }}</span>
{% endif %}
```

Icon Picker can index `.ttf`, `.woff` and `.otf` files. It cannot index `.woff2`, although you may load a matching `.woff2` file on the front end after indexing a supported sibling file.
