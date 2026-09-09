<?php

declare(strict_types=1);

namespace Tests\Support;

use craft\db\Query;
use Craft;
use ReflectionClass;

final class IconPickerTestPlugin
{
    public static function ensureInstalledAndMigrated(): void
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $pluginRows = (new Query())
            ->select(['handle', 'schemaVersion'])
            ->from('{{%plugins}}')
            ->where(['handle' => 'icon-picker'])
            ->all();

        foreach ($pluginRows as $pluginRow) {
            $rowHandle = (string)($pluginRow['handle'] ?? '');
            if (!$rowHandle) {
                continue;
            }

            $key = 'plugins.' . $rowHandle;
            $pluginConfig = $projectConfig->get($key);

            if (!$pluginConfig || empty($pluginConfig['enabled'])) {
                $projectConfig->set($key, [
                    ...($pluginConfig ?: []),
                    'edition' => 'standard',
                    'enabled' => true,
                    'schemaVersion' => (string)($pluginConfig['schemaVersion'] ?? $pluginRow['schemaVersion'] ?? ''),
                ]);
            }
        }

        $pluginsReflection = new ReflectionClass(Craft::$app->plugins);
        foreach ([
            '_pluginsLoaded' => false,
            '_loadingPlugins' => false,
            '_plugins' => [],
        ] as $propertyName => $value) {
            if (!$pluginsReflection->hasProperty($propertyName)) {
                continue;
            }

            $property = $pluginsReflection->getProperty($propertyName);
            $property->setAccessible(true);
            $property->setValue(Craft::$app->plugins, $value);
        }

        $plugins = Craft::$app->plugins;

        if (!$plugins->isPluginInstalled('icon-picker')) {
            $plugins->installPlugin('icon-picker');
        } elseif (!$plugins->isPluginEnabled('icon-picker')) {
            $plugins->enablePlugin('icon-picker');
        }

        $plugin = $plugins->getPlugin('icon-picker');
        if (!$plugin) {
            throw new \RuntimeException('Icon Picker plugin failed to load for integration tests.');
        }

        $readOnly = $projectConfig->readOnly;
        $projectConfig->readOnly = false;

        try {
            $migrator = $plugin->getMigrator();

            foreach ($migrator->getNewMigrations() as $migration) {
                $migrator->migrateUp($migration);
            }

            if ($plugins->isPluginUpdatePending($plugin)) {
                $plugins->updatePluginVersionInfo($plugin);
            }
        } finally {
            $projectConfig->readOnly = $readOnly;
        }
    }
}
