<?php
namespace verbb\iconpicker\models;

use verbb\iconpicker\IconPicker;

use Craft;
use craft\base\Model;
use craft\helpers\FileHelper;

class IconModel extends Model
{
    // Properties
    // =========================================================================

    public $icon;
    public $sprite;
    public $glyphId;
    public $glyphName;
    public $iconSet;
    public $type;
    public $css;
    public $width;
    public $height;


    // Public Methods
    // =========================================================================

    public function __toString()
    {
        if ($this->sprite) {
            return $this->sprite;
        }

        if ($this->glyphId) {
            return $this->glyph;
        }

        if ($this->css) {
            return $this->css;
        }

        return $this->getUrl();
    }

    public function getDimensions($height = null)
    {
        return IconPicker::$plugin->getService()->getDimensions($this->icon, $height);
    }

    public function getUrl()
    {
        $iconSetsUrl = IconPicker::$plugin->getSettings()->iconSetsUrl;

        $url = FileHelper::normalizePath($iconSetsUrl . DIRECTORY_SEPARATOR . $this->icon);
        $url = Craft::getAlias($url);

        return $url;
    }

    public function getInline()
    {
        return IconPicker::$plugin->getService()->inline($this->icon);
    }

    public function getIconName()
    {
        return ($this->icon) ? pathinfo($this->icon, PATHINFO_FILENAME) : '';
    }

    public function getSerializedValue()
    {
        if ($this->type === 'sprite') {
            return implode(':', [$this->type, $this->iconSet, $this->sprite]);
        } else if ($this->type === 'glyph') {
            return implode(':', [$this->type, $this->iconSet, $this->glyphId, $this->glyphName]);
        } else if ($this->type === 'css') {
            return implode(':', [$this->type, $this->iconSet, $this->css]);
        }

        return $this->icon;
    }

    public function getGlyph($format = 'charHex')
    {
        if ($format === 'decimal') {
            return $this->glyphId;
        } else if ($format === 'hex') {
            return dechex($this->glyphId);
        } else if ($format === 'char') {
            return '&#' . $this->glyphId;
        }

        return '&#x' . dechex($this->glyphId);
    }

}
