<?php
namespace verbb\iconpicker\fields;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\assetbundles\IconPickerAsset;
use verbb\iconpicker\models\IconModel;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;

use yii\db\Schema;

class IconPickerField extends Field
{
    // Static
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('icon-picker', 'Icon Picker');
    }


    // Properties
    // =========================================================================

    public $columnType = Schema::TYPE_TEXT;
    public $showLabels = false;
    public $iconSets;
    public $remoteSets = [];


    // Public Methods
    // =========================================================================

    public function getInputHtml($value, ElementInterface $element = null): string
    {
        if (!$value) {
            $value = new IconModel();
        }

        $id = Craft::$app->getView()->formatInputId($this->handle);
        $nameSpacedId = Craft::$app->getView()->namespaceInputId($id);

        $pluginSettings = IconPicker::$plugin->getSettings();

        Craft::$app->getView()->registerAssetBundle(IconPickerAsset::class);

        $iconSets = IconPicker::$plugin->getService()->getIcons($this->iconSets, $this->remoteSets);

        $spriteSheets = IconPicker::$plugin->getService()->getSpriteSheets();
        $fonts = IconPicker::$plugin->getService()->getLoadedFonts();

        $settings = array_merge($this->settings, $pluginSettings->toArray());

        Craft::$app->getView()->registerJs('new Craft.IconPicker.Input(' . json_encode([
            'id' => $id,
            'inputId' => $nameSpacedId,
            'name' => $this->handle,
            'fonts' => $fonts,
            'spriteSheets' => $spriteSheets,
            'settings' => $settings,
        ]) . ');');

        return Craft::$app->getView()->renderTemplate('icon-picker/_field/input', [
            'id' => $id,
            'name' => $this->handle,
            'namespaceId' => $nameSpacedId,
            'value' => $value,
            'iconSets' => $iconSets,
            'showLabels' => $this->showLabels,
        ]);
    }

    public function getSettingsHtml()
    {
        $settings = IconPicker::getInstance()->getSettings();
        $iconSetsPath = $settings->iconSetsPath;

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
            'settings' => $this->getSettings(),
            'iconSets' => $iconSets,
            'remoteSets' => $remoteSets,
            'errors' => $errors,
        ]);
    }

    public function normalizeValue($value, ElementInterface $element = null)
    {
        if ($value instanceof IconModel) {
            return $value;
        }

        $model = new IconModel();            

        if (is_string($value) && !empty($value)) {
            $value = Json::decodeIfJson($value);
        }

        if (is_array($value)) {
            $model->setAttributes($value, false);
        }

        return $model;
    }

    public function serializeValue($value, ElementInterface $element = null)
    {
        // If saving a sprite, we need to sort out the type - although easier than front-end input changing.
        if (strstr($value['icon'], 'sprite:')) {
            $explode = explode(':', $value['icon']);

            $value['icon'] = null;
            $value['type'] = $explode[0];
            $value['iconSet'] = $explode[1];
            $value['sprite'] = $explode[2];
        }

        if (strstr($value['icon'], 'glyph:')) {
            $explode = explode(':', $value['icon']);

            $value['icon'] = null;
            $value['type'] = $explode[0];
            $value['iconSet'] = $explode[1];
            $value['glyphId'] = $explode[2];
            $value['glyphName'] = $explode[3];
        }

        if (strstr($value['icon'], 'css:')) {
            $explode = explode(':', $value['icon']);

            $value['icon'] = null;
            $value['type'] = $explode[0];
            $value['iconSet'] = $explode[1];
            $value['css'] = $explode[2];
        }

        return $value;
    }
}
