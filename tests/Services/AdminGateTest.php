<?php

declare(strict_types=1);

use Tests\Support\AdminUser;
use Tests\Support\CpRequestContext;
use Tests\Support\NonAdminUser;
use verbb\iconpicker\IconPicker;
use verbb\iconpicker\controllers\IconSetsController;
use verbb\iconpicker\controllers\SettingsController;
use verbb\iconpicker\controllers\UtilityController;
use yii\web\ForbiddenHttpException;

describe('Icon Picker plugin boot', function() {
    it('installs and exposes the plugin instance', function() {
        expect(IconPicker::$plugin)->not->toBeNull();
        expect(Craft::$app->plugins->isPluginEnabled('icon-picker'))->toBeTrue();
    });
});

describe('Settings and icon-sets admin gate', function() {
    it('SettingsController requires an admin', function() {
        NonAdminUser::login();
        CpRequestContext::activate('settings/plugins/icon-picker', 'GET');

        $controller = new SettingsController('settings', IconPicker::$plugin);
        $controller->enableCsrfValidation = false;
        $action = $controller->createAction('index');

        expect(fn() => $controller->beforeAction($action))
            ->toThrow(ForbiddenHttpException::class);
    });

    it('IconSetsController requires an admin', function() {
        NonAdminUser::login();
        CpRequestContext::activate('icon-picker/icon-sets', 'GET');

        $controller = new IconSetsController('icon-sets', IconPicker::$plugin);
        $controller->enableCsrfValidation = false;
        $action = $controller->createAction('index');

        expect(fn() => $controller->beforeAction($action))
            ->toThrow(ForbiddenHttpException::class);
    });

    it('allows admins through SettingsController beforeAction', function() {
        AdminUser::login();
        CpRequestContext::activate('settings/plugins/icon-picker', 'GET');

        $controller = new SettingsController('settings', IconPicker::$plugin);
        $controller->enableCsrfValidation = false;
        $action = $controller->createAction('index');

        expect($controller->beforeAction($action))->toBeTrue();
    });
});

it('requires POST for cache regeneration', function() {
    AdminUser::login();
    CpRequestContext::activate('actions/icon-picker/utility/clear-cache', 'GET');
    $controller = new UtilityController('utility', IconPicker::$plugin);
    $controller->enableCsrfValidation = false;
    expect(fn() => $controller->runAction('clear-cache'))->toThrow(\yii\web\MethodNotAllowedHttpException::class);
});

it('allows admin utilities when configuration changes are disabled', function() {
    AdminUser::login();
    CpRequestContext::activate('actions/icon-picker/utility/clear-cache', 'POST');
    $general = Craft::$app->getConfig()->getGeneral();
    $previous = $general->allowAdminChanges;
    $general->allowAdminChanges = false;
    try {
        $utilityController = new UtilityController('utility', IconPicker::$plugin);
        $utilityController->enableCsrfValidation = false;
        foreach (['clear-cache', 'troubleshoot'] as $id) {
            expect($utilityController->beforeAction($utilityController->createAction($id)))->toBeTrue();
        }

        $settingsController = new SettingsController('settings', IconPicker::$plugin);
        $settingsController->enableCsrfValidation = false;
        expect(fn() => $settingsController->beforeAction($settingsController->createAction('save-settings')))
            ->toThrow(ForbiddenHttpException::class);

        NonAdminUser::login();
        foreach (['clear-cache', 'troubleshoot'] as $id) {
            expect(fn() => $utilityController->beforeAction($utilityController->createAction($id)))
                ->toThrow(ForbiddenHttpException::class);
        }
    } finally {
        $general->allowAdminChanges = $previous;
    }
});
