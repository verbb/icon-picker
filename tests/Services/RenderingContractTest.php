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
