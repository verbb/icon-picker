<?php

declare(strict_types=1);

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\iconsets\BootstrapIcons;

it('preserves a disabled boolean through project config and database storage', function() {
    $service = IconPicker::$plugin->getIconSets();
    $set = new BootstrapIcons(['name' => 'Disabled source', 'handle' => 'disabled' . bin2hex(random_bytes(4)), 'enabled' => false]);
    expect($service->saveIconSet($set))->toBeTrue();
    try {
        expect($service->getIconSetById($set->id)->getEnabled())->toBeFalse();
        expect(array_column($service->getAllEnabledIconSets(), 'uid'))->not->toContain($set->uid);
        $set->enabled = true;
        expect($service->saveIconSet($set))->toBeTrue();
        expect($service->getIconSetById($set->id)->getEnabled())->toBeTrue();
    } finally {
        Craft::$app->getProjectConfig()->saveModifiedConfigData();
        Craft::$app->getProjectConfig()->reset();
        $service->deleteIconSet($set);
    }
});
