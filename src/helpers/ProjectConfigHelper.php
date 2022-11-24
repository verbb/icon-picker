<?php
namespace verbb\iconpicker\helpers;

use verbb\iconpicker\IconPicker;

use Craft;
use craft\db\Query;

class ProjectConfigHelper
{
    // Static Methods
    // =========================================================================

    public static function rebuildProjectConfig(): array
    {
        $configData = [];

        $configData['icon-sets'] = self::_getIconSetsData();

        return array_filter($configData);
    }

    
    // Private Methods
    // =========================================================================

    private static function _getIconSetsData(): array
    {
        $data = [];

        $iconSetsService = IconPicker::$plugin->geticonSets();

        foreach ($iconSetsService->getAllIconSets() as $iconSet) {
            $data[$iconSet->uid] = $iconSetsService->createIconSetConfig($iconSet);
        }

        return $data;
    }
}
