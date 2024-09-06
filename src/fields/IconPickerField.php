<?php
namespace verbb\iconpicker\fields;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\helpers\Plugin;
use verbb\iconpicker\models\Icon;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\base\ThumbableFieldInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\TypeLoader;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\web\View;

use yii\db\Schema;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

class IconPickerField extends Field implements ThumbableFieldInterface, PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('icon-picker', 'Icon Picker');
    }

    public static function icon(): string
    {
        return '@verbb/iconpicker/icon-mask.svg';
    }


    // Properties
    // =========================================================================

    public bool $showLabels = false;
    public mixed $iconSets = null;
    public ?string $renderId = null;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Remove unused settings
        unset($config['remoteSets'], $config['columnType']);

        parent::__construct($config);
    }

    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        return $value ? $this->_renderIcon($value, 'renderedPreviewResources') : '';
    }

    public function getThumbHtml(mixed $value, ElementInterface $element, int $size): ?string
    {
        return $value ? $this->_renderIcon($value, 'renderedThumbResources') : '';
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

    public function serializeValue(mixed $value, ElementInterface $element = null): mixed
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


    // Protected Methods
    // =========================================================================

    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
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


    // Private Methods
    // =========================================================================

    private function _renderIcon(mixed $value, string $cacheCategory): string
    {
        $view = Craft::$app->getView();
        $settings = IconPicker::$plugin->getSettings();

        // Check if any of the icons have additional resources to include
        // Adding the `iconSetHandle` was a recent addition, so best to check
        if ($value->iconSetHandle) {
            // Have we already rendered this spritesheet?
            if (!in_array($value->iconSetHandle, IconPicker::$plugin->getService()->$cacheCategory)) {
                if ($iconSet = IconPicker::$plugin->getIconSets()->getIconSetByHandle($value->iconSetHandle)) {
                    // Ensure the icons are loaded (from the cache)
                    $iconSet->populateIcons();

                    // Add all spritesheets to the DOM
                    foreach ($iconSet->getSpriteSheets() as $spriteSheet) {
                        $spriteSheetData = file_get_contents($spriteSheet['url']);
                        $spriteSheetHtml = '<div id="icon-picker-spritesheet-' . $spriteSheet['name'] . '" style="display: none;">' . $spriteSheetData . '</div>';

                        $view->registerHtml($spriteSheetHtml, View::POS_BEGIN);
                    }

                    foreach ($iconSet->fonts as $font) {
                        if ($font['type'] === 'local') {
                            $view->registerCss(<<<CSS
                                @font-face {
                                    font-family: "{$font['name']}";
                                    src: url("{$font['url']}");
                                    font-weight: normal;
                                    font-style: normal;
                                }

                                .{$font['name']} {
                                    font-family: "{$font['name']}" !important;
                                }
                            CSS);
                        } else if ($font['type'] === 'proxy') {
                            $view->registerCss(<<<CSS
                                .{$font['id']} {
                                    font-family: "{$font['name']}" !important;
                                }
                            CSS);
                        } else if ($font['type'] === 'remote') {
                            // Support multiple remote stylesheets
                            if (!is_array($font['url'])) {
                                $font['url'] = [$font['url']];
                            }

                            foreach ($font['url'] as $url) {
                                $view->registerCssFile($url);
                            }
                        }
                    }
                }
            }

            // Store the spritesheet in a flag plugin-wide to prevent multiple rendering
            IconPicker::$plugin->getService()->$cacheCategory[] = $value->iconSetHandle;
        }

        if ($value->type === Icon::TYPE_SVG) {
            $iconHtml = Cp::iconSvg($value->displayValue);

            return Html::tag('div', $iconHtml, ['class' => 'cp-icon']);
        }

        if ($value->type === Icon::TYPE_SPRITE) {
            $iconHtml = '<svg viewBox="0 0 1000 1000"><use xlink:href="#' . $value->displayValue . '" /></svg>';

            return Html::tag('div', $iconHtml, ['class' => 'cp-icon']);
        }

        if ($value->type === Icon::TYPE_GLYPH) {
            $iconHtml = '<span class="ipui-font font-face-' . $value->iconSet . '">' . $value->displayValue . '</span>';

            return Html::tag('div', $iconHtml, ['class' => 'cp-icon']);
        }

        if ($value->type === Icon::TYPE_CSS) {
            $iconHtml = '<span class="' . $value->displayValue . '"></span>';

            return Html::tag('div', $iconHtml, ['class' => 'cp-icon']);
        }

        return '';
    }
}
