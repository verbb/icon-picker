<?php
namespace verbb\iconpicker;

use verbb\iconpicker\assetbundles\IconPickerCacheAsset;
use verbb\iconpicker\assetbundles\IconPickerRedactorAsset;
use verbb\iconpicker\base\PluginTrait;
use verbb\iconpicker\fields\IconPickerField;
use verbb\iconpicker\helpers\ProjectConfigHelper;
use verbb\iconpicker\integrations\feedme\fields\IconPicker as FeedMeIconPickerField;
use verbb\iconpicker\models\Settings;
use verbb\iconpicker\services\IconSets;
use verbb\iconpicker\utilities\CacheUtility;
use verbb\iconpicker\variables\IconPickerVariable;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RebuildConfigEvent;
use craft\events\RegisterCacheOptionsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\services\Fields;
use craft\services\ProjectConfig;
use craft\services\Utilities;
use craft\utilities\ClearCaches;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;

use yii\base\Event;

use craft\redactor\events\RegisterPluginPathsEvent;
use craft\redactor\Field as RichText;

use craft\feedme\events\RegisterFeedMeFieldsEvent;
use craft\feedme\services\Fields as FeedMeFields;

class IconPicker extends Plugin
{
    // Properties
    // =========================================================================

    public bool $hasCpSettings = true;
    public string $schemaVersion = '1.2.3';
    public string $minVersionRequired = '1.1.12';


    // Traits
    // =========================================================================

    use PluginTrait;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        $this->_registerComponents();
        $this->_registerLogTarget();
        $this->_registerVariables();
        $this->_registerFieldTypes();
        $this->_registerCacheTypes();
        $this->_registerProjectConfigEventListeners();
        $this->_registerThirdPartyEventListeners();

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_registerCpRoutes();
            $this->_registerUtilities();
            $this->_registerRedactorPlugins();
            
            // Provide a cache of loaded spritesheets for the CP
            Craft::$app->getView()->registerAssetBundle(IconPickerCacheAsset::class);

            // Try to intelligently check to see if any icons have changed
            $this->getService()->checkToInvalidateCache();
        }
    }

    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('icon-picker/settings'));
    }


    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }


    // Private Methods
    // =========================================================================

    private function _registerCpRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['icon-picker'] = 'icon-picker/settings';
            $event->rules['icon-picker/settings'] = 'icon-picker/settings';
            $event->rules['icon-picker/settings/general'] = 'icon-picker/settings';
            $event->rules['icon-picker/settings/icon-sets'] = 'icon-picker/icon-sets';
            $event->rules['icon-picker/settings/icon-sets/new'] = 'icon-picker/icon-sets/edit';
            $event->rules['icon-picker/settings/icon-sets/edit/<iconSetId:\d+>'] = 'icon-picker/icon-sets/edit';
        });
    }

    private function _registerVariables(): void
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $event->sender->set('iconPicker', IconPickerVariable::class);
        });
    }

    private function _registerFieldTypes(): void
    {
        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = IconPickerField::class;
        });
    }

    private function _registerCacheTypes(): void
    {
        Event::on(ClearCaches::class, ClearCaches::EVENT_REGISTER_CACHE_OPTIONS, function(RegisterCacheOptionsEvent $event) {
            $event->options[] = [
                'key' => 'icon-picker',
                'label' => Craft::t('icon-picker', 'Icon Picker cache'),
                'action' => [IconPicker::$plugin->getService(), 'clearAndRegenerateCache'],
            ];
        });
    }

    private function _registerUtilities(): void
    {
        Event::on(Utilities::class, Utilities::EVENT_REGISTER_UTILITY_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = CacheUtility::class;
        });
    }

    private function _registerProjectConfigEventListeners(): void
    {
        $projectConfigService = Craft::$app->getProjectConfig();

        $iconSetsService = $this->getIconSets();
        $projectConfigService
            ->onAdd(IconSets::CONFIG_ICON_SETS_KEY . '.{uid}', [$iconSetsService, 'handleChangedIconSet'])
            ->onUpdate(IconSets::CONFIG_ICON_SETS_KEY . '.{uid}', [$iconSetsService, 'handleChangedIconSet'])
            ->onRemove(IconSets::CONFIG_ICON_SETS_KEY . '.{uid}', [$iconSetsService, 'handleDeletedIconSet']);

        Event::on(ProjectConfig::class, ProjectConfig::EVENT_REBUILD, function(RebuildConfigEvent $event) {
            $event->config['icon-picker'] = ProjectConfigHelper::rebuildProjectConfig();
        });
    }

    private function _registerRedactorPlugins(): void
    {
        if (class_exists(RichText::class)) {
            Event::on(RichText::class, RichText::EVENT_REGISTER_PLUGIN_PATHS, function(RegisterPluginPathsEvent $event) {
                $event->paths[] = Craft::getAlias('@verbb/iconpicker/resources/dist/js');

                Craft::$app->getView()->registerAssetBundle(IconPickerRedactorAsset::class);
            });
        }
    }

    private function _registerThirdPartyEventListeners(): void
    {
        if (class_exists(FeedMeFields::class)) {
            Event::on(FeedMeFields::class, FeedMeFields::EVENT_REGISTER_FEED_ME_FIELDS, function(RegisterFeedMeFieldsEvent $event) {
                $event->fields[] = FeedMeIconPickerField::class;
            });
        }
    }

}
