<?php
namespace verbb\iconpicker\queue\jobs;

use verbb\iconpicker\IconPicker;

use Craft;
use craft\queue\BaseJob;

class GenerateIconSetCache extends BaseJob
{
    // Properties
    // =========================================================================

    public ?string $iconSetUid = null;
    public ?string $iconSetHandle = null;


    // Public Methods
    // =========================================================================

    public function execute($queue): void
    {
        $this->setProgress($queue, 0);

        $iconSet = IconPicker::$plugin->getIconSets()->getIconSetByUid($this->iconSetUid);

        if ($iconSet) {
            $iconSet->populateIcons(false);
        }

        $this->setProgress($queue, 1);
    }

    protected function defaultDescription(): ?string
    {
        return Craft::t('icon-picker', 'Generating icon cache for "{iconSetHandle}"', ['iconSetHandle' => $this->iconSetHandle]);
    }
}