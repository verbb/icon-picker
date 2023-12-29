<?php
namespace verbb\iconpicker\models;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\helpers\IconPickerHelper;

use Craft;
use craft\base\Model;
use craft\helpers\ArrayHelper;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
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
        // For when saving the field and building the icon cache
        $array = $this->toArray();

        // Save the `displayValue` to the cache for less disk IO (for SVGs)
        $array['displayValue'] = $this->getDisplayValue();

        return $array;
    }

    public function jsonSerialize(): mixed
    {
        // For when converting this model into JSON for the Vue picker to use in the CP
        // (either a selected value or the icons to pick from)
        $array = $this->toArray();
        $array['label'] = $this->getLabel();
        $array['keywords'] = $this->getKeywords();
        $array['displayValue'] = $this->getDisplayValue();

        // ID for vue-virtual-scroller
        $array['id'] = $array['label'] ? StringHelper::appendRandomString($array['label'], 5) : rand();

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
        if ($this->type === self::TYPE_SVG) {
            return IconPickerHelper::getIconUrl($this->value);
        }

        return null;
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
}
