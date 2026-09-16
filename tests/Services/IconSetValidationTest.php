<?php

declare(strict_types=1);

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\iconsets\SvgFolder;

it('rejects missing names and invalid or duplicate handles without writing configuration', function() {
    $set = new SvgFolder(['folder' => '[root]']);
    expect($set->validate())->toBeFalse()
        ->and($set->hasErrors('name'))->toBeTrue()
        ->and($set->hasErrors('handle'))->toBeTrue();
    $set->name = 'Duplicate';
    $set->handle = 'root';
    expect($set->validate())->toBeFalse()->and($set->hasErrors('handle'))->toBeTrue();
    $set->handle = 'invalid.handle';
    expect($set->validate())->toBeFalse()->and($set->hasErrors('handle'))->toBeTrue();
    $existing = IconPicker::$plugin->getIconSets()->getIconSetByHandle('root');
    expect($existing->validate())->toBeTrue();
});
