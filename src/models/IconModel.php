<?php
namespace verbb\iconpicker\models;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\helpers\IconPickerHelper;

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
        return IconPickerHelper::getIconUrl($this->icon);
    }

    public function getPath()
    {
        $settings = IconPicker::$plugin->getSettings();
        
        $path = FileHelper::normalizePath($settings->iconSetsPath . DIRECTORY_SEPARATOR . $this->icon);

        if (!file_exists($path)) {
            return '';
        }

        return $path;
    }

    public function getInline()
    {
        return IconPicker::$plugin->getService()->inline($this->icon);
    }

    public function getIconName()
    {
        return ($this->icon) ? pathinfo($this->icon, PATHINFO_FILENAME) : '';
    }

    public function getHasIcon()
    {
        return (bool)$this->icon;
    }

    public function getRemoteSet()
    {
        return IconPicker::$plugin->getIconSources()->getRegisteredIconSourceByHandle($this->iconSet);
    }

    public function getSerializedValues()
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
        } else if ($this->type === 'glyph') {
            return [
                'type' => 'glyph',
                'name' => $this->iconSet,
                'value' => 'glyph:' . $this->iconSet . ':' . $this->glyphId . ':' . $this->glyphName,
                'url' => $this->getGlyph(),
                'label' => $this->glyphName,
            ];
        } else if ($this->type === 'css') {
            $remoteSet = $this->getRemoteSet();

            return [
                'type' => 'css',
                'name' => $remoteSet['fontName'],
                'value' => 'css:' . $this->iconSet . ':' . $this->css,
                'url' => '',
                'classes' => $remoteSet['classes'] . $this->css,
                'label' => $this->css,
            ];
        }

        return [
            'type' => 'svg',
            'value' => $this->icon,
            'url' => $this->url,
            'label' => $this->iconName,
        ];
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
