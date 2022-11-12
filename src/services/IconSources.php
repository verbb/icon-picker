<?php
namespace verbb\iconpicker\services;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\base\IconSourceInterface;
use verbb\iconpicker\events\RegisterIconSourceEvent;
use verbb\iconpicker\iconsources\FontAwesome;

use Craft;
use craft\base\Component;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\StringHelper;

class IconSources extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_REGISTER_ICON_SOURCE = 'registerIconSource';

    private ?array $_remoteSources = null;


    // Public Methods
    // =========================================================================

    public function getRegisteredIconSources(): ?array
    {
        if ($this->_remoteSources !== null) {
            return $this->_remoteSources;
        }

        $settings = IconPicker::$plugin->getSettings();

        $sources = [
            FontAwesome::class,
        ];

        $event = new RegisterIconSourceEvent([
            'sources' => $sources,
        ]);

        $this->trigger(self::EVENT_REGISTER_ICON_SOURCE, $event);

        foreach ($event->sources as $source) {
            $this->_remoteSources[] = ComponentHelper::createComponent([
                'type' => $source,
                'settings' => $settings->iconSources[$source] ?? [],
            ], IconSourceInterface::class);
        }

        return $this->_remoteSources;
    }

    public function getRegisteredIconSourceByClass(string $class): ?IconSourceInterface
    {
        foreach ($this->getRegisteredIconSources() as $source) {
            if ($source instanceof $class) {
                return $source;
            }
        }

        return null;
    }

    public function getIconSourcesForSettings(): array
    {
        $settings = [];
        $sources = $this->getRegisteredIconSources();

        foreach ($sources as $source) {
            if ($source::hasSettings()) {
                $settings[] = $source;
            }
        }

        return $settings;
    }

}
