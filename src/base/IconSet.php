<?php
namespace verbb\iconpicker\base;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\helpers\IconPickerHelper;
use verbb\iconpicker\models\Icon;

use Craft;
use craft\base\SavableComponent;
use craft\helpers\App;
use craft\helpers\Json;
use craft\helpers\UrlHelper;

abstract class IconSet extends SavableComponent implements IconSetInterface, \JsonSerializable
{
    // Properties
    // =========================================================================

    public ?string $name = null;
    public ?string $handle = null;
    public ?string $type = null;
    public ?int $sortOrder = null;
    public ?string $uid = null;

    public string $cssAttribute = 'class';
    public array $icons = [];
    public array $fonts = [];
    public array $spriteSheets = [];
    public array $scripts = [];

    private bool|string $_enabled = true;


    // Public Methods
    // =========================================================================

    public function jsonSerialize(): mixed
    {
        // Return a minimal array for the front-end Vue field
        return array_filter([
            'icons' => $this->icons,
            'fonts' => $this->fonts,
            'spriteSheets' => $this->spriteSheets,
            'scripts' => $this->scripts,
            'cssAttribute' => $this->cssAttribute,
        ]);
    }

    public function getName(): string
    {
        return $this->name ?? '';
    }

    public function getHandle(): string
    {
        return $this->handle ?? '';
    }

    public function getEnabled(bool $parse = true): bool|string
    {
        if ($parse) {
            return App::parseBooleanEnv($this->_enabled) ?? true;
        }

        return $this->_enabled;
    }

    public function setEnabled(bool|string $name): void
    {
        $this->_enabled = $name;
    }

    public function getSettingsHtml(): ?string
    {
        return null;
    }

    public function populateIcons(bool $fromCache = true): void
    {
        $settings = IconPicker::$plugin->getSettings();
        // v2: SVG catalog no longer embeds full markup in the cache blob. Bump the
        // key so fat v1 entries are ignored until natural eviction / Clear Caches.
        $cacheKey = 'icon-picker:v2:' . $this->handle;

        // Check to see if loaded in-memory already, rather than loading from the cache
        if ($preloadedData = IconPicker::$plugin->getIconSets()->getPreloadedIconSet($cacheKey)) {
            $this->setAttributes($preloadedData->getAttributes(), false);

            return;
        }

        if ($fromCache && $settings->enableCache) {
            if ($cachedData = Craft::$app->getCache()->get($cacheKey)) {
                $cachedData = $this->_unserializeFromCache($cachedData);

                $this->setAttributes($cachedData, false);

                // Save the data as preloaded, in case we need it again for the same request.
                // Faster than the cache for large icon sets
                IconPicker::$plugin->getIconSets()->setPreloadedIconSet($cacheKey, $this);

                return;
            }
        }

        // Populates the icons (and fonts/spritesheets) based on the icon set class.
        $this->fetchIcons();

        // Save the icon set to the cache, if using
        if ($settings->enableCache) {
            Craft::$app->getCache()->set($cacheKey, $this->_serializeToCache($this));
        }

        // Save the data as preloaded, in case we need it again for the same request.
        // Faster than the cache for large icon sets
        IconPicker::$plugin->getIconSets()->setPreloadedIconSet($cacheKey, $this);
    }

    public function fetchIcons(): void
    {
        return;
    }

    public function getMetadata(string $path, string $key): ?string
    {
        // Get or set the metadata from the cache
        $metadata = $this->_fetchMetadata($path);
        $itemMetadata = $metadata[$key] ?? [];

        if ($itemMetadata) {
            if (!is_array($itemMetadata)) {
                $itemMetadata = [$itemMetadata];
            }

            return implode(' ', $itemMetadata);
        }

        return null;
    }

    public function getSpriteSheets(bool $includeSprites = false): array
    {
        // CP only needs name + url to fetch the sheet once. The parsed `sprites`
        // map is large and unused by the field UI — keep it opt-in for diagnostics.
        $spriteSheets = [];

        foreach ($this->spriteSheets as $spriteSheet => $sprites) {
            $row = [
                'url' => IconPickerHelper::getUrlForPath($spriteSheet),
                'name' => pathinfo($spriteSheet, PATHINFO_FILENAME),
            ];

            if ($includeSprites) {
                $row['sprites'] = $sprites;
            }

            $spriteSheets[] = $row;
        }

        return $spriteSheets;
    }

    /**
     * Load fonts / spritesheets / scripts for a field without hydrating the icon catalog.
     * Used by `resources-for-field` so a saved glyph/sprite/css value can paint its chip
     * without decoding thousands of Icon models.
     */
    public function populateResources(bool $fromCache = true): void
    {
        $settings = IconPicker::$plugin->getSettings();
        $cacheKey = 'icon-picker:v2:' . $this->handle;

        if ($preloadedData = IconPicker::$plugin->getIconSets()->getPreloadedIconSet($cacheKey)) {
            $this->fonts = $preloadedData->fonts;
            $this->spriteSheets = $preloadedData->spriteSheets;
            $this->scripts = $preloadedData->scripts;
            $this->cssAttribute = $preloadedData->cssAttribute;

            return;
        }

        if ($fromCache && $settings->enableCache) {
            if ($cachedData = Craft::$app->getCache()->get($cacheKey)) {
                $data = Json::decode($cachedData);

                $this->fonts = $data['fonts'] ?? [];
                $this->spriteSheets = $data['spriteSheets'] ?? [];
                $this->scripts = $data['scripts'] ?? [];
                $this->cssAttribute = $data['cssAttribute'] ?? 'class';
                // Intentionally leave `icons` empty — callers only need resources.

                return;
            }
        }

        // Cold cache: full populate (also writes the slim v2 blob).
        $this->populateIcons($fromCache);
    }

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('icon-picker/settings/icon-sets/edit/' . $this->id);
    }

    public function getIconDiagnosticsSummary(Icon $icon): array
    {
        return $icon->toArray();
    }

    public function getDiagnosticsSummary(): array
    {
        return [];
    }


    // Private Methods
    // =========================================================================

    private function _serializeToCache(IconSet $iconSet): string
    {
        $icons = [];
        $data = $iconSet->jsonSerialize();

        foreach ($iconSet->icons as $key => $icon) {
            $data['icons'][$key] = $icon->serializeValueForCache();
        }

        return Json::encode($data);
    }

    private function _unserializeFromCache(string $data): array
    {
        $data = Json::decode($data);
        $icons = $data['icons'] ?? [];

        foreach ($icons as $key => $icon) {
            // Drop any legacy SVG markup that may still sit in an old blob shape.
            if (($icon['type'] ?? null) === Icon::TYPE_SVG) {
                unset($icon['displayValue']);
            }

            $data['icons'][$key] = new Icon($icon);
        }

        return $data;
    }

    private function _fetchMetadata(string $path): ?array
    {
        $cacheKey = 'icon-picker-metadata: ' . md5($path);

        return Craft::$app->getCache()->getOrSet($cacheKey, function() use ($path) {
            $filename = basename($path);
            $folderPath = str_replace($filename, '', $path);

            $metadataFiles = IconPickerHelper::getFiles($folderPath, [
                'only' => [$filename],
                'recursive' => false,
            ]);

            $metadataFile = $metadataFiles[0] ?? null;

            if ($metadataFile) {
                return Json::decode(file_get_contents($metadataFile));
            }
        });
    }
}