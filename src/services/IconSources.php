<?php
namespace verbb\iconpicker\services;

use craft\base\Component;
use craft\helpers\Json;

class IconSources extends Component
{
    // Public Methods
    // =========================================================================

    public function getJsonData(string $filename): array
    {
        return Json::decodeFromFile(dirname(__DIR__) . '/json/' . $filename);
    }
}
