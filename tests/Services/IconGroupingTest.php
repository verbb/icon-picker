<?php

declare(strict_types=1);

use craft\helpers\FileHelper;
use Tests\Support\AdminUser;
use Tests\Support\CpRequestContext;
use verbb\iconpicker\IconPicker;
use verbb\iconpicker\controllers\IconsController;
use verbb\iconpicker\fields\IconPickerField;
use verbb\iconpicker\helpers\CpInputContext;
use verbb\iconpicker\iconsets\SvgFolder;

it('groups SVG catalogs relative to the selected folder without changing saved values or recursion', function() {
    $root = sys_get_temp_dir() . '/icon-groups-' . bin2hex(random_bytes(4));
    FileHelper::createDirectory($root . '/custom/social/brands');
    file_put_contents($root . '/custom/root.svg', '<svg/>');
    file_put_contents($root . '/custom/social/brands/nested.svg', '<svg/>');
    $settings = IconPicker::$plugin->getSettings();
    $previousPath = $settings->iconSetsPath;
    $settings->iconSetsPath = $root;
    $set = new SvgFolder(['handle' => basename($root), 'folder' => '/custom']);

    try {
        $set->populateIcons(false);
        $icons = $set->icons;
        usort($icons, fn($a, $b) => strcmp($a->value, $b->value));
        expect($icons)->toHaveCount(2);
        $saved = array_map(fn($icon) => $icon->serializeValueForDb(), $icons);
        expect(array_map($set->getIconGroupPath(...), $icons))->toBe(['', '']);
        $set->groupBySubfolder = true;
        expect(array_map($set->getIconGroupPath(...), $icons))->toBe(['', 'social/brands']);
        expect(array_map(fn($icon) => $icon->serializeValueForDb(), $icons))->toBe($saved);
        $set->folder = '[root]';
        expect($set->getIconGroupPath($icons[1]))->toBe('custom/social/brands');
        $set->folder = '/custom';
        $set->recursive = false;
        $set->populateIcons(false);
        expect(array_column($set->icons, 'value'))->toBe(['/custom/root.svg']);
    } finally {
        $settings->iconSetsPath = $previousPath;
        FileHelper::removeDirectory($root);
        Craft::$app->getCache()->delete(SvgFolder::getCacheKey($set->handle));
    }
});

it('adds configured set identity and names to catalog responses', function() {
    AdminUser::login();
    $setsService = IconPicker::$plugin->getIconSets();
    $source = new \verbb\iconpicker\iconsets\Heroicons(['name' => 'Grouped source', 'handle' => 'groupedSource' . bin2hex(random_bytes(4)), 'variants' => ['outline']]);
    expect($setsService->saveIconSet($source))->toBeTrue();
    $field = new IconPickerField(['name' => 'Grouped icons', 'handle' => 'groupedIconFixture', 'iconSets' => [$source->uid]]);
    expect(Craft::$app->getFields()->saveField($field))->toBeTrue();
    CpRequestContext::activate('actions/icon-picker/icons/icons-for-field', 'POST');
    Craft::$app->getRequest()->setBodyParams([
        'fieldId' => $field->id,
        'siteId' => Craft::$app->getSites()->getCurrentSite()->id,
        'context' => CpInputContext::create($field, null),
    ]);
    $controller = new IconsController('icons', IconPicker::$plugin);
    $controller->enableCsrfValidation = false;

    try {
        $data = $controller->runAction('icons-for-field')->data;
        $sets = IconPicker::$plugin->getIconSets()->getIconSetsForField($field);
        expect($data['showSetHeadings'])->toBe(count($sets) > 1);
        expect($data['icons'])->not->toBeEmpty();
        expect($data['iconSets'])->toHaveCount(count($sets));
        foreach ($data['icons'] as $item) {
            $set = IconPicker::$plugin->getIconSets()->getIconSetByUid($data['iconSets'][$item['iconSetHandle']]['id']);
            expect($set)->not->toBeNull();
            expect($data['iconSets'][$item['iconSetHandle']]['label'])->toBe($set->name);
            expect($item['browsePath'])->toBe('');
            expect($item)->not->toHaveKey('browseGroup');
        }
    } finally {
        Craft::$app->getFields()->deleteField($field);
        $setsService->deleteIconSet($source);
    }
});
