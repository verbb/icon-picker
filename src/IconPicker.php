<?php
namespace verbb\iconpicker;

use verbb\iconpicker\assetbundles\IconPickerCacheAsset;
use verbb\iconpicker\assetbundles\IconPickerRedactorAsset;
use verbb\iconpicker\base\PluginTrait;
use verbb\iconpicker\fields\IconPickerField;
use verbb\iconpicker\models\Settings;
use verbb\iconpicker\utilities\CacheUtility;
use verbb\iconpicker\variables\IconPickerVariable;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterCacheOptionsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\redactor\events\RegisterPluginPathsEvent;
use craft\redactor\Field as RichText;
use craft\services\Fields;
use craft\services\Utilities;
use craft\utilities\ClearCaches;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\web\View;

use yii\base\Event;
use yii\base\Module;

class IconPicker extends Plugin
{
    // Public Properties
    // =========================================================================

    public $schemaVersion = '1.0.1';
    public $hasCpSettings = true;


    // Traits
    // =========================================================================

    use PluginTrait;


    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->_setPluginComponents();
        $this->_setLogging();
        $this->_registerCpRoutes();
        $this->_registerVariables();
        $this->_registerFieldTypes();
        $this->_registerCacheTypes();
        $this->_registerUtilities();
        $this->_registerRedactorPlugins();

        // Provide a cache of loaded spritesheets for the CP
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            Craft::$app->getView()->registerAssetBundle(IconPickerCacheAsset::class);

            // Try to intelligently check to see if any icons have changed
            $this->getCache()->checkToInvalidate();
        }
    }

    public function getSettingsResponse()
    {
        Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('icon-picker/settings'));
    }


    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }


    // Private Methods
    // =========================================================================

    private function _registerCpRoutes()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'icon-picker/settings' => 'icon-picker/default/settings',
            ]);
        });
    }

    private function _registerVariables()
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $event->sender->set('iconPicker', IconPickerVariable::class);
        });
    }

    private function _registerFieldTypes()
    {
        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = IconPickerField::class;
        });
    }

    private function _registerCacheTypes()
    {
        Event::on(ClearCaches::class, ClearCaches::EVENT_REGISTER_CACHE_OPTIONS, function(RegisterCacheOptionsEvent $e) {
            $e->options[] = [
                'key' => 'icon-picker',
                'label' => Craft::t('icon-picker', 'Icon Picker cache'),
                'action' => [IconPicker::$plugin->getCache(), 'clearAndRegenerate'],
            ];
        });
    }

    private function _registerUtilities()
    {
        Event::on(Utilities::class, Utilities::EVENT_REGISTER_UTILITY_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = CacheUtility::class;
        });
    }

    private function _registerRedactorPlugins()
    {
        if (Craft::$app->getPlugins()->getPlugin('redactor') && $this->getSettings()->redactorFieldHandle) {
            Event::on(RichText::class, RichText::EVENT_REGISTER_PLUGIN_PATHS, function(RegisterPluginPathsEvent $event) {
                $event->paths[] = Craft::getAlias('@verbb/iconpicker/resources/dist/js');
        
                Craft::$app->getView()->registerAssetBundle(IconPickerRedactorAsset::class);
            });
        }
    }

}
