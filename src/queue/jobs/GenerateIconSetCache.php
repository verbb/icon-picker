<?php
namespace verbb\iconpicker\queue\jobs;

use verbb\iconpicker\IconPicker;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\queue\BaseJob;

class GenerateIconSetCache extends BaseJob
{
    // Properties
    // =========================================================================

    public $iconSetKey;


    // Public Methods
    // =========================================================================

    public function execute($queue)
    {
        $this->setProgress($queue, 0);

        IconPicker::$plugin->getCache()->generateIconSetCache($this->iconSetKey);

        $this->setProgress($queue, 1);
    }

    protected function defaultDescription(): string
    {
        return Craft::t('icon-picker', 'Generating icon cache for "{iconSetKey}"', ['iconSetKey' => $this->iconSetKey]);
    }
}