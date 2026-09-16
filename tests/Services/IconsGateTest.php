<?php

declare(strict_types=1);

use craft\elements\GlobalSet;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use Tests\Support\AdminUser;
use Tests\Support\CpRequestContext;
use verbb\iconpicker\IconPicker;
use verbb\iconpicker\controllers\IconsController;
use verbb\iconpicker\fields\IconPickerField;
use yii\web\BadRequestHttpException;
use yii\web\MethodNotAllowedHttpException;

describe('IconsController access boundary', function() {
    it('rejects non-CP requests', function() {
        AdminUser::login();
        CpRequestContext::activate('actions/icon-picker/icons/icons-for-field', 'POST', false);

        $controller = new IconsController('icons', IconPicker::$plugin);
        $controller->enableCsrfValidation = false;
        $action = $controller->createAction('icons-for-field');

        expect(fn() => $controller->beforeAction($action))
            ->toThrow(BadRequestHttpException::class);
    });

    it('rejects non-POST requests', function() {
        AdminUser::login();
        CpRequestContext::activate('actions/icon-picker/icons/icons-for-field', 'GET');

        $controller = new IconsController('icons', IconPicker::$plugin);
        $controller->enableCsrfValidation = false;
        $action = $controller->createAction('icons-for-field');

        expect(fn() => $controller->beforeAction($action))
            ->toThrow(MethodNotAllowedHttpException::class);
    });

    it('requires an element context for a real Icon Picker field', function() {
        AdminUser::login();
        CpRequestContext::activate('actions/icon-picker/icons/icons-for-field', 'POST');

        $field = new IconPickerField([
            'name' => 'Icon access fixture',
            'handle' => 'iconAccessFixture',
        ]);
        expect(Craft::$app->getFields()->saveField($field))->toBeTrue();

        /** @var \craft\web\Request $request */
        $request = Craft::$app->getRequest();
        $request->setBodyParams(['fieldId' => $field->id]);

        $controller = new IconsController('icons', IconPicker::$plugin);
        $controller->enableCsrfValidation = false;

        try {
            $controller->runAction('icons-for-field');
            test()->fail('Expected a missing elementId exception.');
        } catch (BadRequestHttpException $e) {
            expect($request->getBodyParam('elementId'))->toBeNull()
                ->and($e->getMessage())->toBe('Request missing required param');
        } finally {
            Craft::$app->getFields()->deleteField($field);
        }
    });

    it('returns resources for the exact field on a viewable element layout', function() {
        AdminUser::login();
        $suffix = bin2hex(random_bytes(4));
        $field = new IconPickerField([
            'name' => 'Icon access fixture',
            'handle' => 'iconAccess' . $suffix,
            'iconSets' => [],
        ]);
        expect(Craft::$app->getFields()->saveField($field))->toBeTrue();

        $layout = new FieldLayout([
            'uid' => StringHelper::UUID(),
            'type' => GlobalSet::class,
        ]);
        $layout->setTabs([new FieldLayoutTab([
            'layout' => $layout,
            'name' => 'Content',
            'elements' => [new CustomField($field)],
        ])]);
        expect(Craft::$app->getFields()->saveLayout($layout))->toBeTrue();
        $set = new GlobalSet([
            'name' => 'Icon access fixture',
            'handle' => 'iconAccess' . $suffix,
            'fieldLayoutId' => $layout->id,
        ]);
        $set->setFieldLayout($layout);

        try {
            expect(Craft::$app->getGlobals()->saveSet($set))->toBeTrue();
            CpRequestContext::activate('actions/icon-picker/icons/resources-for-field', 'POST');
            Craft::$app->getRequest()->setIsConsoleRequest(true);
            Craft::$app->getRequest()->setBodyParams([
                'fieldId' => $field->id,
                'elementId' => $set->id,
                'siteId' => Craft::$app->getSites()->getPrimarySite()->id,
            ]);
            $controller = new IconsController('icons', IconPicker::$plugin);
            $controller->enableCsrfValidation = false;
            $response = $controller->runAction('resources-for-field');

            expect($response->data)->toBe([
                'icons' => [],
                'fonts' => [],
                'spriteSheets' => [],
                'scripts' => [],
            ]);
        } finally {
            if ($set->id) {
                Craft::$app->getGlobals()->deleteSet($set);
            }
            Craft::$app->getFields()->deleteField($field);
        }
    });
});
