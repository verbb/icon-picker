<?php

$vendor = getenv('VERBB_TEST_VENDOR') ?: dirname(__DIR__, 2) . '/vendor';
require $vendor . '/autoload.php';
require_once $vendor . '/yiisoft/yii2/Yii.php';
require_once $vendor . '/craftcms/cms/src/Craft.php';
$checks = 0;
$check = static function(bool $value, string $message) use (&$checks): void {
    if (!$value) {
        throw new RuntimeException($message);
    }
    $checks++;
};

$key = 'icon-picker-craft4';
$service = new \verbb\iconpicker\services\IconSources();
$names = $service->getJsonData('material.json');
$check(($key === 'icon-picker-craft4' ? $names[738] === '3d_rotation' : $names[57344] === 'error'), 'Material glyph IDs must resolve to their bundled names.');
$plugin = (new ReflectionClass(\verbb\iconpicker\IconPicker::class))->newInstanceWithoutConstructor();
$plugin->setComponents(['iconSources' => \verbb\iconpicker\services\IconSources::class]);
$check($plugin->getIconSources()->getJsonData('material.json') === $names, 'The public icon source getter must resolve its service.');

echo "Passed $checks behavioral regression checks on PHP " . PHP_VERSION . ".\n";
