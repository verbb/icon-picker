<?php

declare(strict_types=1);

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\iconsets\SvgSprite;

it('loads zero or one sprite without failing or dropping a nested symbol', function(string $svg, array $expected) {
    $root = sys_get_temp_dir() . '/sprite-shape-' . bin2hex(random_bytes(4));
    mkdir($root);
    file_put_contents($root . '/test-sprites.svg', $svg);
    $settings = IconPicker::$plugin->getSettings();
    $previousPath = $settings->iconSetsPath;
    $settings->iconSetsPath = $root;
    $set = new SvgSprite(['handle' => basename($root), 'spriteFile' => 'test-sprites.svg']);

    try {
        $set->fetchIcons();
        expect(array_column($set->icons, 'value'))->toBe($expected);
    } finally {
        $settings->iconSetsPath = $previousPath;
        unlink($root . '/test-sprites.svg');
        rmdir($root);
    }
})->with([
    'empty SVG' => ['<svg xmlns="http://www.w3.org/2000/svg"/>', []],
    'single nested symbol' => ['<svg xmlns="http://www.w3.org/2000/svg"><symbol id="one"><path d="M0 0"/></symbol></svg>', ['one']],
    'grouped symbol' => ['<svg><symbol id="grouped"><g><path d="M0 0"/></g></symbol></svg>', ['grouped']],
    'multiple symbols' => ['<svg><symbol id="one"/><symbol id="two"/></svg>', ['one', 'two']],
    'malformed SVG' => ['<svg><symbol', []],
]);
