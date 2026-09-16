<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\GlobalSet;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\Json;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use verbb\iconpicker\IconPicker;
use verbb\iconpicker\fields\IconPickerField;
use verbb\iconpicker\iconsets\Heroicons;
use yii\db\JsonExpression;

it('keeps saved provider identity through renames, deletion and handle reuse', function(bool $legacyString, bool $rename) {
    $service = IconPicker::$plugin->getIconSets();
    $config = Craft::$app->getProjectConfig();
    $suffix = bin2hex(random_bytes(4));
    $handle = 'identity' . $suffix;
    $source = new Heroicons(['name' => 'Identity source', 'handle' => $handle, 'variants' => ['outline']]);
    expect($service->saveIconSet($source))->toBeTrue();
    $field = new IconPickerField(['name' => 'Identity', 'handle' => $handle, 'iconSets' => '*']);
    expect(Craft::$app->getFields()->saveField($field))->toBeTrue();
    $layout = new FieldLayout(['type' => GlobalSet::class]);
    $layout->setTabs([new FieldLayoutTab(['layout' => $layout, 'name' => 'Content', 'elements' => [new CustomField($field)]])]);
    expect(Craft::$app->getFields()->saveLayout($layout))->toBeTrue();
    $global = new GlobalSet(['name' => 'Identity', 'handle' => $handle, 'fieldLayoutId' => $layout->id]);
    $global->setFieldLayout($layout);
    expect(Craft::$app->getGlobals()->saveSet($global))->toBeTrue();
    $value = ['type' => 'svg', 'value' => 'academic-cap', 'iconSet' => 'outline', 'iconSetHandle' => $handle];
    $global->setFieldValue($field->handle, $value);
    expect(Craft::$app->getElements()->saveElement($global))->toBeTrue();
    $expectedUrl = GlobalSet::find()->id($global->id)->one()->getFieldValue($field->handle)->getUrl();
    $column = $layout->getCustomFieldElements()[0]->uid;
    $unrelated = ['text' => 'Keep this value', 'number' => 123456789];
    // Older Craft migrations can retain field values as JSON strings inside content.
    Craft::$app->getDb()->createCommand()->update('{{%elements_sites}}', ['content' => new JsonExpression([
        $column => $legacyString ? Json::encode($value) : $value,
        'unrelated' => $unrelated,
    ])], ['elementId' => $global->id])->execute();
    $config->saveModifiedConfigData();
    $config->reset();

    try {
        if ($rename) {
            $source->handle .= 'Renamed';
            expect($service->saveIconSet($source))->toBeTrue();
        } else {
            expect($service->deleteIconSet($source))->toBeTrue();
        }
        $reloaded = GlobalSet::find()->id($global->id)->one()->getFieldValue($field->handle);
        expect($reloaded->getUrl())->toBe($rename ? $expectedUrl : null);
        $stored = Json::decode((new Query())->select('content')->from('{{%elements_sites}}')->where(['elementId' => $global->id])->scalar());
        $storedIcon = is_string($stored[$column]) ? Json::decode($stored[$column]) : $stored[$column];
        expect($storedIcon['iconSetUid'])->toBe($source->uid)->and($stored['unrelated'])->toBe($unrelated);

        $replacement = new Heroicons(['name' => 'Replacement', 'handle' => $handle, 'cdnVersion' => '2.1.5']);
        expect($service->saveIconSet($replacement))->toBeTrue();
        expect(GlobalSet::find()->id($global->id)->one()->getFieldValue($field->handle)->getUrl())->toBe($rename ? $expectedUrl : null);
        $config->saveModifiedConfigData();
        $config->reset();
        if ($rename) expect($service->deleteIconSet($source))->toBeTrue();
        expect(GlobalSet::find()->id($global->id)->one()->getFieldValue($field->handle)->getUrl())->toBeNull();

        // Restoring the same UID under a new handle restores the original provider reference.
        $source->handle = $handle . 'Restored';
        expect($service->saveIconSet($source))->toBeTrue();
        expect(GlobalSet::find()->id($global->id)->one()->getFieldValue($field->handle)->getUrl())->toBe($expectedUrl);
    } finally {
        $config->saveModifiedConfigData();
        $config->reset();
        if ($service->getIconSetByUid($source->uid)) $service->deleteIconSet($source);
        if (isset($replacement)) $service->deleteIconSet($replacement);
        Craft::$app->getGlobals()->deleteSet($global);
        Craft::$app->getFields()->deleteField($field);
    }
})->with([[false, false], [false, true], [true, false], [true, true]]);
