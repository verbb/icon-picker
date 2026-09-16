<?php

declare(strict_types=1);

use craft\helpers\Json;
use Tests\Support\AdminUser;
use Tests\Support\CpRequestContext;
use verbb\iconpicker\IconPicker;
use verbb\iconpicker\controllers\IconSetsController;
use verbb\iconpicker\helpers\ProjectConfigHelper;
use verbb\iconpicker\iconsets\SvgFolder;

it('reorders icon sets through the CP action and persists project configuration', function() {
    AdminUser::login();
    $service = IconPicker::$plugin->getIconSets();
    $suffix = bin2hex(random_bytes(4));
    $first = new SvgFolder(['name' => 'First', 'handle' => 'first' . $suffix, 'folder' => '[root]']);
    $second = new SvgFolder(['name' => 'Second', 'handle' => 'second' . $suffix, 'folder' => '[root]']);
    expect($service->saveIconSet($first))->toBeTrue();
    expect($service->saveIconSet($second))->toBeTrue();

    try {
        CpRequestContext::activate('actions/icon-picker/icon-sets/reorder', 'POST');
        $request = Craft::$app->getRequest();
        $request->getHeaders()->set('Accept', 'application/json');
        $request->setBodyParams(['ids' => Json::encode([$second->id, $first->id])]);
        $controller = new IconSetsController('icon-sets', IconPicker::$plugin);
        $controller->enableCsrfValidation = false;
        $response = $controller->runAction('reorder');

        expect($response->data)->toBe(['success' => true]);
        $config = ProjectConfigHelper::rebuildProjectConfig();
        expect($config['icon-sets'][$second->uid]['sortOrder'])->toBe(1)
            ->and($config['icon-sets'][$first->uid]['sortOrder'])->toBe(2);
    } finally {
        $service->deleteIconSet($first);
        $service->deleteIconSet($second);
    }
});
