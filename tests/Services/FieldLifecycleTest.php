<?php

declare(strict_types=1);

use craft\elements\GlobalSet;
use craft\fieldlayoutelements\CustomField;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\helpers\StringHelper;
use verbb\iconpicker\IconPicker;
use verbb\iconpicker\fields\IconPickerField;
use verbb\iconpicker\models\Icon;

describe('Icon Picker Craft lifecycle', function() {
    it('persists a compact trusted value through a real field layout and element reload', function() {
        $suffix = bin2hex(random_bytes(4));
        $root = sys_get_temp_dir() . '/icon-lifecycle-' . $suffix;
        mkdir($root);
        file_put_contents($root . '/known.svg', '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0"/></svg>');

        $settings = IconPicker::$plugin->getSettings();
        $previousPath = $settings->iconSetsPath;
        $settings->iconSetsPath = $root;
        $field = new IconPickerField([
            'name' => 'Icon lifecycle fixture',
            'handle' => 'iconLifecycle' . $suffix,
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
            'name' => 'Icon lifecycle fixture',
            'handle' => 'iconLifecycle' . $suffix,
            'fieldLayoutId' => $layout->id,
        ]);
        $set->setFieldLayout($layout);

        try {
            expect(Craft::$app->getGlobals()->saveSet($set))->toBeTrue();
            $set->setFieldValue($field->handle, [
                'type' => Icon::TYPE_SVG,
                'value' => 'known.svg',
                'label' => 'Known',
                'displayValue' => '<svg><script>forged()</script></svg>',
            ]);
            expect(Craft::$app->getElements()->saveElement($set))->toBeTrue();

            $reloaded = Craft::$app->getGlobals()->getSetById($set->id);
            expect($reloaded)->not->toBe($set);
            $value = $reloaded->getFieldValue($field->handle);

            expect($value)->toBeInstanceOf(Icon::class)
                ->and($value->value)->toBe('known.svg')
                ->and((string)$value->getInline())->toContain('<path d="M0 0"')
                ->and((string)$value->getInline())->not->toContain('forged()')
                ->and($field->serializeValue($value))->not->toHaveKey('displayValue');
        } finally {
            if ($set->id) {
                Craft::$app->getGlobals()->deleteSet($set);
            }
            Craft::$app->getFields()->deleteField($field);
            $settings->iconSetsPath = $previousPath;
            @unlink($root . '/known.svg');
            @rmdir($root);
        }
    });
});
