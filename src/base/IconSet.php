<?php
namespace verbb\iconpicker\base;

use verbb\iconpicker\IconPicker;
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

        if ($fromCache && $settings->enableCache) {
            if ($cachedData = Craft::$app->getCache()->get($cacheKey)) {
                $cachedData = $this->_unserializeFromCache($cachedData);

                $this->setAttributes($cachedData, false);

                return;
            }
        }

        // Populates the icons (and fonts/spritesheets) based on the icon set class.
        $this->fetchIcons();

        // Save the icon set to the cache, if using
        if ($settings->enableCache) {
            Craft::$app->getCache()->set($cacheKey, $this->_serializeToCache($this));
        }
    }

    public function fetchIcons(): void
    {
        return;
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






    // public function getSettingsHtml(): ?string
    // {
    //     return null;
    // }

    // public function getFieldSettingLabel(): ?string
    // {
    //     return static::displayName();
    // }

    // public function getIconSets(): array
    // {
    //     return [];
    // }

    // public function getIcons(IconSet $iconSet): void
    // {
    //     return;
    // }
}