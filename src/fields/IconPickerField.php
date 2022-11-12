<?php
namespace verbb\iconpicker\fields;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\helpers\Plugin;
use verbb\iconpicker\models\Icon;
use verbb\iconpicker\models\IconSet;
use verbb\iconpicker\queue\jobs\GenerateIconSetCache;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\gql\GqlEntityRegistry;
use craft\gql\TypeLoader;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\helpers\Json;

use yii\db\Schema;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

class IconPickerField extends Field
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('icon-picker', 'Icon Picker');
    }


    // Properties
    // =========================================================================

    public string $columnType = Schema::TYPE_TEXT;
    public bool $showLabels = false;
    public mixed $iconSets = null;
    public mixed $remoteSets = null;


    // Public Methods
    // =========================================================================

    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        if (!$value) {
            $value = new Icon();
        }

        $view = Craft::$app->getView();
        $iconPickerService = IconPicker::$plugin->getService();

        $id = Html::id($this->handle);
        $nameSpacedId = $view->namespaceInputId($id);
        $pluginSettings = IconPicker::$plugin->getSettings();

        // Fetch the actual icons (from the cache), which preps any fonts and spritesheets
        $iconPickerService->getIconsForField($this);

        // Fetch any fonts or spritesheets that are extra and once-off
        $spriteSheets = $iconPickerService->getSpriteSheets();
        $fonts = $iconPickerService->getLoadedFonts();
        $scripts = $iconPickerService->getLoadedScripts();

        $settings = $this->settings;

        Plugin::registerAsset('field/src/js/icon-picker.js');

        // Create the IconPicker Input Vue component
        $js = 'new Craft.IconPicker.Input(' . Json::encode([
            'id' => $id,
            'inputId' => $nameSpacedId,
            'name' => $this->handle,
            'fonts' => $fonts,
            'spriteSheets' => $spriteSheets,
            'scripts' => $scripts,
            'settings' => $settings,
            'fieldId' => $this->id,
        ]) . ');';

        // Wait for IconPicker JS to be loaded, either through an event listener, or by a flag.
        // This covers if this script is run before, or after the IconPicker JS has loaded
        $view->registerJs('document.addEventListener("vite-script-loaded", function(e) {' .
            'if (e.detail.path === "field/src/js/icon-picker.js") {' . $js . '}' .
        '}); if (Craft.IconPickerReady) {' . $js . '}');

        return $view->renderTemplate('icon-picker/_field/input', [
            'id' => $id,
            'name' => $this->handle,
            'namespaceId' => $nameSpacedId,
            'value' => $value,
        ]);
    }

    public function getSettingsHtml(): ?string
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->getIconSetsPath();

        $errors = [];

        $iconSets = IconPicker::$plugin->getIconSets()->getIconSets();
        $remoteSets = IconPicker::$plugin->getIconSets()->getRemoteIconSets();

        if (!$iconSets) {
            $errors[] = 'Unable to locate SVG Icons source directory.</strong><br>Please ensure the directory <code>' . $iconSetsPath . '</code> exists.</p>';
        }

        return Craft::$app->getView()->renderTemplate('icon-picker/_field/settings', [
            'field' => $this,
            'settings' => $this->getSettings(),
            'iconSets' => $iconSets,
            'remoteSets' => $remoteSets,
            'errors' => $errors,
        ]);
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element = null): Icon
    {
        if ($value instanceof Icon) {
            return $value;
        }

        if (is_string($value) && !empty($value)) {
            $value = Json::decodeIfJson($value);
        }

        if (!is_array($value)) {
            $value = [];
        }

        return new Icon($value);
    }

    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if ($value instanceof Icon) {
            $value = $value->serializeValueForDb();
        }

        return $value;
    }

    public function afterSave(bool $isNew): void
    {
        // When saving the field, fire off queue jobs to prime the icon cache
        $iconSets = IconPicker::$plugin->getIconSets()->getEnabledIconSets($this);
        $remoteSets = IconPicker::$plugin->getIconSets()->getEnabledRemoteSets($this);
        $sets = array_merge($iconSets, $remoteSets);

        IconPicker::$plugin->getCache()->clearAndRegenerate($sets);

        parent::afterSave($isNew);
    }

    public function getContentGqlType(): array|Type
    {
        $typeName = $this->handle . '_Icon';

        $iconType = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new ObjectType([
            'name' => $typeName,
            'fields' => [
                'value' => [
                    'name' => 'value',
                    'type' => Type::string(),
                    'description' => 'The value of the icon. This will vary depending on the type of icon.',
                ],
                'iconSet' => [
                    'name' => 'iconSet',
                    'type' => Type::string(),
                    'description' => 'The icon set this icon belongs to.',
                ],
                'label' => [
                    'name' => 'label',
                    'type' => Type::string(),
                    'description' => 'The named representation of the icon.',
                ],
                'keywords' => [
                    'name' => 'keywords',
                    'type' => Type::string(),
                    'description' => 'The keywords used to search for the icon by. Defaults to the `label`.',
                ],
                'type' => [
                    'name' => 'type',
                    'type' => Type::string(),
                    'description' => 'What type of icon this is: `svg`, `sprite`, `glyph` or `css`.',
                ],
                'isEmpty' => [
                    'name' => 'isEmpty',
                    'type' => Type::boolean(),
                    'description' => 'Returns whether or not there‘s an icon selected for this field.',
                    'resolve' => function($model) {
                        return $model->isEmpty();
                    },
                ],
                'url' => [
                    'name' => 'url',
                    'type' => Type::string(),
                    'description' => 'Return the full URL to the icon.',
                ],
                'path' => [
                    'name' => 'path',
                    'type' => Type::string(),
                    'description' => 'Return the full path to the icon.',
                ],
                'inline' => [
                    'name' => 'inline',
                    'type' => Type::string(),
                    'description' => 'Returns the raw contents of the icon.',
                ],
                'glyph' => [
                    'name' => 'glyph',
                    'type' => Type::string(),
                    'description' => 'Returns the character representation of a font glyph.',
                ],
                'glyphName' => [
                    'name' => 'glyphName',
                    'type' => Type::string(),
                    'description' => 'Returns the named representation of a font glyph.',
                ],
            ],
        ]));

        TypeLoader::registerType($typeName, static function() use ($iconType) {
            return $iconType;
        });

        return $iconType;
    }
}
