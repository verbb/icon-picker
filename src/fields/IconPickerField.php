<?php
namespace verbb\iconpicker\fields;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\assetbundles\IconPickerAsset;
use verbb\iconpicker\models\Icon;
use verbb\iconpicker\queue\jobs\GenerateIconSetCache;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\gql\GqlEntityRegistry;
use craft\gql\TypeLoader;
use craft\helpers\Json;

use yii\db\Schema;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use craft\helpers\Html;

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

        $id = Html::id($this->handle);
        $nameSpacedId = Craft::$app->getView()->namespaceInputId($id);

        $pluginSettings = IconPicker::$plugin->getSettings();

        Craft::$app->getView()->registerAssetBundle(IconPickerAsset::class);

        $enabledIconSets = IconPicker::$plugin->getService()->getEnabledIconSets($this);
        $remoteIconSets = IconPicker::$plugin->getService()->getEnabledRemoteSets($this);

        // Fetch the actual icons (from the cache)
        IconPicker::$plugin->getService()->getIcons($enabledIconSets, $remoteIconSets);

        // Fetch any fonts or spritesheets that are extra and once-off
        $spriteSheets = IconPicker::$plugin->getService()->getSpriteSheets();
        $fonts = IconPicker::$plugin->getService()->getLoadedFonts();

        $settings = array_merge($this->settings, $pluginSettings->toArray());

        Craft::$app->getView()->registerJs('new Craft.IconPicker.Input(' . Json::encode([
            'id' => $id,
            'inputId' => $nameSpacedId,
            'name' => $this->handle,
            'fonts' => $fonts,
            'spriteSheets' => $spriteSheets,
            'settings' => $settings,
            'fieldId' => $this->id,
        ]) . ');');

        return Craft::$app->getView()->renderTemplate('icon-picker/_field/input', [
            'id' => $id,
            'name' => $this->handle,
            'namespaceId' => $nameSpacedId,
            'value' => $value,
            'showLabels' => $this->showLabels,
        ]);
    }

    public function getSettingsHtml(): ?string
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSetsPath = $settings->getIconSetsPath();

        $errors = [];

        $iconSets = IconPicker::$plugin->getService()->getIconSets();
        $remoteSets = IconPicker::$plugin->getIconSources()->getRegisteredOptions();

        if (!$iconSets) {
            $errors[] = 'Unable to locate SVG Icons source directory.</strong><br>Please ensure the directory <code>' . $iconSetsPath . '</code> exists.</p>';
        }

        // If it found the path, we'll always have the root folder - make sure to remove that
        if (in_array('[root]', $iconSets)) {
            unset($iconSets['[root]']);
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

        $model = new Icon();

        if (is_string($value) && !empty($value)) {
            $value = Json::decodeIfJson($value);
        }

        if (is_array($value)) {
            $model->setAttributes($value, false);
        }

        return $model;
    }

    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        // If saving a sprite, we need to sort out the type - although easier than front-end input changing.
        if (str_contains($value['icon'], 'sprite:')) {
            $explode = explode(':', $value['icon']);

            $value['icon'] = null;
            $value['type'] = $explode[0];
            $value['iconSet'] = $explode[1];
            $value['sprite'] = $explode[2];
        }

        if (str_contains($value['icon'], 'glyph:')) {
            $explode = explode(':', $value['icon']);

            $value['icon'] = null;
            $value['type'] = $explode[0];
            $value['iconSet'] = $explode[1];
            $value['glyphId'] = $explode[2];
            $value['glyphName'] = $explode[3];
        }

        if (str_contains($value['icon'], 'css:')) {
            $explode = explode(':', $value['icon']);

            $value['icon'] = null;
            $value['type'] = $explode[0];
            $value['iconSet'] = $explode[1];
            $value['css'] = $explode[2];
        }

        return $value;
    }

    public function afterSave(bool $isNew): void
    {
        $settings = IconPicker::$plugin->getSettings();
        $iconSets = IconPicker::$plugin->getService()->getEnabledIconSets($this);

        // When saving the field, fire off queue jobs to prime the icon cache
        foreach ($iconSets as $iconSetKey => $iconSetName) {
            if ($settings->enableCache) {
                Craft::$app->getQueue()->push(new GenerateIconSetCache([
                    'iconSetKey' => $iconSetKey,
                ]));
            } else {
                IconPicker::$plugin->getCache()->generateIconSetCache($iconSetKey);
            }
        }

        parent::afterSave($isNew);
    }

    public function getContentGqlType(): array|Type
    {
        $typeName = 'Icon_Dimensions';

        $dimensionType = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new ObjectType([
            'name' => $typeName,
            'fields' => [
                'width' => Type::string(),
                'height' => Type::string(),
            ],
        ]));

        TypeLoader::registerType($typeName, static function() use ($dimensionType) {
            return $dimensionType;
        });

        $typeName = $this->handle . '_Icon';

        $iconType = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new ObjectType([
            'name' => $typeName,
            'fields' => [
                'url' => Type::string(),
                'icon' => Type::string(),
                'sprite' => Type::string(),
                'glyphId' => Type::string(),
                'glyphName' => Type::string(),
                'iconSet' => Type::string(),
                'type' => Type::string(),
                'css' => Type::string(),
                'width' => Type::string(),
                'height' => Type::string(),
                'path' => Type::string(),
                'dimensions' => $dimensionType,
                'inline' => Type::string(),
                'iconName' => Type::string(),
                'hasIcon' => Type::string(),
                'serializedValue' => Type::string(),
                'glyph' => Type::string(),
            ],
        ]));

        TypeLoader::registerType($typeName, static function() use ($iconType) {
            return $iconType;
        });

        return $iconType;
    }
}
