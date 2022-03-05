<?php
namespace verbb\iconpicker\models;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\helpers\IconPickerHelper;

use craft\base\Model;
use craft\helpers\FileHelper;

class IconModel extends Model
{
    // Properties
    // =========================================================================

    public ?string $icon = null;
    public ?string $sprite = null;
    public ?string $glyphId = null;
    public ?string $glyphName = null;
    public ?string $iconSet = null;
    public ?string $type = null;
    public ?string $css = null;
    public ?string $width = null;
    public ?string $height = null;


    // Public Methods
    // =========================================================================

    public function __toString(): string
    {
        if ($this->sprite) {
            return (string) $this->sprite;
        }

        if ($this->glyphId) {
            return (string) $this->glyph;
        }

        if ($this->css) {
            return (string) $this->css;
        }

        if ($this->icon) {
            return $this->getUrl();
        }

        return '';
    }

    public function getLength(): int|string
    {
        // TODO: deprecate this at the next major breakpoint
        if ((string)$this === '') {
            return 0;
        }

        return (string)$this;
    }

    public function getIsEmpty(): bool
    {
        // TODO: deprecate this at the next major breakpoint
        return !$this->getLength();
    }

    public function getDimensions($height = null): array
    {
        return IconPicker::$plugin->getService()->getDimensions($this->icon, $height);
    }

    public function getUrl(): string
    {
        return IconPickerHelper::getIconUrl($this->icon);
    }

    public function getPath(): string
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->getIconSetsPath();

        $path = FileHelper::normalizePath($iconSetsPath . DIRECTORY_SEPARATOR . $this->icon);

        if (!file_exists($path)) {
            return '';
        }

        return $path;
    }

    public function getInline(): string|\Twig\Markup
    {
        return IconPicker::$plugin->getService()->inline($this->icon);
    }

    public function getIconName(): string
    {
        return ($this->icon) ? pathinfo($this->icon, PATHINFO_FILENAME) : '';
    }

    public function getHasIcon(): bool
    {
        return (bool)$this->icon;
    }

    public function getRemoteSet()
    {
        return IconPicker::$plugin->getIconSources()->getRegisteredIconSourceByHandle($this->iconSet);
    }

    public function getSerializedValues(): array
    {
        // Return content saved in icon caches, and used for the field. Basically a cut-down version of the model
        // with a few extra properties handy
        if ($this->type === 'sprite') {
            return [
                'type' => 'sprite',
                'name' => $this->iconSet,
                'value' => 'sprite:' . $this->iconSet . ':' . $this->sprite,
                'url' => $this->sprite,
                'label' => $this->sprite,
            ];
        }

        if ($this->type === 'glyph') {
            return [
                'type' => 'glyph',
                'name' => $this->iconSet,
                'value' => 'glyph:' . $this->iconSet . ':' . $this->glyphId . ':' . $this->glyphName,
                'url' => $this->getGlyph(),
                'label' => $this->glyphName,
            ];
        }

        if ($this->type === 'css') {
            if ($remoteSet = $this->getRemoteSet()) {
                return [
                    'type' => 'css',
                    'name' => $remoteSet['fontName'] ?? '',
                    'value' => 'css:' . $this->iconSet . ':' . $this->css,
                    'url' => '',
                    'classes' => $remoteSet['classes'] . $this->css,
                    'label' => $this->css,
                ];
            }
        }

        return [
            'type' => 'svg',
            'value' => $this->icon,
            'url' => $this->url,
            'label' => $this->iconName,
        ];
    }

    public function getGlyph($format = 'charHex'): string
    {
        if ($format === 'decimal') {
            return $this->glyphId;
        }

        if ($format === 'hex') {
            return dechex($this->glyphId);
        }

        if ($format === 'char') {
            return '&#' . $this->glyphId;
        }

        return '&#x' . dechex($this->glyphId);
    }

}
