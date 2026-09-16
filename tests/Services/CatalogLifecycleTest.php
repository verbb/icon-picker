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
        Craft::$app->getCache()->delete(SvgFolder::getCacheKey($set->handle));
    }
});

it('uses effective root settings for cached catalogs and resources', function() {
    $root = sys_get_temp_dir() . '/icon-roots-' . bin2hex(random_bytes(4));
    foreach (['one', 'two'] as $directory) {
        mkdir($root . '/' . $directory, 0775, true);
        file_put_contents($root . '/' . $directory . '/' . $directory . '.svg', '<svg/>');
        file_put_contents($root . '/' . $directory . '/icons-sprites.svg', '<svg><symbol id="' . $directory . '"/></svg>');
    }
    $settings = IconPicker::$plugin->getSettings();
    $previous = [$settings->iconSetsPath, $settings->iconSetsUrl];
    $settings->iconSetsPath = $root . '/one';
    $settings->iconSetsUrl = 'https://example.test/one';
    $set = new SvgFolder(['handle' => basename($root), 'folder' => '[root]']);
    $sprite = new \verbb\iconpicker\iconsets\SvgSprite(['handle' => basename($root) . 'Sprite', 'spriteFile' => 'icons-sprites.svg']);
    try {
        $set->populateIcons();
        $sprite->populateResources();
        expect(array_column($set->icons, 'value'))->toBe(['/one.svg']);
        $settings->iconSetsPath = $root . '/two';
        $settings->iconSetsUrl = 'https://example.test/two';
        $set->populateIcons();
        $sprite->populateResources();
        expect(array_column($set->icons, 'value'))->toBe(['/two.svg'])
            ->and($sprite->getSpriteSheets()[0]['url'])->toBe('https://example.test/two/icons-sprites.svg');
        // URL-only changes also replace resource payloads that store resolved URLs.
        $settings->iconSetsUrl = 'https://example.test/three';
        $sprite->populateResources();
        expect($sprite->getSpriteSheets()[0]['url'])->toBe('https://example.test/three/icons-sprites.svg');
    } finally {
        [$settings->iconSetsPath, $settings->iconSetsUrl] = $previous;
        \craft\helpers\FileHelper::removeDirectory($root);
    }
});
