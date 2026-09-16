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
