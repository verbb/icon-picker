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

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class IconPickerField extends Field implements ThumbableFieldInterface, PreviewableFieldInterface
{
    // Constants
    // =========================================================================

    private const GQL_ICON_INTERFACE_NAME = 'IconInterface';


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
    /** Shown in the empty search control (e.g. “Choose an icon…”). */
    public ?string $placeholder = null;
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

        // Presentation markup is never authoritative from field POST — hydrate from
        // catalog/cache after load (SEC-03).
        unset($value['displayValue']);

        // Reject path traversal / absolute escapes in the stored relative value.
        if (isset($value['value']) && is_string($value['value']) && !$this->_isSafeIconValue($value['value'], $value['type'] ?? null)) {
            $value['value'] = '';
        }

        return new Icon($value);
    }

    /**
     * Relative SVG paths must stay under the configured icon root; remote absolute
     * URLs are only accepted for remote SVG sets (resolved via iconSetHandle).
     */
    private function _isSafeIconValue(string $raw, mixed $type): bool
    {
        $raw = trim($raw);

        if ($raw === '') {
            return true;
        }

        if (preg_match('#^(https?:)?//#i', $raw) || str_starts_with($raw, 'data:')) {
            // Absolute / data URLs are not stored as local file values.
            return ($type ?? '') !== 'svg' || str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://');
        }

        if (str_contains($raw, "\0") || str_contains($raw, '..')) {
            return false;
        }

        return true;
    }

    public function serializeValue(mixed $value, ElementInterface $element = null): mixed
    {
        if ($value instanceof Icon) {
            $value = $value->serializeValueForDb();
        }

        return $value;
    }

    public function isValueEmpty(mixed $value, ElementInterface $element): bool
    {
        return $value->isEmpty();
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
            'interfaces' => [
                static::gqlIconInterface(),
            ],
            'fields' => static::gqlIconFields(),
        ]));

        TypeLoader::registerType($typeName, static function() use ($iconType) {
            return $iconType;
        });

        return $iconType;
    }


    private static function gqlIconFields(): array
    {
        return [
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
        ];
    }

    private static function gqlIconInterface(): InterfaceType
    {
        $name = self::GQL_ICON_INTERFACE_NAME;

        $interface = GqlEntityRegistry::getEntity($name);
        if ($interface instanceof InterfaceType) {
            return $interface;
        }

        $interface = GqlEntityRegistry::createEntity($name, new InterfaceType([
            'name' => $name,
            'description' => 'Fields shared by every Icon Picker field GraphQL type so a single fragment can target `IconInterface` across field handles.',
            'fields' => static::gqlIconFields(),
        ]));

        TypeLoader::registerType($name, static function() use ($interface) {
            return $interface;
        });

        return $interface;
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
        // Feather-style CSS icons may already carry inline SVG in displayValue (hydrated
        // from the set cache) and need no remote resources.
        $this->_hydrateIconDisplay($value);

        $loadResources = false;
        $display = (string)$value->getDisplayValue();

        if (
            $value->value
            && $value->type !== Icon::TYPE_SVG
            && !str_starts_with(ltrim($display), '<svg')
        ) {
            $loadResources = true;
        }

        $componentSettings = [
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
        ];

        // Register Plugin Kit web components + Icon Picker field assets; roots are
        // mounted automatically by icon-picker.ts.
        Plugin::registerFieldAssets();

        return $view->renderTemplate('icon-picker/_field/input', [
            'id' => $id,
            'name' => $this->handle,
            'namespaceId' => $nameSpacedId,
            'value' => $value,
            'componentSettings' => Json::encode($componentSettings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _renderIcon(mixed $value, string $cacheCategory): string
    {
        $view = Craft::$app->getView();
        $settings = IconPicker::$plugin->getSettings();

        if ($value instanceof Icon) {
            $this->_hydrateIconDisplay($value);
        }

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
            $url = $value->getUrl();

            if ($url && str_starts_with($url, 'http')) {
                $iconHtml = Html::img($url, ['alt' => '', 'loading' => 'lazy']);

                return Html::tag('div', $iconHtml, ['class' => 'cp-icon']);
            }

            $iconHtml = Cp::iconSvg($value->displayValue);

            return Html::tag('div', $iconHtml, ['class' => 'cp-icon']);
        }

        if ($value->type === Icon::TYPE_SPRITE) {
            $spriteId = Html::encode((string)$value->displayValue);
            $iconHtml = '<svg viewBox="0 0 1000 1000"><use xlink:href="#' . $spriteId . '" href="#' . $spriteId . '" /></svg>';

            return Html::tag('div', $iconHtml, ['class' => 'cp-icon']);
        }

        if ($value->type === Icon::TYPE_GLYPH) {
            $iconHtml = '<span class="ipui-font font-face-' . Html::encode((string)$value->iconSet) . '">' . Html::encode((string)$value->displayValue) . '</span>';

            return Html::tag('div', $iconHtml, ['class' => 'cp-icon']);
        }

        if ($value->type === Icon::TYPE_CSS) {
            $display = (string)$value->getDisplayValue();

            // Feather (and similar) may cache a full <svg> as displayValue so the CP
            // does not depend on remote feather.replace(). Only after catalog hydrate.
            if (str_starts_with(ltrim($display), '<svg') && $value->iconSetHandle) {
                $iconHtml = $display;
            } else {
                $iconHtml = '<span class="' . Html::encode($display) . '"></span>';
            }

            return Html::tag('div', $iconHtml, ['class' => 'cp-icon']);
        }

        return '';
    }

    /**
     * Fill `displayValue` from the icon-set cache when the element only stored the
     * compact field value (e.g. Feather name → inline SVG for the chip / thumbs).
     */
    private function _hydrateIconDisplay(Icon $value): void
    {
        if (!$value->value || !$value->iconSetHandle || $value->type !== Icon::TYPE_CSS) {
            return;
        }

        $current = ltrim((string)$value->getDisplayValue());

        // Already rich (inline SVG) or empty.
        if ($current === '' || str_starts_with($current, '<svg')) {
            return;
        }

        $iconSet = IconPicker::$plugin->getIconSets()->getIconSetByHandle($value->iconSetHandle);

        if (!$iconSet) {
            return;
        }

        $iconSet->populateIcons();

        foreach ($iconSet->icons as $icon) {
            if ($icon->value !== $value->value) {
                continue;
            }

            $fromSet = (string)$icon->getDisplayValue();

            if ($fromSet !== '' && $fromSet !== (string)$value->value) {
                $value->setDisplayValue($fromSet);
            }

            return;
        }
    }
}
