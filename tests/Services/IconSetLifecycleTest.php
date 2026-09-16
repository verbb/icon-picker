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

    // Creation and reordering are separate requests in the CP. Craft deduplicates
    // nested project-config events within one request, so flush that boundary here.
    Craft::$app->getProjectConfig()->saveModifiedConfigData();
    Craft::$app->getProjectConfig()->reset();

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

it('refreshes warm catalogs after deployed settings, renames and handle reuse', function() {
    AdminUser::login();
    $service = IconPicker::$plugin->getIconSets();
    $config = Craft::$app->getProjectConfig();
    $handle = 'deployed' . bin2hex(random_bytes(4));
    $set = new \verbb\iconpicker\iconsets\Heroicons(['name' => 'Deployed', 'handle' => $handle, 'variants' => ['outline']]);
    expect($service->saveIconSet($set))->toBeTrue();
    $set->populateIcons();
    expect(count($set->icons))->toBeGreaterThan(0);
    $config->saveModifiedConfigData();
    $config->reset();

    try {
        $config->set($service::CONFIG_ICON_SETS_KEY . '.' . $set->uid . '.settings.variants', '[]');
        $changed = $service->getIconSetByUid($set->uid);
        $changed->populateIcons();
        expect($changed->variants)->toBe([])->and($changed->icons)->toBe([]);

        $config->saveModifiedConfigData();
        $config->reset();
        $changed->handle = $handle . 'Renamed';
        expect($service->saveIconSet($changed))->toBeTrue();
        $replacement = new \verbb\iconpicker\iconsets\Heroicons(['name' => 'Replacement', 'handle' => $handle, 'variants' => []]);
        expect($service->saveIconSet($replacement))->toBeTrue();
        $replacement->populateIcons();
        expect($replacement->icons)->toBe([]);

        $config->saveModifiedConfigData();
        $config->reset();
        expect($service->deleteIconSet($replacement))->toBeTrue();
        $replacement = new \verbb\iconpicker\iconsets\Heroicons(['name' => 'Recreated', 'handle' => $handle, 'variants' => ['solid']]);
        expect($service->saveIconSet($replacement))->toBeTrue();
        $replacement->populateIcons();
        expect(count($replacement->icons))->toBeGreaterThan(0);
    } finally {
        $config->saveModifiedConfigData();
        $config->reset();
        $service->deleteIconSet($service->getIconSetByUid($set->uid));
        if (isset($replacement)) $service->deleteIconSet($replacement);
    }
});

it('reports a vetoed icon set deletion as a failure', function(bool $json) {
    AdminUser::login();
    $service = IconPicker::$plugin->getIconSets();
    $set = $service->getAllIconSets()[0];
    $veto = static function($event) { $event->isValid = false; };
    $set->on(\craft\base\SavableComponent::EVENT_BEFORE_DELETE, $veto);
    CpRequestContext::activate('actions/icon-picker/icon-sets/delete', 'POST');
    Craft::$app->getRequest()->getHeaders()->set('Accept', $json ? 'application/json' : 'text/html');
    Craft::$app->getRequest()->setBodyParams(['id' => $set->id]);
    // Capture the host flash boundary because the suite runs in a console app.
    $controller = new class('icon-sets', IconPicker::$plugin) extends IconSetsController {
        public ?string $failureMessage = null;
        public function setFailFlash(?string $default = null, array $settings = []): void { $this->failureMessage = $default; }
    };
    $controller->enableCsrfValidation = false;
    try {
        $response = $controller->runAction('delete');
        expect((new \craft\db\Query())->from('{{%iconpicker_iconsets}}')->where(['id' => $set->id])->exists())->toBeTrue();
        if ($json) {
            expect($response->statusCode)->toBe(400)->and($response->data['success'])->toBeFalse();
        } else {
            expect($response)->toBeNull()->and($controller->failureMessage)->toBe('Couldn’t delete icon set.');
        }
    } finally {
        $set->off(\craft\base\SavableComponent::EVENT_BEFORE_DELETE, $veto);
        Craft::$app->getResponse()->setStatusCode(200);
    }
})->with([true, false]);
