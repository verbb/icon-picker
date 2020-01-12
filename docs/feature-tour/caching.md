# Caching

Icon Picker features a caching mechanism for all icon sets. This helps with particularly large icon sets, but also ensures that processes aren't blocked when trying to enumerate your icons from the file system.

Whenever you save an Icon Picker field, the cache of all enabled icon sets are built. Subsequent saves to this field rebuilds this cache.

### Lazy-loading

In addition, icons are lazy-loaded for additional performance, rather than loading all icons when you load an element page. This provides signficant performance improvements. When opening the dropdown to pick an icon, a small loading spinner will appear to the right of the dropdown. Depending on the size of your icon sets, this may take a second or two.

### Adding new icons

If you add new icons to your folders, you'll likely notice that they won't show in the Icon Picker field. This is because Icon Picker doesn't know about these new icons, and is instead using the cached icons. You'll either need to:

- Re-save any Icon Picker fields that use this icon set.
- Go to Utilities > Clear Caches and tick `Icon Picker cache`.
- Go to Utilities > Icon Picker and hit `Re-generate all icon set caches` button.

However, Icon Picker is smart enough to know when your `iconSetsPath` folder has changed. Caches will re-generate whenever a folder or file is added/deleted/updated. The caveat here is that only changes to the root of this folder will take place. Changes to nested folders won't be watched, and you'll need to re-generate the cache via one of the above methods.