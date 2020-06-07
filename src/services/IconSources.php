<?php
namespace verbb\iconpicker\services;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\events\RegisterIconSourceEvent;
use verbb\iconpicker\helpers\IconPickerHelper;

use Craft;
use craft\base\Component;
use craft\helpers\Json;

class IconSources extends Component
{
    // Constants
    // =========================================================================

    const EVENT_REGISTER_ICON_SOURCE = 'registerIconSource';

    private $_remoteSources;


    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        $this->getRegisteredIconSources();
    }

    public function getRegisteredIconSources()
    {
        if (null === $this->_remoteSources) {

            $icons = $this->getJsonData('font-awesome.json');
            $sources = [
                'font-awesome-all' => [
                    'label' => Craft::t('icon-picker', 'Font Awesome 5 (All)'),
                    'url' => 'https://use.fontawesome.com/releases/v5.7.2/css/all.css',
                    'icons' => $icons,
                    'classes' => 'fa fa-',
                    'fontName' => 'Font Awesome 5 Free',
                ],
            ];

            $event = new RegisterIconSourceEvent([
                'sources' => $sources,
            ]);

            $this->trigger(self::EVENT_REGISTER_ICON_SOURCE, $event);
            $this->_remoteSources = $event->sources;
        }
        
        return $this->_remoteSources;
    }

    public function getRegisteredOptions()
    {
        $options = [];
        $sources = $this->getRegisteredIconSources();

        foreach ($sources as $key => $source) {
            $options[] = ['label' => $source['label'], 'value' => $key];
        }

        return $options;
    }

    public function getRegisteredIconSourceByHandle($handle)
    {
        $sources = $this->getRegisteredIconSources();

        return $sources[$handle] ?? [];
    }

    public function getJsonData($file)
    {
        $iconPath = __DIR__ . '/../json/' . $file;

        return Json::decode(file_get_contents($iconPath));
    }

}
