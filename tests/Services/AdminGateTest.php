<?php

declare(strict_types=1);

use Tests\Support\AdminUser;
use Tests\Support\CpRequestContext;
use Tests\Support\NonAdminUser;
use verbb\iconpicker\IconPicker;
use verbb\iconpicker\controllers\IconSetsController;
use verbb\iconpicker\controllers\SettingsController;
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
