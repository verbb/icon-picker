<?php

declare(strict_types=1);

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\iconsets\SvgFolder;

it('rebuilds a preloaded catalog without stale or duplicate icons and metadata', function() {
    $root = sys_get_temp_dir() . '/icon-refresh-' . bin2hex(random_bytes(4));
    mkdir($root);
    file_put_contents($root . '/old.svg', '<svg/>');
    file_put_contents($root . '/metadata.json', '{"old":["before"]}');
    $settings = IconPicker::$plugin->getSettings();
    $previousPath = $settings->iconSetsPath;
    $settings->iconSetsPath = $root;
    $set = new SvgFolder(['handle' => basename($root), 'folder' => '[root]']);

    try {
        $set->populateIcons();
        expect(array_column($set->icons, 'value'))->toBe(['/old.svg']);
        file_put_contents($root . '/new.svg', '<svg/>');
        file_put_contents($root . '/metadata.json', '{"old":["after"],"new":["fresh"]}');
        $set->populateIcons(false);
        expect(array_column($set->icons, 'value'))->toBe(['/new.svg', '/old.svg'])
            ->and(array_column($set->icons, 'keywords'))->toBe(['fresh', 'after']);
        unlink($root . '/old.svg');
        $set->populateIcons(false);
        expect(array_column($set->icons, 'value'))->toBe(['/new.svg']);
        $set->populateIcons(false);
        expect($set->icons)->toHaveCount(1);
    } finally {
        $settings->iconSetsPath = $previousPath;
        foreach (glob($root . '/*') as $file) {
            unlink($file);
        }
        rmdir($root);
        Craft::$app->getCache()->delete('icon-picker:v2:' . $set->handle);
    }
});
