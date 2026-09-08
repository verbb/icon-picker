# Caching

Icon Picker caches icon set catalogs so large libraries do not block Control Panel requests while scanning the filesystem or remote name lists.

Whenever you save an Icon Picker field, the cache for its enabled icon sets is built. Subsequent saves rebuild those caches.

SVG catalogs use a slim **v2** cache key (`icon-picker:v2:*`). The picker paints SVG cells via `<img src>` (URL) instead of embedding full markup in the catalog JSON — smaller payloads and less CSS/`id` bleed between icons. Existing v1 caches are superseded on the next load or regenerate.

## Lazy-loading

Icons are lazy-loaded when you open the picker, rather than loading every glyph when the element edit screen loads. A small spinner appears while the catalog loads; large sets may take a second or two.

## Adding new icons

If you add files to an SVG folder (or change a spritesheet / font), they may not appear until the cache is refreshed. You can:

- Re-save any Icon Picker field that uses the icon set.
- Go to **Utilities → Clear Caches** and tick **Icon Picker cache**.
- Go to **Utilities → Icon Picker** and use **Re-generate all icon set caches**.

Icon Picker also watches the root of your `iconSetsPath` folder. Caches re-generate when a folder or file is added, deleted, or updated at that root. Nested folder changes alone are not watched — use one of the methods above.

## Troubleshooting

| Symptom | What to try |
|---|---|
| New SVGs missing after upload | Regenerate Icon Picker caches (Utilities), or re-save the field. |
| Remote CDN icons look wrong after changing package version | Align **Package version** with the bundled catalog, or leave blank for the plugin default. |
| Stale labels / keywords | Clear **Icon Picker cache**, then regenerate set caches. |
| Very large SVG folders feel slow | Prefer sprites or a remote set for huge libraries; keep **Search Subfolders** scoped if you only need one depth. |
