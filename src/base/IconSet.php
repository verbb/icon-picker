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

    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('icon-picker/settings/icon-sets/edit/' . $this->id);
    }

    public function populateIcons(bool $fromCache = true): void
    {
        $settings = IconPicker::$plugin->getSettings();
        $cacheKey = 'icon-picker:' . $this->handle;

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

    public function getSpriteSheets(): array
    {
        $spriteSheets = [];

        foreach ($this->spriteSheets as $spriteSheet => $sprites) {
            $spriteSheets[] = [
                'url' => IconPickerHelper::getUrlForPath($spriteSheet),
                'name' => pathinfo($spriteSheet, PATHINFO_FILENAME),
                'sprites' => $sprites,
            ];
        }

        return $spriteSheets;
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