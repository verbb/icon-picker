<?php
namespace verbb\iconpicker\fields;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\helpers\Plugin;
use verbb\iconpicker\models\Icon;

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
    public ?string $renderId = null;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Config normalization
        if (array_key_exists('remoteSets', $config)) {
            unset($config['remoteSets']);
        }

        parent::__construct($config);
    }

    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        if (!$value) {
            $value = new Icon();
        }

        $view = Craft::$app->getView();
        $iconPickerService = IconPicker::$plugin->getService();

        $id = $this->renderId ?? Html::id($this->handle);
        $nameSpacedId = $view->namespaceInputId($id);
        $pluginSettings = IconPicker::$plugin->getSettings();

        // Check if this is a non-SVG icon. We will need to trigger a lazy-load of any
        // spritesheets, fonts, or remote CSS, but we don't want to fire that here before load.
        $loadResources = false;

        if ($value->value && $value->type !== Icon::TYPE_SVG) {
            $loadResources = true;
        }

        Plugin::registerAsset('field/src/js/icon-picker.js');

        // Create the IconPicker Input Vue component
        $js = 'new Craft.IconPicker.Input(' . Json::encode([
            'id' => $id,
            'inputId' => $nameSpacedId,
            'name' => $this->handle,
            'loadResources' => $loadResources,
            'settings' => $this->settings,
            'fieldId' => $this->id,
            'itemSize' => $pluginSettings->iconItemSize,
            'itemSizeLarge' => $pluginSettings->iconItemSizeLarge,
            'itemWrapperSize' => $pluginSettings->iconItemWrapperSize,
            'itemWrapperSizeLarge' => $pluginSettings->iconItemWrapperSizeLarge,
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
        $iconSets = IconPicker::$plugin->getIconSets()->getAllEnabledIconSets();

        return Craft::$app->getView()->renderTemplate('icon-picker/_field/settings', [
            'field' => $this,
            'iconSets' => $iconSets,
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
        $iconSets = IconPicker::$plugin->getIconSets()->getIconSetsForField($this);

        IconPicker::$plugin->getService()->clearAndRegenerateCache($iconSets);

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
