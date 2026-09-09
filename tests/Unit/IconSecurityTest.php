<?php

declare(strict_types=1);

use ReflectionClass;
use ReflectionMethod;
use verbb\iconpicker\IconPicker;
use verbb\iconpicker\fields\IconPickerField;
use verbb\iconpicker\models\Icon;

describe('Icon normalize security', function() {
    it('strips forged displayValue from field POST', function() {
        $field = (new ReflectionClass(IconPickerField::class))->newInstanceWithoutConstructor();
        $icon = $field->normalizeValue([
            'type' => 'css',
            'value' => 'heart',
            'displayValue' => '<svg><title>forged</title></svg>',
        ]);

        expect($icon->getDisplayValue())->not->toBe('<svg><title>forged</title></svg>');
    });

    it('clears path-traversal values for local SVG icons', function() {
        $field = (new ReflectionClass(IconPickerField::class))->newInstanceWithoutConstructor();
        $icon = $field->normalizeValue([
            'type' => 'svg',
            'value' => '../synthetic.txt',
        ]);

        expect($icon->value)->toBeEmpty();
    });

    it('rejects null-byte paths', function() {
        $field = (new ReflectionClass(IconPickerField::class))->newInstanceWithoutConstructor();
        $icon = $field->normalizeValue([
            'type' => 'svg',
            'value' => "icons/safe.svg\0../evil.txt",
        ]);

        expect($icon->value)->toBeEmpty();
    });

    it('does not render forged displayValue markup for CSS icons', function() {
        $field = (new ReflectionClass(IconPickerField::class))->newInstanceWithoutConstructor();
        $icon = $field->normalizeValue([
            'type' => 'css',
            'value' => 'x',
            'displayValue' => '<svg><title>synthetic</title></svg>',
        ]);

        $icon->setDisplayValue('<svg><title>synthetic</title></svg>');

        $method = new ReflectionMethod($field, '_renderIcon');
        $method->setAccessible(true);
        $rendered = (string)$method->invoke($field, $icon, 'renderedPreviewResources');

        expect($rendered)->not->toContain('<svg><title>synthetic</title></svg>');
    });
});

describe('Icon path containment', function() {
    it('refuses to read files outside the configured icon sets path', function() {
        expect(IconPicker::$plugin)->not->toBeNull();

        $tmp = sys_get_temp_dir() . '/icon-picker-test-' . bin2hex(random_bytes(4));
        mkdir($tmp);
        mkdir($tmp . '/icons');
        file_put_contents($tmp . '/synthetic.txt', 'SYNTHETIC_MARKER');
        file_put_contents($tmp . '/icons/ok.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $settings = IconPicker::$plugin->getSettings();
        $previous = $settings->iconSetsPath;
        $settings->iconSetsPath = $tmp . '/icons';

        try {
            $field = (new ReflectionClass(IconPickerField::class))->newInstanceWithoutConstructor();
            $escaped = $field->normalizeValue(['type' => 'svg', 'value' => '../synthetic.txt']);
            expect((string)($escaped->getInline() ?? ''))->not->toContain('SYNTHETIC_MARKER');

            $ok = new Icon(['type' => 'svg', 'value' => 'ok.svg']);
            expect($ok->getPath())->toContain('ok.svg');
            expect((string)$ok->getInline())->toContain('<svg');
        } finally {
            $settings->iconSetsPath = $previous;
            @unlink($tmp . '/synthetic.txt');
            @unlink($tmp . '/icons/ok.svg');
            @rmdir($tmp . '/icons');
            @rmdir($tmp);
        }
    });
});
