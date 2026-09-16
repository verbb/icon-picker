<?php

declare(strict_types=1);

use verbb\iconpicker\base\RemoteSvgIconSet;
use verbb\iconpicker\models\Icon;

it('resolves a large remote catalog with indexed membership checks', function() {
    $set = new class extends RemoteSvgIconSet {
        public function populateIcons(bool $fromCache = true): void
        {
        }

        protected function catalogMap(): array
        {
            return [];
        }

        protected function defaultVariant(): string
        {
            return 'default';
        }

        protected function defaultVersion(): string
        {
            return '1';
        }

        protected function buildSvgUrl(string $iconName, string $variant): string
        {
            return "https://cdn.example.test/{$variant}/{$iconName}.svg";
        }
    };

    for ($i = 0; $i < 4000; $i++) {
        $set->icons[] = new Icon([
            'type' => Icon::TYPE_SVG,
            'iconSet' => 'default',
            'value' => 'icon-' . $i,
        ]);
    }

    $start = hrtime(true);
    $last = null;

    foreach ($set->icons as $icon) {
        $last = $set->resolveSvgUrl($icon);
    }

    $elapsed = (hrtime(true) - $start) / 1_000_000_000;

    expect($last)->toBe('https://cdn.example.test/default/icon-3999.svg')
        ->and($elapsed)->toBeLessThan(1.5);
})->group('perf');
