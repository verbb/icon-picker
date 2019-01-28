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

    public static function supportedTranslationMethods(): array
    {
        return [];
    }


    // Properties
    // =========================================================================

    public $columnType = Schema::TYPE_TEXT;
    public $iconSets;


    // Public Methods
    // =========================================================================

    public function getInputHtml($value, ElementInterface $element = null): string
    {
        if (!$value) {
            $value = new IconModel();
        }

        $id = Craft::$app->getView()->formatInputId($this->handle);
        $nameSpacedId = Craft::$app->getView()->namespaceInputId($id);

        $settings = IconPicker::$plugin->getSettings();

        Craft::$app->getView()->registerAssetBundle(IconPickerAsset::class);

        Craft::$app->getView()->registerJs('new Craft.IconPicker.Input(' . json_encode([
            'id' => $id,
            'inputId' => $nameSpacedId,
            'name' => $this->handle,
        ]) . ');');

        $options = IconPicker::$plugin->getService()->getIcons($this->iconSets);

        return Craft::$app->getView()->renderTemplate('icon-picker/_field/input', [
            'id' => $id,
            'name' => $this->handle,
            'namespaceId' => $nameSpacedId,
            'value' => $value,
            'options' => $options,
        ]);
    }

    public function getSettingsHtml()
    {
        $settings = IconPicker::getInstance()->getSettings();

        $iconSetsPath = $settings->iconSetsPath;
        $iconSets = [];
        $errors = [];

        if (is_dir($iconSetsPath)) {
            $folders = FileHelper::findDirectories($iconSetsPath, [
                'recursive' => false,
            ]);
            
            foreach ($folders as $folder) {
                $iconSets[$folder] = str_replace($iconSetsPath, '', $folder);
            }
        } else {
            $errors[] = '<p><strong class="warning">Unable to locate SVG Icons source directory.</strong><br>Please ensure the directory <code>' . $iconSetsPath . '</code> exists.</p>';
        }

        return Craft::$app->getView()->renderTemplate('icon-picker/_field/settings', [
            'settings' => $this->getSettings(),
            'iconSets' => $iconSets,
            'errors' => $errors,
        ]);
    }

    public function normalizeValue($value, ElementInterface $element = null)
    {
        if ($value === null) {
            return new IconModel();
        }

        if (is_string($value)) {
            $value = Json::decodeIfJson($value);
        }

        return $value;
    }
}
