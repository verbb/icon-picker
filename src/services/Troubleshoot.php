<?php
namespace verbb\iconpicker\services;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\queue\jobs\GenerateIconSetCache;

use Craft;
use craft\base\Component;
use craft\base\Field;
use craft\helpers\App;

class Troubleshoot extends Component
{
    // Public Methods
    // =========================================================================

    public function runDiagnostics(array $iconSets = null): array
    {
        $settings = IconPicker::$plugin->getSettings();

        $results = [];
        $results[] = $this->_checkPathSetting('Icon Sets Path', $settings->iconSetsPath);
        $results[] = $this->_checkUrlSetting('Icon Sets URL', $settings->iconSetsUrl);

        if ($iconSets === null) {
            $iconSets = IconPicker::$plugin->getIconSets()->getAllIconSets();
        }

        foreach ($iconSets as $iconSet) {
            $results[] = $this->_checkIconSet($iconSet);
        }

        return $results;
    }

    
    // Private Methods
    // =========================================================================

    private function _checkPathSetting(string $label, ?string $path): array
    {
        $resolved = App::parseEnv($path);

        $details = [
            "Original: $path",
            "Resolved: $resolved",
            'Exists: ' . (file_exists($resolved) ? '✅' : '❌'),
            'Readable: ' . (is_readable($resolved) ? '✅' : '❌'),
            'Writable: ' . (is_writable($resolved) ? '✅' : '❌'),
        ];

        return [
            'label' => $label,
            'status' => file_exists($resolved) ? 'success' : 'error',
            'message' => "Resolved path: $resolved",
            'details' => $details,
        ];
    }

    private function _checkUrlSetting(string $label, ?string $url): array
    {
        $resolved = App::parseEnv($url);

        return [
            'label' => $label,
            'status' => $resolved ? 'success' : 'error',
            'message' => "Resolved URL: $resolved",
            'details' => ["Original: $url", "Resolved: $resolved"],
        ];
    }

    private function _checkIconSet($iconSet): array
    {
        $label = "Icon Set: " . ($iconSet->name ?? 'Unnamed');

        try {
            $iconSet->fetchIcons();

            $icons = $iconSet->icons; // array of Icon models
            $count = count($icons);

            $details = [];
            $rawModels = [];

            if ($count > 0) {
                $details = $iconSet->getDiagnosticsSummary();

                foreach (array_slice($icons, 0, 5) as $icon) {
                    $rawModels[] = $iconSet->getIconDiagnosticsSummary($icon);
                }
            }

            $status = ($count > 0) ? 'success' : 'warning';
            $message = ($count > 0) ? $count . ' icons found successfully.' : 'No icons found on disk.';

            return [
                'label' => $label,
                'status' => $status,
                'message' => $message,
                'details' => $details,
                'raw' => $rawModels,
            ];
        } catch (\Throwable $e) {
            return [
                'label' => $label,
                'status' => 'error',
                'message' => 'Error loading icon set: ' . $e->getMessage(),
                'details' => [],
            ];
        }
    }
}