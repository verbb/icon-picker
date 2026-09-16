<?php

declare(strict_types=1);

use craft\elements\Entry;
use craft\web\View;
use verbb\iconpicker\IconPicker;
use verbb\iconpicker\fields\IconPickerField;
use verbb\iconpicker\iconsets\SvgSprite;

it('isolates sprite previews while preserving public and stored symbol names', function() {
    $root = sys_get_temp_dir() . '/sprite-identity-' . bin2hex(random_bytes(4));
    mkdir($root);
    $svg = '<svg xmlns="http://www.w3.org/2000/svg"><style>#heart:hover {fill:url(\'#paint\');stroke:#f00}</style><defs><linearGradient id="paint"/></defs><symbol id="heart"><path fill="url(#paint)"/><use href="#detail"/></symbol><path id="detail"/><path id="f00"/></svg>';
    foreach (['outline', 'solid'] as $name) {
        file_put_contents($root . '/' . $name . '-sprites.svg', $svg);
    }
    $settings = IconPicker::$plugin->getSettings();
    $previous = [$settings->iconSetsPath, $settings->iconSetsUrl];
    $settings->iconSetsPath = $root;
    $settings->iconSetsUrl = 'https://example.test/' . basename($root);
    $clientDefinition = Craft::$container->getDefinitions()[\GuzzleHttp\Client::class] ?? null;
    Craft::$container->set(\GuzzleHttp\Client::class, static function($container, $params) use ($svg) {
        return new \GuzzleHttp\Client(array_merge($params[0] ?? [], ['handler' => fn() => \GuzzleHttp\Promise\Create::promiseFor(new \GuzzleHttp\Psr7\Response(200, [], $svg))]));
    });
    $service = IconPicker::$plugin->getIconSets();
    $sets = [];
    $ids = [];
    $view = Craft::$app->getView();
    $view->startHtmlBuffer();
    try {
        foreach (['outline', 'solid'] as $name) {
            $set = new SvgSprite(['name' => $name, 'handle' => $name . bin2hex(random_bytes(4)), 'spriteFile' => $name . '-sprites.svg']);
            expect($service->saveIconSet($set))->toBeTrue();
            $sets[] = $set;
            $set->populateIcons(false);
            $icon = $set->icons[0];
            $namespace = $set->getSpriteSheets()[0]['namespace'];
            $ids[] = $icon->jsonSerialize()['spriteId'];
            expect($icon->jsonSerialize()['spriteId'])->toBe($namespace . '-heart');
            expect((string)$icon)->toBe('heart')->and($icon->serializeValueForDb()['value'])->toBe('heart');
            expect($icon->serializeValueForDb())->not->toHaveKey('spriteId');
            $field = new IconPickerField(['handle' => 'spritePreview']);
            expect($field->getThumbHtml($icon, new Entry(), 32))->toContain('href="#' . $namespace . '-heart"');
        }
        expect($ids[0])->not->toBe($ids[1]);
    } finally {
        $registered = $view->clearHtmlBuffer();
        foreach ($sets as $set) {
            $service->deleteIconSet($set);
        }
        [$settings->iconSetsPath, $settings->iconSetsUrl] = $previous;
        Craft::$container->clear(\GuzzleHttp\Client::class);
        if ($clientDefinition !== null) {
            Craft::$container->set(\GuzzleHttp\Client::class, $clientDefinition);
        }
        \craft\helpers\FileHelper::removeDirectory($root);
    }
    $html = implode('', $registered[View::POS_BEGIN] ?? []);
    foreach ($ids as $id) {
        $namespace = substr($id, 0, -strlen('-heart'));
        expect($html)->toContain('id="' . $id . '"')->toContain('#' . $id . ':hover {')->toContain('stroke:#f00')
            ->toContain('url(#' . $namespace . '-paint)')->toContain('href="#' . $namespace . '-detail"');
    }
});
