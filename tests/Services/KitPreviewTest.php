<?php

declare(strict_types=1);

use craft\elements\Entry;
use craft\web\View;
use verbb\iconpicker\IconPicker;
use verbb\iconpicker\fields\IconPickerField;
use verbb\iconpicker\iconsets\FontAwesome;

class PreviewKitFixture extends FontAwesome
{
    public function getKit(string $kitId, string $license): array
    {
        return ['iconUploads' => [[
            'name' => 'Custom icon',
            'iconDefinition' => ['prefix' => 'fak', 'iconName' => 'custom-icon'],
        ]]];
    }
}

it('retains a custom kit icon source and registers its script for element previews', function() {
    $set = new PreviewKitFixture([
        'name' => 'Preview kit', 'handle' => 'previewKit' . bin2hex(random_bytes(4)),
        'type' => 'kit', 'apiKey' => 'test-only', 'kits' => ['fixture:6.0.0:free'],
    ]);
    $sets = IconPicker::$plugin->getIconSets();
    expect($sets->saveIconSet($set))->toBeTrue();
    try {
        $set->populateIcons(false);
        $icon = $set->icons[0];
        expect($icon->iconSetHandle)->toBe($set->handle);
        $field = new IconPickerField(['handle' => 'previewIcon']);
        expect($field->getThumbHtml($icon, new Entry(), 40))->toContain('fak fa-custom-icon');
        $scripts = Craft::$app->getView()->jsFiles[View::POS_END] ?? [];
        expect(implode('', $scripts))->toContain('https://kit.fontawesome.com/fixture.js');
    } finally {
        $sets->deleteIconSet($set);
    }
});
