<?php

declare(strict_types=1);

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\base\RemoteCssIconSet;
use verbb\iconpicker\base\RemoteSvgIconSet;

it('persists inherited remote provider package versions and variants', function() {
    $service = IconPicker::$plugin->getIconSets();
    foreach ($service->getRegisteredIconSets() as $type) {
        $set = new $type();
        if (!$set instanceof RemoteSvgIconSet && !$set instanceof RemoteCssIconSet) {
            continue;
        }
        $set->name = 'Provider settings';
        $set->handle = 'provider' . bin2hex(random_bytes(4));
        $set->cdnVersion = '1.2.3';
        if ($set instanceof RemoteSvgIconSet) {
            $set->variants = [];
        }
        expect($service->saveIconSet($set))->toBeTrue();
        try {
            $loaded = $service->getIconSetById($set->id);
            expect($loaded->cdnVersion)->toBe('1.2.3', $type);
            if ($set instanceof RemoteSvgIconSet) {
                expect($loaded->variants)->toBe([], $type);
            }
        } finally {
            $service->deleteIconSet($set);
        }
    }
});

it('keeps each CSS icon rendering attribute with its owning set', function() {
    $service = IconPicker::$plugin->getIconSets();
    $set = new verbb\iconpicker\iconsets\BootstrapIcons(['name' => 'Mixed CSS', 'handle' => 'mixed' . bin2hex(random_bytes(4))]);
    expect($service->saveIconSet($set))->toBeTrue();
    try {
        $set->populateIcons();
        $payload = $set->icons[0]->jsonSerialize();
        expect($payload)->toHaveKey('cssAttribute', 'class');
    } finally {
        $service->deleteIconSet($set);
    }
});

it('preserves an explicitly cleared Font Awesome style selection', function() {
    $service = IconPicker::$plugin->getIconSets();
    $set = new verbb\iconpicker\iconsets\FontAwesome([
        'name' => 'Empty styles', 'handle' => 'emptyStyles' . bin2hex(random_bytes(4)),
        'type' => 'kit', 'apiKey' => 'test-only', 'styles' => [],
    ]);
    expect($service->saveIconSet($set))->toBeTrue();
    try {
        $loaded = $service->getIconSetById($set->id);
        $includesStyle = new ReflectionMethod($loaded, '_shouldIncludeStyle');
        expect($includesStyle->invoke($loaded, ['family' => 'classic', 'style' => 'solid']))->toBeFalse()
            ->and($loaded->styles)->toBe('');
    } finally {
        $service->deleteIconSet($set);
    }
});

it('includes common Material Design Icons from the pinned font catalog', function() {
    $set = new \verbb\iconpicker\iconsets\MaterialDesignIcons(['handle' => 'mdiCatalog']);
    $set->populateIcons(false);
    $values = array_column($set->icons, 'value');
    foreach (['home', 'account', 'heart', 'star', 'check', 'magnify'] as $name) {
        expect($values)->toContain('mdi mdi-' . $name);
    }
});
