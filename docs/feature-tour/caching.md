# Caching

Icon Picker keeps a cached list of the icons in each set so editors can open the picker without scanning the collection on every request. Refresh that list after adding or changing icon files.

Saving an Icon Picker field rebuilds the cached lists for its enabled Icon Sets. SVG previews load as individual images, keeping large collections smaller to load and preventing styles within one SVG from affecting another icon.

## Lazy-Loading

Icons are lazy-loaded when you open the picker, rather than loading every glyph when the element edit screen loads. A small spinner appears while the catalogue loads; large sets may take a second or two.

## Adding New Icons

If you add files to an SVG folder (or change a spritesheet / font), they may not appear until the cache is refreshed. You can:

- Re-save any Icon Picker field that uses the icon set.
- Go to **Utilities → Clear Caches** and tick **Icon Picker cache**.
- Go to **Utilities → Icon Picker** and use **Re-generate all icon set caches**.

In Craft’s dev mode, Icon Picker also checks the modification time of the root `iconSetsPath` folder. Adding or removing an item at that root triggers cache regeneration on a subsequent request. Changes to file contents or nested folders require one of the methods above.

## Troubleshooting

| Symptom | What to try |
|---|---|
| New SVGs missing after upload | Regenerate Icon Picker caches (Utilities), or re-save the field. |
| Remote CDN icons look wrong after changing package version | Align **Package version** with the bundled catalogue, or leave blank for the plugin default. |
| Stale labels / keywords | Clear **Icon Picker cache**, then regenerate set caches. |
| Very large SVG folders feel slow | Prefer sprites or a remote set for huge libraries; keep **Search Subfolders** scoped if you only need one depth. |
