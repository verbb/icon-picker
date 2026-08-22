<?php
namespace verbb\iconpicker\models;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\helpers\IconPickerHelper;

use Craft;
use craft\base\Model;
use craft\helpers\FileHelper;
use craft\helpers\Template;

use Twig\Markup;

class Icon extends Model implements \JsonSerializable, \Countable
{
    // Constants
    // =========================================================================

    public const TYPE_SVG = 'svg';
    public const TYPE_SPRITE = 'sprite';
    public const TYPE_GLYPH = 'glyph';
    public const TYPE_CSS = 'css';


    // Properties
    // =========================================================================

    public ?string $value = null;
    public ?string $iconSet = null;
    public ?string $iconSetHandle = null;
    public ?string $type = null;
    public ?string $label = null;
    public ?string $keywords = null;

    private ?string $_displayValue = null;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Config normalization
        $attributes = ['icon', 'glyphId', 'glyphName', 'css', 'sprite', 'width', 'height'];

        foreach ($attributes as $attribute) {
            if (array_key_exists($attribute, $config)) {
                unset($config[$attribute]);
            }
        }

        parent::__construct($config);
    }

    public function __toString(): string
    {
        if ($this->type === self::TYPE_GLYPH) {
            return (string)$this->getGlyph();
        }

        if ($this->type === self::TYPE_SVG) {
            return (string)$this->getUrl();
        }

        return (string)$this->value;
    }

    public function init(): void
    {
        parent::init();

        // Setup defaults
        $this->label = $this->getLabel();
        $this->keywords = $this->getKeywords();
    }

    public function serializeValueForDb(): ?array
    {
        // For when saving the value from the field into the content table for an element
        return $this->toArray();
    }

    public function serializeValueForCache(): ?array
    {
        // Catalog cache is an index only. SVG markup used to be embedded as
        // `displayValue` to avoid disk IO, but that blew cache/AJAX payloads to
        // multi-MB for large folders (Carbon ~2.6k icons → ~3.6MB). CP paints
        // SVG cells via `url` + <img>; Twig/thumbs still call getInline() once.
        $array = $this->toArray();

        // Non-SVG display strings are tiny (glyph entity, sprite id, CSS class)
        // and still required to paint without extra work — keep those in cache.
        if ($this->type !== self::TYPE_SVG) {
            $array['displayValue'] = $this->getDisplayValue();
        }

        return $array;
    }

    public function jsonSerialize(): mixed
    {
        // CP field catalog / selected-value JSON (not a public API).
        $array = $this->toArray();
        $array['label'] = $this->getLabel();
        $array['keywords'] = $this->getKeywords();

        if ($this->type === self::TYPE_SVG) {
            // Catalog + chip paint from URL (isolated <img>), not inlined markup.
            $array['url'] = $this->getUrl();
        } else {
            $array['displayValue'] = $this->getDisplayValue();
        }

        // Stable id for lit-virtualizer reuse (was a random suffix every response).
        $array['id'] = implode(':', array_filter([
            $this->type,
            $this->iconSetHandle,
            $this->iconSet,
            $this->value,
        ])) ?: (string)random_int(1, PHP_INT_MAX);

        return $array;
    }

    public function count(): mixed
    {
        return mb_strlen((string)$this, Craft::$app->charset);
    }

    public function isEmpty(): bool
    {
        return !(bool)$this->count();
    }

    public function getLabel(): ?string
    {
        if ($this->label) {
            return $this->label;
        }

        if ($this->type === self::TYPE_CSS) {
            return $this->value;
        }

        if ($this->type === self::TYPE_GLYPH) {
            return $this->getGlyphName();
        }

        if ($this->type === self::TYPE_SPRITE) {
            return $this->value;
        }

        if ($this->value && $this->type === self::TYPE_SVG) {
            return pathinfo($this->value, PATHINFO_FILENAME);
        }

        return null;
    }

    public function getKeywords(): ?string
    {
        if ($this->keywords) {
            return $this->keywords;
        }

        return $this->getLabel();
    }

    public function setDisplayValue(string $value): void
    {
        $this->_displayValue = $value;
    }

    public function getDisplayValue(): ?string
    {
        // Use the in-memory cache if available
        if ($this->_displayValue) {
            return $this->_displayValue;
        }

        // An inline SVG is used for the display value, not the URL
        if ($this->type === self::TYPE_SVG) {
            return $this->getInline();
        }

        return (string)$this;
    }

    public function getUrl(): ?string
    {
        if ($this->type !== self::TYPE_SVG) {
            return null;
        }

        if ($this->_isAbsoluteUrl($this->value)) {
            return $this->value;
        }

        if ($this->iconSetHandle) {
            $iconSet = IconPicker::$plugin->getIconSets()->getIconSetByHandle($this->iconSetHandle);

            if ($iconSet instanceof \verbb\iconpicker\base\RemoteSvgIconSet) {
                return $iconSet->resolveSvgUrl($this);
            }
        }

        return IconPickerHelper::getIconUrl($this->value);
    }

    public function getPath(): string
    {
        if ($this->type === self::TYPE_SVG) {
            $settings = IconPicker::$plugin->getSettings();
            $iconSetsPath = $settings->getIconSetsPath();

            $path = FileHelper::normalizePath($iconSetsPath . DIRECTORY_SEPARATOR . $this->value);

            if (!file_exists($path)) {
                return '';
            }

            return $path;
        }

        return '';
    }

    public function getInline(): ?Markup
    {
        if ($this->type === self::TYPE_SVG) {
            // Saved in the cache as the inline SVG to save disk IO
            if ($this->_displayValue) {
                return Template::raw($this->_displayValue);
            }

            if ($path = $this->getPath()) {
                return Template::raw(@file_get_contents($path));
            }

            $url = $this->getUrl();

            if ($url && $this->_isAbsoluteUrl($url)) {
                $contents = IconPickerHelper::getFileContents($url);

                if ($contents) {
                    return Template::raw($contents);
                }
            }
        }

        return null;
    }

    public function getGlyph($format = 'charHex'): ?string
    {
        if ($this->type === self::TYPE_GLYPH) {
            $glyphName = (explode(':', $this->value)[0]) ?? null;
            $glyphId = (explode(':', $this->value)[1]) ?? null;

            if ($format === 'decimal') {
                return $glyphId;
            }

            if ($format === 'hex') {
                return dechex($glyphId);
            }

            if ($format === 'char') {
                return '&#' . $glyphId;
            }

            return '&#x' . dechex($glyphId);
        }

        return null;
    }

    public function getGlyphName(): ?string
    {
        if ($this->type === self::TYPE_GLYPH) {
            return (explode(':', $this->value)[0]) ?? null;
        }

        return null;
    }

    private function _isAbsoluteUrl(?string $value): bool
    {
        return $value !== null && (str_starts_with($value, 'http://') || str_starts_with($value, 'https://'));
    }
}
