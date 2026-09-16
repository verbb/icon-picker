<?php

declare(strict_types=1);

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\base\RemoteSvgIconSet;
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

    it('emits semicolon-terminated glyph entities without HTML-encoding them in CP preview', function() {
        $field = (new ReflectionClass(IconPickerField::class))->newInstanceWithoutConstructor();
        $icon = $field->normalizeValue([
            'type' => Icon::TYPE_GLYPH,
            'iconSet' => 'demo-font',
            'value' => 'stack-overflow:61804',
        ]);

        expect($icon->value)->toBe('stack-overflow:61804');
        expect($icon->getGlyph())->toBe('&#xf16c;');
        expect($icon->getDisplayValue())->toBe('&#xf16c;');

        $method = new ReflectionMethod($field, '_renderIcon');
        $method->setAccessible(true);
        $rendered = (string)$method->invoke($field, $icon, 'renderedPreviewResources');

        expect($rendered)->toContain('&#xf16c;');
        expect($rendered)->not->toContain('&amp;#xf16c');
        expect($rendered)->toContain('class="ipui-font ' . $icon->jsonSerialize()['fontClass'] . '"');
    });

    it('keeps CSS selections as identifiers while presentation comes from the catalog', function() {
        $field = new IconPickerField();
        $valid = $field->normalizeValue(['type' => Icon::TYPE_CSS, 'value' => 'fas fa-heart']);
        $invalid = $field->normalizeValue(['type' => Icon::TYPE_CSS, 'value' => '<svg><title>preview</title></svg>']);

        expect($valid->value)->toBe('fas fa-heart')
            ->and($invalid->isEmpty())->toBeTrue()
            ->and($invalid->getDisplayValue())->toBe('');
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

    it('rejects submitted absolute and scheme-relative SVG URLs', function(string $value) {
        $field = (new ReflectionClass(IconPickerField::class))->newInstanceWithoutConstructor();
        $icon = $field->normalizeValue([
            'type' => Icon::TYPE_SVG,
            'iconSetHandle' => 'trusted-set',
            'value' => $value,
        ]);

        expect($icon->value)->toBeEmpty()
            ->and($icon->getUrl())->toBeNull();
    })->with([
        'private HTTP URL' => 'http://127.0.0.1/internal.svg',
        'HTTPS URL' => 'https://example.com/icon.svg',
        'scheme-relative URL' => '//example.com/icon.svg',
        'data URL' => 'data:image/svg+xml,<svg/>',
        'file URL' => 'file:///etc/passwd',
    ]);

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

describe('Remote SVG catalog trust', function() {
    it('derives URLs only for an exact configured catalog entry', function() {
        $iconSet = new class extends RemoteSvgIconSet {
            public function fetchIcons(): void
            {
                $this->icons = [new Icon([
                    'type' => Icon::TYPE_SVG,
                    'iconSetHandle' => 'trusted-set',
                    'iconSet' => 'outline',
                    'value' => 'known-icon',
                ])];
            }

            protected function catalogMap(): array
            {
                return [];
            }

            protected function defaultVariant(): string
            {
                return 'outline';
            }

            protected function defaultVersion(): string
            {
                return '1.0.0';
            }

            protected function buildSvgUrl(string $iconName, string $variant): string
            {
                return "https://cdn.example.test/{$variant}/{$iconName}.svg";
            }
        };
        $iconSet->handle = 'trusted-set';

        $known = new Icon(['type' => Icon::TYPE_SVG, 'iconSet' => 'outline', 'value' => 'known-icon']);
        $unknown = new Icon(['type' => Icon::TYPE_SVG, 'iconSet' => 'outline', 'value' => '../private']);
        $wrongVariant = new Icon(['type' => Icon::TYPE_SVG, 'iconSet' => 'solid', 'value' => 'known-icon']);

        expect($iconSet->resolveSvgUrl($known))->toBe('https://cdn.example.test/outline/known-icon.svg')
            ->and($iconSet->resolveSvgUrl($unknown))->toBeNull()
            ->and($iconSet->resolveSvgUrl($wrongVariant))->toBeNull();
    });
});

describe('Icon path containment', function() {
    it('refuses to read files outside the configured icon sets path', function() {
        expect(IconPicker::$plugin)->not->toBeNull();

        $tmp = sys_get_temp_dir() . '/icon-picker-test-' . bin2hex(random_bytes(4));
        mkdir($tmp);
        mkdir($tmp . '/icons');
        file_put_contents($tmp . '/synthetic.txt', 'SYNTHETIC_MARKER');
        file_put_contents($tmp . '/synthetic.svg', '<svg><title>OUTSIDE_MARKER</title></svg>');
        file_put_contents($tmp . '/icons/ok.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        symlink($tmp . '/synthetic.svg', $tmp . '/icons/linked.svg');

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

            $linked = new Icon(['type' => 'svg', 'value' => 'linked.svg']);
            expect($linked->getPath())->toBe('')
                ->and((string)($linked->getInline() ?? ''))->not->toContain('OUTSIDE_MARKER');
        } finally {
            $settings->iconSetsPath = $previous;
            @unlink($tmp . '/synthetic.txt');
            @unlink($tmp . '/synthetic.svg');
            @unlink($tmp . '/icons/linked.svg');
            @unlink($tmp . '/icons/ok.svg');
            @rmdir($tmp . '/icons');
            @rmdir($tmp);
        }
    });
});
