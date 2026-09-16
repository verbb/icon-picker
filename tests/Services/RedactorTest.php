<?php

declare(strict_types=1);

use Tests\Support\AdminUser;
use Tests\Support\CpRequestContext;
use verbb\iconpicker\IconPicker;
use verbb\iconpicker\controllers\RedactorController;
use verbb\iconpicker\fields\IconPickerField;

it('renders the configured Redactor picker with its production entry points', function() {
    AdminUser::login();
    $field = new IconPickerField(['name' => 'Redactor fixture', 'handle' => 'redactor' . bin2hex(random_bytes(4)), 'iconSets' => []]);
    expect(Craft::$app->getFields()->saveField($field))->toBeTrue();
    $settings = IconPicker::$plugin->getSettings();
    $previous = $settings->redactorFieldHandle;
    $settings->redactorFieldHandle = $field->handle;
    try {
        CpRequestContext::activate('actions/icon-picker/redactor', 'POST');
        $controller = new RedactorController('redactor', IconPicker::$plugin);
        $controller->enableCsrfValidation = false;
        $response = $controller->runAction('index');
        expect($response->data['inputHtml'])->toContain('data-icon-picker-auto-mount')
            ->and($response->data['inputHtml'])->toContain('redactor')
            ->and($response->data['footHtml'])->toContain('pluginKit-')
            ->and($response->data['footHtml'])->toContain('icon-picker-');

        // The configured field is the only source, even if a caller supplies another ID.
        Craft::$app->getRequest()->setBodyParams(['fieldId' => 999999]);
        expect($controller->runAction('icons-for-field')->data['icons'])->toBe([]);
    } finally {
        $settings->redactorFieldHandle = $previous;
        Craft::$app->getFields()->deleteField($field);
    }
});

it('requires POST when opening the Redactor picker', function() {
    AdminUser::login();
    CpRequestContext::activate('actions/icon-picker/redactor', 'GET');
    $controller = new RedactorController('redactor', IconPicker::$plugin);
    $controller->enableCsrfValidation = false;
    expect(fn() => $controller->runAction('index'))->toThrow(\yii\web\MethodNotAllowedHttpException::class);
});

it('reports a missing Redactor field without attempting to render an input', function() {
    AdminUser::login();
    CpRequestContext::activate('actions/icon-picker/redactor', 'POST');
    $settings = IconPicker::$plugin->getSettings();
    $previous = $settings->redactorFieldHandle;
    $settings->redactorFieldHandle = '';
    try {
        $controller = new RedactorController('redactor', IconPicker::$plugin);
        $controller->enableCsrfValidation = false;
        expect(fn() => $controller->runAction('index'))->toThrow(\yii\web\BadRequestHttpException::class);
    } finally {
        $settings->redactorFieldHandle = $previous;
    }
});
