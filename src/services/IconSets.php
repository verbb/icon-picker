<?php
namespace verbb\iconpicker\services;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\models\IconSet;

use Craft;
use craft\base\Component;
use craft\base\Field;
use craft\helpers\ArrayHelper;
use craft\helpers\FileHelper;

class IconSets extends Component
{
    // Public Methods
    // =========================================================================

    public function getIconSetByKey(string $key): ?IconSet
    {
        return ArrayHelper::firstWhere($this->getIconSets(), 'key', $key);
    }

    public function getRemoteSetByKey(string $key): ?IconSet
    {
        return ArrayHelper::firstWhere($this->getRemoteIconSets(), 'remoteSet', $key);
    }

    public function getIconSets(): array
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->getIconSetsPath();

        $iconSets = [];

        if (is_dir($iconSetsPath)) {
            // Always return the root
            $iconSets[] = new IconSet([
                'key' => '[root]',
                'name' => '',
                'type' => IconSet::TYPE_ROOT,
            ]);

            $folders = FileHelper::findDirectories($iconSetsPath, [
                'recursive' => false,
            ]);

            foreach ($folders as $folder) {
                $path = str_replace($iconSetsPath, '', $folder);
                $name = basename($folder);

                $iconSets[] = new IconSet([
                    'key' => 'folder:' . $path,
                    'name' => $name,
                    'type' => IconSet::TYPE_FOLDER,
                ]);
            }

            // Get all sprites
            $spriteSheets = FileHelper::findFiles($iconSetsPath, [
                'only' => ['*-sprites.svg'],
                'recursive' => false,
            ]);

            foreach ($spriteSheets as $spriteSheet) {
                $name = pathinfo($spriteSheet, PATHINFO_FILENAME);
                $name = str_replace('-sprites', '', $name);
                $filename = basename($spriteSheet);

                $iconSets[] = new IconSet([
                    'key' => 'sprite:' . $filename,
                    'name' => $name,
                    'type' => IconSet::TYPE_SPRITE,
                ]);
            }

            // // Get all icon fonts
            $fonts = FileHelper::findFiles($iconSetsPath, [
                'only' => ['*.ttf', '*.woff', '*.otf', '*.woff2'],
                'recursive' => false,
            ]);

            foreach ($fonts as $font) {
                $name = pathinfo($font, PATHINFO_FILENAME);
                $filename = basename($font);

                $iconSets[] = new IconSet([
                    'key' => 'font:' . $filename,
                    'name' => $name,
                    'type' => IconSet::TYPE_FONT,
                ]);
            }
        }

        // Sort alphabetically
        usort($iconSets, fn($a, $b) => strcmp($a->name, $b->name));

        return $iconSets;
    }

    public function getEnabledIconSets(Field $field): array
    {
        $allIconSets = $this->getIconSets();

        if ($field->iconSets === '' || $field->iconSets === null) {
            return [];
        }

        // For each enabled icon set, generate a cache
        if ($field->iconSets === '*') {
            return $allIconSets;
        }

        // Always include '[root]'
        $iconSets = [new IconSet([
            'key' => '[root]',
            'name' => '',
            'type' => IconSet::TYPE_ROOT,
        ])];

        foreach ($allIconSets as $allIconSet) {
            if (in_array($allIconSet->key, $field->iconSets)) {
                $iconSets[] = $allIconSet;
            }
        }

        return $iconSets;
    }

    public function getRemoteIconSets(): array
    {
        $iconSets = [];
        $sources = IconPicker::$plugin->getIconSources()->getRegisteredIconSources();

        foreach ($sources as $source) {
            foreach ($source->getIconSets() as $iconSet) {
                $iconSets[] = $iconSet;
            }
        }

        return $iconSets;
    }

    public function getEnabledRemoteSets(Field $field): array
    {
        $allRemoteSets = $this->getRemoteIconSets();

        if ($field->remoteSets === '' || $field->remoteSets === null) {
            return [];
        }

        // For each enabled icon set, generate a cache
        if ($field->remoteSets === '*') {
            return $allRemoteSets;
        }

        $iconSets = [];

        foreach ($allRemoteSets as $allRemoteSet) {
            if (in_array($allRemoteSet->key, $field->remoteSets)) {
                $iconSets[] = $allRemoteSet;
            }
        }

        return $iconSets;
    }
}