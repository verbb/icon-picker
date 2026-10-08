<?php

declare(strict_types=1);

use verbb\iconpicker\fields\IconPickerField;

it('resolves legacy label settings without requiring a field resave', function() {
    expect((new IconPickerField(['showLabels' => true]))->getResolvedLabelDisplay())->toBe('below');
    expect((new IconPickerField(['showLabels' => false]))->getResolvedLabelDisplay())->toBe('tooltip');
    expect((new IconPickerField(['showLabels' => true, 'labelDisplay' => 'hidden']))->getResolvedLabelDisplay())->toBe('hidden');
});

it('rejects unsupported picker presentation settings', function() {
    $field = new IconPickerField(['iconSize' => 'huge', 'labelDisplay' => 'sometimes']);
    expect($field->validate(['iconSize', 'labelDisplay']))->toBeFalse();
    expect($field->getErrors())->toHaveKeys(['iconSize', 'labelDisplay']);
});

it('persists picker preferences and displays their settings choices', function() {
    $field = new IconPickerField([
        'name' => 'Picker presentation fixture',
        'handle' => 'pickerPresentation' . bin2hex(random_bytes(4)),
        'iconSets' => [],
        'iconSize' => 'small',
        'labelDisplay' => 'tooltip',
    ]);
    $fields = Craft::$app->getFields();
    expect($fields->saveField($field))->toBeTrue();
    try {
        $config = Craft::$app->getProjectConfig()->get('fields.' . $field->uid . '.settings');
        expect($config)->toMatchArray(['iconSize' => 'small', 'labelDisplay' => 'tooltip']);
        $copy = new IconPickerField($config);
        expect($copy->getResolvedLabelDisplay())->toBe('tooltip');
        expect($copy->iconSize)->toBe('small');
        $html = $copy->getSettingsHtml();
        expect($html)->toContain('name="iconSize"', 'name="labelDisplay"', 'Below Icon');
        expect($html)->not->toContain('name="showLabels"');
    } finally {
        $fields->deleteField($field);
    }
});
