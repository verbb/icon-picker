<?php

declare(strict_types=1);

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Schema;
use verbb\iconpicker\fields\IconPickerField;
use verbb\iconpicker\models\Icon;

it('renders empty and selected values with the documented Twig guard', function() {
    $template = '{% if not icon.isEmpty() %}selected{% else %}empty{% endif %}';
    expect(Craft::$app->getView()->renderString($template, ['icon' => new Icon()]))->toBe('empty')
        ->and(Craft::$app->getView()->renderString($template, ['icon' => new Icon(['type' => 'css', 'value' => 'alarm'])]))->toBe('selected')
        ->and(Craft::$app->getView()->renderString('{{ icon|length }}', ['icon' => new Icon(['type' => 'css', 'value' => 'alarm'])]))->toBe('5');
});

it('resolves icon fields and shared fragments through GraphQL', function() {
    $field = new IconPickerField(['name' => 'GraphQL', 'handle' => 'graphqlIcon']);
    $type = $field->getContentGqlType();
    $schema = new Schema(['query' => new ObjectType([
        'name' => 'Query',
        'fields' => ['icon' => ['type' => $type, 'resolve' => fn() => new Icon(['type' => 'css', 'value' => 'bi bi-alarm'])]],
    ])]);
    $result = GraphQL::executeQuery($schema, '{ icon { ...Common } } fragment Common on IconInterface { value type isEmpty glyph url inline }')->toArray();
    expect($result)->not->toHaveKey('errors')
        ->and($result['data']['icon'])->toBe(['value'=>'bi bi-alarm','type'=>'css','isEmpty'=>false,'glyph'=>null,'url'=>null,'inline'=>null]);
});

it('keeps server paths and remote SVG downloads out of GraphQL', function() {
    $sets = \verbb\iconpicker\IconPicker::$plugin->getIconSets();
    $set = new \verbb\iconpicker\iconsets\Heroicons(['name' => 'GraphQL remote', 'handle' => 'graphqlRemote' . bin2hex(random_bytes(4))]);
    expect($sets->saveIconSet($set))->toBeTrue();

    $previousClient = Craft::$container->getDefinitions()[\GuzzleHttp\Client::class] ?? null;
    $requests = 0;
    Craft::$container->set(\GuzzleHttp\Client::class, function($container, $params) use (&$requests) {
        $config = $params[0] ?? [];
        $config['handler'] = function() use (&$requests) {
            $requests++;
            return \GuzzleHttp\Promise\Create::promiseFor(new \GuzzleHttp\Psr7\Response(200, [], '<svg><path d="M0 0"/></svg>'));
        };
        return new \GuzzleHttp\Client($config);
    });

    try {
        $field = new IconPickerField(['name' => 'GraphQL remote', 'handle' => 'graphqlRemote' . bin2hex(random_bytes(4))]);
        $type = $field->getContentGqlType();
        $schema = new Schema(['query' => new ObjectType([
            'name' => 'Query',
            'fields' => ['icons' => [
                'type' => \GraphQL\Type\Definition\Type::listOf($type),
                'resolve' => fn() => array_map(
                    fn() => new Icon([
                        'type' => Icon::TYPE_SVG,
                        'value' => 'academic-cap',
                        'iconSet' => 'outline',
                        'iconSetHandle' => $set->handle,
                    ]),
                    range(1, 2),
                ),
            ]],
        ])]);

        $result = GraphQL::executeQuery($schema, '{ icons { url first: inline second: inline } }')->toArray();
        $pathResult = GraphQL::executeQuery($schema, '{ icons { path } }')->toArray();
        $complexity = $type->getField('inline')->getComplexityFn();

        expect($result)->not->toHaveKey('errors')
            ->and($result['data']['icons'])->toHaveCount(2)
            ->and($result['data']['icons'][0]['url'])->toContain('academic-cap.svg')
            ->and($result['data']['icons'][0]['first'])->toBeNull()
            ->and($result['data']['icons'][0]['second'])->toBeNull()
            ->and($result['data']['icons'][1]['first'])->toBeNull()
            ->and($result['data']['icons'][1]['second'])->toBeNull()
            ->and($requests)->toBe(0)
            ->and($pathResult)->toHaveKey('errors')
            ->and($type->hasField('path'))->toBeFalse()
            ->and($complexity(0, []))->toBe(\craft\services\Gql::GRAPHQL_COMPLEXITY_CPU_HEAVY);
    } finally {
        Craft::$container->clear(\GuzzleHttp\Client::class);
        if ($previousClient !== null) {
            Craft::$container->set(\GuzzleHttp\Client::class, $previousClient);
        }
        $sets->deleteIconSet($set);
    }
});

it('still returns local SVG markup through GraphQL inline', function() {
    $root = sys_get_temp_dir() . '/icon-picker-graphql-' . bin2hex(random_bytes(4));
    mkdir($root);
    file_put_contents($root . '/known.svg', '<svg><title>Local GraphQL icon</title></svg>');

    $settings = \verbb\iconpicker\IconPicker::$plugin->getSettings();
    $previousPath = $settings->iconSetsPath;
    $settings->iconSetsPath = $root;

    try {
        $field = new IconPickerField(['name' => 'GraphQL local', 'handle' => 'graphqlLocal' . bin2hex(random_bytes(4))]);
        $type = $field->getContentGqlType();
        $schema = new Schema(['query' => new ObjectType([
            'name' => 'Query',
            'fields' => ['icon' => [
                'type' => $type,
                'resolve' => fn() => new Icon(['type' => Icon::TYPE_SVG, 'value' => 'known.svg']),
            ]],
        ])]);
        $result = GraphQL::executeQuery($schema, '{ icon { inline } }')->toArray();

        expect($result)->not->toHaveKey('errors')
            ->and($result['data']['icon']['inline'])->toContain('Local GraphQL icon');
    } finally {
        $settings->iconSetsPath = $previousPath;
        @unlink($root . '/known.svg');
        @rmdir($root);
    }
});

it('renders static selections without mounting editable controls', function() {
    \Tests\Support\CpRequestContext::activate('globals/static-audit', 'GET');
    Craft::$app->set('assetManager', Craft::createObject(\craft\helpers\App::assetManagerConfig()));
    Craft::$app->getView()->setTemplateMode(\craft\web\View::TEMPLATE_MODE_CP);
    $field = new IconPickerField(['name' => 'Read only icon', 'handle' => 'staticIcon']);
    $value = new Icon(['type' => 'css', 'value' => 'bi bi-alarm', 'label' => 'Alarm']);
    $html = $field->getStaticHtml($value, new \craft\elements\Entry());
    expect($html)->toContain('bi bi-alarm')->toContain('Alarm')
        ->not->toContain('data-icon-picker-auto-mount')->not->toContain('<input');
    expect($field->getStaticHtml(new Icon(), new \craft\elements\Entry()))->toBe('');
});

it('renders saved remote SVG fields without downloading their markup on the server', function() {
    $sets = \verbb\iconpicker\IconPicker::$plugin->getIconSets();
    $set = new \verbb\iconpicker\iconsets\Heroicons(['name' => 'Remote preview', 'handle' => 'remotePreview' . bin2hex(random_bytes(4))]);
    expect($sets->saveIconSet($set))->toBeTrue();
    $previousClient = Craft::$container->getDefinitions()[\GuzzleHttp\Client::class] ?? null;
    $requests = 0;
    Craft::$container->set(\GuzzleHttp\Client::class, function($container, $params) use (&$requests) {
        $config = $params[0] ?? [];
        $config['handler'] = function() use (&$requests) {
            $requests++;
            return \GuzzleHttp\Promise\Create::promiseFor(new \GuzzleHttp\Psr7\Response(200, [], '<svg><path d="M0 0"/></svg>'));
        };
        return new \GuzzleHttp\Client($config);
    });
    try {
        \Tests\Support\CpRequestContext::activate('globals/remote-preview', 'GET');
        Craft::$app->set('assetManager', Craft::createObject(\craft\helpers\App::assetManagerConfig()));
        Craft::$app->getView()->setTemplateMode(\craft\web\View::TEMPLATE_MODE_CP);
        $field = new IconPickerField(['name' => 'Remote icon', 'handle' => 'remoteIcon']);
        for ($i = 0; $i < 3; $i++) {
            $icon = new Icon(['type' => 'svg', 'value' => 'academic-cap', 'iconSet' => 'outline', 'iconSetHandle' => $set->handle]);
            expect($field->getInputHtml($icon, new \craft\elements\Entry()))->toContain('academic-cap.svg');
        }
        expect($requests)->toBe(0);
        expect((string)$icon->getInline())->toContain('<svg');
        expect($requests)->toBe(1);
    } finally {
        Craft::$container->clear(\GuzzleHttp\Client::class);
        if ($previousClient !== null) {
            Craft::$container->set(\GuzzleHttp\Client::class, $previousClient);
        }
        $sets->deleteIconSet($set);
    }
});
