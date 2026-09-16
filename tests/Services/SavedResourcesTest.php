<?php

declare(strict_types=1);

use craft\elements\GlobalSet;
use craft\fieldlayoutelements\CustomField;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use Tests\Support\CpRequestContext;
use verbb\iconpicker\IconPicker;
use verbb\iconpicker\controllers\IconsController;
use verbb\iconpicker\fields\IconPickerField;

it('loads saved selection resources independently of available choices', function(string $scenario) {
    $service = IconPicker::$plugin->getIconSets();
    $config = Craft::$app->getProjectConfig();
    $suffix = bin2hex(random_bytes(4));
    $source = match ($scenario) {
        'collection' => new \verbb\iconpicker\iconsets\FontAwesome(['type' => 'cdn', 'cdnLicense' => 'free', 'cdnVersion' => '5.15.4', 'cdnCollections' => ['solid']]),
        'weight' => new \verbb\iconpicker\iconsets\Phosphor(['variants' => ['regular']]),
        default => new \verbb\iconpicker\iconsets\BootstrapIcons(),
    };
    $source->name = 'Saved resources';
    $source->handle = 'savedResources' . $suffix;
    $source->enabled = $scenario !== 'disabled';
    expect($service->saveIconSet($source))->toBeTrue();
    $field = new IconPickerField(['name' => 'Saved resources', 'handle' => 'savedIcon' . $suffix, 'iconSets' => $scenario === 'excluded' ? [] : '*']);
    expect(Craft::$app->getFields()->saveField($field))->toBeTrue();
    $layout = new FieldLayout(['type' => GlobalSet::class]);
    $layout->setTabs([new FieldLayoutTab(['layout' => $layout, 'name' => 'Content', 'elements' => [new CustomField($field)]])]);
    expect(Craft::$app->getFields()->saveLayout($layout))->toBeTrue();
    $global = new GlobalSet(['name' => 'Saved resources', 'handle' => 'savedResources' . $suffix, 'fieldLayoutId' => $layout->id]);
    $global->setFieldLayout($layout);
    expect(Craft::$app->getGlobals()->saveSet($global))->toBeTrue();
    $value = match ($scenario) { 'collection' => 'fab fa-github', 'weight' => 'ph-duotone ph-acorn', default => 'bi bi-alarm' };
    $expected = match ($scenario) { 'collection' => '/css/brands.css', 'weight' => '/src/duotone/style.css', default => '/font/bootstrap-icons.min.css' };
    $global->setFieldValue($field->handle, ['type' => 'css', 'value' => $value, 'iconSetHandle' => $source->handle, 'iconSet' => $scenario === 'weight' ? 'duotone' : null]);
    expect(Craft::$app->getElements()->saveElement($global))->toBeTrue();
    try {
        Craft::$app->getUser()->setIdentity(\craft\elements\User::find()->admin(true)->one());
        CpRequestContext::activate('actions/icon-picker/icons/resources-for-field');
        Craft::$app->getRequest()->setIsConsoleRequest(true);
        Craft::$app->getRequest()->setBodyParams(['fieldId' => $field->id, 'elementId' => $global->id, 'siteId' => $global->siteId]);
        foreach (['resources-for-field', 'icons-for-field'] as $action) {
            $controller = new IconsController('icons', IconPicker::$plugin);
            $controller->enableCsrfValidation = false;
            $data = $controller->runAction($action)->data;
            expect(json_encode($data['fonts'], JSON_UNESCAPED_SLASHES))->toContain($expected);
            expect(array_column($data['icons'], 'value'))->not->toContain($value);
        }
        $field->getThumbHtml($global->getFieldValue($field->handle), $global, 32);
        expect(json_encode(Craft::$app->getView()->cssFiles, JSON_UNESCAPED_SLASHES))->toContain($expected);
    } finally {
        $config->saveModifiedConfigData();
        $config->reset();
        Craft::$app->getGlobals()->deleteSet($global);
        Craft::$app->getFields()->deleteField($field);
        $service->deleteIconSet($source);
    }
})->with(['excluded', 'disabled', 'collection', 'weight']);
