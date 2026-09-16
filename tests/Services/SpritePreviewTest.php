<?php

declare(strict_types=1);

use craft\elements\Entry;
use verbb\iconpicker\IconPicker;
use verbb\iconpicker\fields\IconPickerField;
use verbb\iconpicker\iconsets\SvgSprite;

it('keeps element previews usable when a spritesheet URL is unavailable', function() {
    $root = sys_get_temp_dir() . '/sprite-preview-' . bin2hex(random_bytes(4));
    mkdir($root);
    file_put_contents($root . '/test-sprites.svg', '<svg><symbol id="preview-square" viewBox="0 0 24 24"><path d="M0 0h24v24z"/></symbol></svg>');
    $settings = IconPicker::$plugin->getSettings();
    $previousPath = $settings->iconSetsPath;
    $previousUrl = $settings->iconSetsUrl;
    $settings->iconSetsPath = $root;
    $settings->iconSetsUrl = 'http://127.0.0.1:9/';
    $sets = IconPicker::$plugin->getIconSets();
    $set = new SvgSprite(['name' => 'Sprite preview', 'handle' => 'spritePreview' . bin2hex(random_bytes(4)), 'spriteFile' => 'test-sprites.svg']);

    try {
        expect($sets->saveIconSet($set))->toBeTrue();
        $set->populateIcons(false);
        $icon = $set->icons[0];
        $field = new IconPickerField(['handle' => 'previewIcon']);
        expect($field->getThumbHtml($icon, new Entry(), 40))->toContain('href="#' . $icon->getCpSpriteId() . '"');
        expect($field->getPreviewHtml($icon, new Entry()))->toContain('href="#' . $icon->getCpSpriteId() . '"');
    } finally {
        if ($set->id) {
            $sets->deleteIconSet($set);
        }
        $settings->iconSetsPath = $previousPath;
        $settings->iconSetsUrl = $previousUrl;
        unlink($root . '/test-sprites.svg');
        rmdir($root);
    }
});
