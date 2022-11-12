<?php
namespace verbb\iconpicker\queue\jobs;

use verbb\iconpicker\IconPicker;

use Craft;
use craft\queue\BaseJob;

class GenerateIconSetCache extends BaseJob
{
    // Properties
    // =========================================================================

    public ?string $iconSetKey = null;
    public ?string $remoteSet = null;
    public bool $isRemote = false;


    // Public Methods
    // =========================================================================

    public function execute($queue): void
    {
        $this->setProgress($queue, 0);

        if ($this->isRemote) {
            $iconSet = IconPicker::$plugin->getIconSets()->getRemoteSetByKey($this->remoteSet);
        } else {
            $iconSet = IconPicker::$plugin->getIconSets()->getIconSetByKey($this->iconSetKey);
        }

        if ($iconSet) {
            IconPicker::$plugin->getCache()->generateIconSetCache($iconSet);
        }

        $this->setProgress($queue, 1);
    }

    protected function defaultDescription(): ?string
    {
        return Craft::t('icon-picker', 'Generating icon cache for "{iconSetKey}"', ['iconSetKey' => $this->iconSetKey]);
    }
}