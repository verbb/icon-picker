# Rendering Icons

Icon Picker supports SVGs, SVG Spritesheets, and Icon Fonts for picking icons with. As each method requires the need for a different way of outputting, you'll want to consult the guide below on how best to handle outputting your icon in your templates.

## SVG Icons
You have two main options when rendering a single SVG icon:

### URL

A URL can be generated for a direct link to the SVG file. This is most commonly used when using SVGs are an `<img>` `src` attribute.

```twig
<img src="{{ entry.iconPickerField }}" width="20" height="20">

// Or
<img src="{{ entry.iconPickerField.url }}" width="20" height="20">

// Renders
<img src="http://mysite.test/assets/icons/hamburger.svg" width="20" height="20">
```

### Inline
Rendering an SVG inline, will directly render the contents of the SVG file on the page.

```twig
{{ entry.iconPickerField.inline }}

// Renders
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 496 512"><path d="M248 8C111 8 0 119 0 256s111 248 248 248 248-111 248-248S385 8 248 8zm0 448c-110.3 0-200-89.7-200-200S137.7 56 248 56s200 89.7 200 200-89.7 200-200 200zm0-144c-33.6 0-65.2 14.8-86.8 40.6-8.5 10.2-7.1 25.3 3.1 33.8s25.3 7.2 33.8-3c24.8-29.7 75-29.7 99.8 0 8.1 9.7 23.2 11.9 33.8 3 10.2-8.5 11.5-23.6 3.1-33.8-21.6-25.8-53.2-40.6-86.8-40.6zm-48-72c10.3 0 19.9-6.7 23-17.1 3.8-12.7-3.4-26.1-16.1-29.9l-80-24c-12.8-3.9-26.1 3.4-29.9 16.1-3.8 12.7 3.4 26.1 16.1 29.9l28.2 8.5c-3.1 4.9-5.3 10.4-5.3 16.6 0 17.7 14.3 32 32 32s32-14.4 32-32.1zm199-54.9c-3.8-12.7-17.1-19.9-29.9-16.1l-80 24c-12.7 3.8-19.9 17.2-16.1 29.9 3.1 10.4 12.7 17.1 23 17.1 0 17.7 14.3 32 32 32s32-14.3 32-32c0-6.2-2.2-11.7-5.3-16.6l28.2-8.5c12.7-3.7 19.9-17.1 16.1-29.8z"></path></svg>
```

### SVG function
You can use Craft's own `svg()` Twig function by using the path. [Read more](https://docs.craftcms.com/v3/dev/functions.html#svg).

```twig
{{ svg(entry.iconPickerField.path, class='lemon-icon') }}
```

Note that this cannot be used for SVG Sprites, only singular SVG icons.

## SVG Sprites

You can also use an SVG Spritesheet for your icons. The general concept of a sprite sheet is that it saves adding duplicate content to the page body. For example, you might have 50 'check' icons on a page, and using the above inline code, 50 lots of SVG code would need to be output.

Instead, with SVG Sprites, you output your SVG Spritesheet only once, and then every instance on the page you want to use the icon, you reference it via id.

First, you'll want to output the spritesheet on your page. You can either do this yourself in your templates, or use the following to output it for you. Its advisable this is done at the top of your template, ideally just below the `<body>` tag.

```twig
{{ craft.iconPicker.spritesheet('path/regular.svg') }}
```

Then, every time you want to reference an icon, you use its id. Note that Icon Picker will automatically know whether this field is using a SVG sprite or not, so you don't have to specifically use `.sprite`.

```twig
<svg width="20" height="20"><use xlink:href="#{{ entry.iconPickerField }}"></use></svg>

// Or
<svg width="20" height="20"><use xlink:href="#{{ entry.iconPickerField.sprite }}"></use></svg>

// Renders
<svg width="20" height="20"><use xlink:href="#address-book"></use></svg>
```

## Icon Fonts
You can also use an icon font for your icon source. Icon Picker will extract the available icons in font file, for you to pick. These are refered to as 'glyphs'. From your field, you have two main options on how to display the icon, depending on what works for your project.

It's also largely up to you to implement the required CSS required for either of these approaches. An example might be for the Font Awesome 5 Free pack:

```css
@font-face {
    font-family: 'Font Awesome 5 Free';
    font-style: normal;
    font-weight: normal;
    font-display: auto;
    src: url("{{ craft.iconPicker.fontUrl('folder/fa-solid-900.ttf') }}");
}

.fab {
    font-family: 'Font Awesome 5 Free'; 
}

.fa-bomb:before {
    content: "\f1e2";
}
```

Note the use of the `{{ craft.iconPicker.fontUrl() }}` function, which retrieves the URL to the font file.

### Icon Glyph

You can output the actual glyph unicode character representation on the page, and then ensure you style the HTML node with the correct font.

```twig
<span class="fa">{{ entry.iconPickerField.glyph | raw }}</span>

// Renders
<span class="fa">&#xf1e2</span>
```

### Glyph Name
It's fairly common for font icon providers to have named classes for each icon, which you might rather use.

```twig
<span class="fa fa-{{ entry.iconPickerField.glyphName }}"></span>

// Renders
<span class="fa fa-bomb"></span>
```

## Remote Icon Sets

Remote icon sets are often CDN-based icon kits, hosted offsite. Icon Picker comes built in with [Font Awesome 5](https://fontawesome.com/), but you can also [register your own](docs:developers/icon-sources).

Because these kits are CSS-based, they act similar to the icon fonts above, but won't contain any glyph information. Instead, the name of the icon will be available in a `css` property.

Using the built-in Font Awesome remote kit:

```twig
<span class="fa fa-{{ entry.iconPickerField }}"></span>

// Or
<span class="fa fa-{{ entry.iconPickerField.css }}"></span>

// Renders
<span class="fa fa-air-freshener"></span>
```

