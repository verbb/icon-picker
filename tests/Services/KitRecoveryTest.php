<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use verbb\iconpicker\IconPicker;
use verbb\iconpicker\base\IconSet;
use verbb\iconpicker\iconsets\FontAwesome;

it('retries failed Kit requests without caching an empty success', function(string $operation, int $status, string $body) {
    $previousCache = Craft::$app->getCache();
    $previousClient = Craft::$container->getDefinitions()[Client::class] ?? null;
    $settings = IconPicker::$plugin->getSettings();
    $previousEnabled = $settings->enableCache;
    $settings->enableCache = true;
    Craft::$app->set('cache', new \yii\caching\ArrayCache(['defaultDuration' => 86400]));
    $history = [];
    $payload = $operation === 'catalog'
        ? ['data' => ['me' => ['kit' => ['iconUploads' => [], 'release' => ['icons' => [['id' => 'heart', 'label' => 'Heart', 'familyStylesByLicense' => ['free' => [['family' => 'classic', 'style' => 'solid']]]]]]]]]]
        : ['data' => ['me' => ['kits' => [['name' => 'Example', 'token' => 'fixture', 'version' => '6.0.0', 'licenseSelected' => 'free']]]]];
    $mock = new MockHandler([
        new Response(200, [], '{"access_token":"fixture"}'), new Response($status, [], $body),
        new Response(200, [], '{"access_token":"fixture"}'), new Response(200, [], json_encode($payload)),
    ]);
    $handler = HandlerStack::create($mock);
    $handler->push(Middleware::history($history));
    Craft::$container->set(Client::class, static function($container, $params) use ($handler) {
        return new Client(array_merge($params[0] ?? [], ['handler' => $handler]));
    });
    $set = new FontAwesome(['name' => 'Recovery', 'handle' => 'kitRecovery' . bin2hex(random_bytes(4)), 'type' => 'kit', 'apiKey' => 'fixture', 'kits' => ['fixture:6.0.0:free']]);
    try {
        if ($operation === 'catalog') {
            expect(fn() => $set->populateIcons(false))->toThrow(\RuntimeException::class);
            expect(Craft::$app->getCache()->get(IconSet::getCacheKey($set->handle)))->toBeFalse();
            $set->populateIcons();
            expect($set->icons)->toHaveCount(1);
            $set->populateIcons(false);
        } else {
            expect($set->getKits())->toBe([]);
            expect($set->getApiError())->not->toBeNull();
            expect($set->getKits())->toHaveCount(1);
            expect($set->getKits())->toHaveCount(1);
        }
        expect($set->getApiError())->toBeNull();
        expect($history)->toHaveCount(4);
    } finally {
        Craft::$app->set('cache', $previousCache);
        $settings->enableCache = $previousEnabled;
        Craft::$container->clear(Client::class);
        if ($previousClient !== null) {
            Craft::$container->set(Client::class, $previousClient);
        }
    }
})->with([
    'catalog HTTP failure' => ['catalog', 503, '{"error":"Temporarily unavailable"}'],
    'catalog GraphQL failure' => ['catalog', 200, '{"errors":[{"message":"Temporarily unavailable"}]}'],
    'settings HTTP failure' => ['settings', 503, '{"error":"Temporarily unavailable"}'],
    'settings GraphQL failure' => ['settings', 200, '{"errors":[{"message":"Temporarily unavailable"}]}'],
]);
