<?php

declare(strict_types=1);

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\fields\IconPickerField;
use verbb\iconpicker\iconsets\WebFont;

it('uses matching safe font identifiers for ordinary filenames', function(string $name) {
    $root = sys_get_temp_dir() . '/icon-font-' . bin2hex(random_bytes(4));
    mkdir($root);
    copy(Craft::getAlias('@app/web/assets/cp/dist/fonts/Craft.ttf'), $root . '/' . $name . '.ttf');
    $settings = IconPicker::$plugin->getSettings();
    $previous = [$settings->iconSetsPath, $settings->iconSetsUrl];
    $settings->iconSetsPath = $root;
    $settings->iconSetsUrl = 'https://example.test/fonts';
    $service = IconPicker::$plugin->getIconSets();
    $source = new WebFont(['name' => 'Font file', 'handle' => 'fontFile' . bin2hex(random_bytes(4)), 'fontFile' => $name . '.ttf']);
    expect($service->saveIconSet($source))->toBeTrue();
    try {
        $source->populateIcons(false);
        expect($source->icons)->not->toBeEmpty();
        $icon = $source->icons[0];
        $fontName = $source->fonts[0]['name'];
        expect(preg_match('/^[a-zA-Z0-9_-]+$/D', $fontName))->toBe(1);
        expect($icon->iconSet)->toBe($name)->and($icon->jsonSerialize()['fontClass'])->toBe($fontName);
        $field = new IconPickerField(['handle' => 'fontPreview']);
        expect($field->getThumbHtml($icon, new \craft\elements\Entry(), 32))->toContain('class="ipui-font ' . $fontName . '"');
        $settings->iconSetsUrl = 'https://example.test/updated-fonts';
        $source->populateResources();
        expect($source->fonts[0]['url'])->toContain('/updated-fonts/');
    } finally {
        Craft::$app->getProjectConfig()->saveModifiedConfigData();
        Craft::$app->getProjectConfig()->reset();
        $service->deleteIconSet($source);
        [$settings->iconSetsPath, $settings->iconSetsUrl] = $previous;
        \craft\helpers\FileHelper::removeDirectory($root);
    }
})->with(['custom.icons', 'Custom Icons', 'custom-icons']);

it('keeps Material Symbols aligned with the shared glyph font identifier', function() {
    $source = new \verbb\iconpicker\iconsets\MaterialSymbols(['handle' => 'symbols' . bin2hex(random_bytes(4))]);
    $source->populateIcons(false);
    expect($source->icons[0]->jsonSerialize()['fontClass'])->toBe($source->fonts[1]['id']);
});
