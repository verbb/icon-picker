<?php
namespace verbb\iconpicker\services;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\base\IconSet;
use verbb\iconpicker\base\IconSetInterface;
use verbb\iconpicker\events\IconSetEvent;
use verbb\iconpicker\events\RegisterIconSetsEvent;
use verbb\iconpicker\iconsets as registerediconsets;
use verbb\iconpicker\models\MissingIconSet;
use verbb\iconpicker\records\IconSet as IconSetRecord;

use Craft;
use craft\base\Field;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\errors\MissingComponentException;
use craft\events\ConfigEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;

use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\UnknownPropertyException;
use yii\db\ActiveRecord;
use yii\db\Exception;

use Throwable;

class IconSets extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_REGISTER_ICON_SETS = 'registerIconSets';
    public const EVENT_BEFORE_SAVE_ICON_SET = 'beforeSaveIconSet';
    public const EVENT_AFTER_SAVE_ICON_SET = 'afterSaveIconSet';
    public const EVENT_BEFORE_DELETE_ICON_SET = 'beforeDeleteIconSet';
    public const EVENT_BEFORE_APPLY_ICON_SET_DELETE = 'beforeApplyIconSetDelete';
    public const EVENT_AFTER_DELETE_ICON_SET = 'afterDeleteIconSet';
    public const CONFIG_ICON_SETS_KEY = 'icon-picker.icon-sets';


    // Properties
    // =========================================================================

    private ?MemoizableArray $_iconSets = null;
    private ?array $_iconSetsByType = null;
    private ?array $_preloadedIconSets = null;


    // Public Methods
    // =========================================================================

    public function getRegisteredIconSets(): array
    {
        $iconSets = [
            registerediconsets\SvgFolder::class,
            registerediconsets\SvgSprite::class,
            registerediconsets\WebFont::class,
            registerediconsets\FontAwesome::class,
            registerediconsets\Ionicons::class,
            registerediconsets\Feather::class,
            registerediconsets\CssGg::class,
            registerediconsets\MaterialSymbols::class,
        ];

        $event = new RegisterIconSetsEvent([
            'iconSets' => $iconSets,
        ]);

        $this->trigger(self::EVENT_REGISTER_ICON_SETS, $event);

        return $event->iconSets;
    }

    public function getAllIconSets(): array
    {
        return $this->_iconSets()->all();
    }

    public function getAllEnabledIconSets(): array
    {
        return ArrayHelper::where($this->getAllIconSets(), 'enabled', true);
    }

    public function getIconSetById(int $iconSetId): ?IconSetInterface
    {
        return ArrayHelper::firstWhere($this->getAllIconSets(), 'id', $iconSetId);
    }

    public function getIconSetByUid(string $iconSetUid): ?IconSetInterface
    {
        return ArrayHelper::firstWhere($this->getAllIconSets(), 'uid', $iconSetUid);
    }

    public function getIconSetByHandle(string $handle): ?IconSetInterface
    {
        return ArrayHelper::firstWhere($this->getAllIconSets(), 'handle', $handle, true);
    }

    public function getIconSetsForField(Field $field): array
    {
        $allIconSets = $this->getAllEnabledIconSets();

        if ($field->iconSets === '' || $field->iconSets === null) {
            return [];
        }

        // For each enabled icon set, generate a cache
        if ($field->iconSets === '*') {
            return $allIconSets;
        }

        $iconSets = [];

        foreach ($allIconSets as $allIconSet) {
            if (in_array($allIconSet->uid, $field->iconSets)) {
                $iconSets[] = $allIconSet;
            }
        }

        return $iconSets;
    }

    public function getPreloadedIconSet(string $key): ?IconSet
    {
        return $this->_preloadedIconSets[$key] ?? null;
    }

    public function setPreloadedIconSet(string $key, IconSet $value): void
    {
        $this->_preloadedIconSets[$key] = $value;
    }

    public function createIconSetConfig(IconSetInterface $iconSet): array
    {
        return [
            'name' => $iconSet->name,
            'handle' => $iconSet->handle,
            'type' => get_class($iconSet),
            'enabled' => $iconSet->getEnabled(false),
            'sortOrder' => (int)$iconSet->sortOrder,
            'settings' => ProjectConfigHelper::packAssociativeArrays($iconSet->getSettings()),
        ];
    }

    public function saveIconSet(IconSetInterface $iconSet, bool $runValidation = true): bool
    {
        $isNewIconSet = $iconSet->getIsNew();

        // Fire a 'beforeSaveIconSet' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_ICON_SET)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_ICON_SET, new IconSetEvent([
                'iconSet' => $iconSet,
                'isNew' => $isNewIconSet,
            ]));
        }

        if (!$iconSet->beforeSave($isNewIconSet)) {
            return false;
        }

        if ($runValidation && !$iconSet->validate()) {
            IconPicker::log('Icon set not saved due to validation error.');

            return false;
        }

        if ($isNewIconSet) {
            $iconSet->uid = StringHelper::UUID();
            
            $iconSet->sortOrder = (new Query())
                    ->from(['{{%iconpicker_iconsets}}'])
                    ->max('[[sortOrder]]') + 1;
        } else if (!$iconSet->uid) {
            $iconSet->uid = Db::uidById('{{%iconpicker_iconsets}}', $iconSet->id);
        }

        $configPath = self::CONFIG_ICON_SETS_KEY . '.' . $iconSet->uid;
        $configData = $this->createIconSetConfig($iconSet);
        Craft::$app->getProjectConfig()->set($configPath, $configData, "Save the “{$iconSet->handle}” icon set");

        if ($isNewIconSet) {
            $iconSet->id = Db::idByUid('{{%iconpicker_iconsets}}', $iconSet->uid);
        }

        return true;
    }

    public function handleChangedIconSet(ConfigEvent $event): void
    {
        $iconSetUid = $event->tokenMatches[0];
        $data = $event->newValue;

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $iconSetRecord = $this->_getIconSetRecord($iconSetUid, true);
            $isNewIconSet = $iconSetRecord->getIsNewRecord();

            $settings = $data['settings'] ?? [];

            $iconSetRecord->name = $data['name'];
            $iconSetRecord->handle = $data['handle'];
            $iconSetRecord->type = $data['type'];
            $iconSetRecord->enabled = $data['enabled'];
            $iconSetRecord->sortOrder = $data['sortOrder'];
            $iconSetRecord->settings = ProjectConfigHelper::unpackAssociativeArrays($settings);
            $iconSetRecord->uid = $iconSetUid;

            // Save the iconSet
            if ($wasTrashed = (bool)$iconSetRecord->dateDeleted) {
                $iconSetRecord->restore();
            } else {
                $iconSetRecord->save(false);
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_iconSets = null;

        $iconSet = $this->getIconSetById($iconSetRecord->id);
        $iconSet->afterSave($isNewIconSet);

        // Fire an 'afterSaveIconSet' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_ICON_SET)) {
            $this->trigger(self::EVENT_AFTER_SAVE_ICON_SET, new IconSetEvent([
                'iconSet' => $this->getIconSetById($iconSetRecord->id),
                'isNew' => $isNewIconSet,
            ]));
        }
    }

    public function reorderIconSets(array $iconSetIds): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        $uidsByIds = Db::uidsByIds('{{%iconpicker_iconsets}}', $iconSetIds);

        foreach ($iconSetIds as $iconSetOrder => $iconSetId) {
            if (!empty($uidsByIds[$iconSetId])) {
                $iconSetUid = $uidsByIds[$iconSetId];
                $projectConfig->set(self::CONFIG_ICON_SETS_KEY . '.' . $iconSetUid . '.sortOrder', $iconSetOrder + 1, "Reorder icon sets");
            }
        }

        return true;
    }

    public function createIconSet(mixed $config): IconSetInterface
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        if (isset($config['settings']) && is_string($config['settings'])) {
            $config['settings'] = Json::decode($config['settings']);
        }

        try {
            $iconSet = ComponentHelper::createComponent($config, IconSetInterface::class);
        } catch (UnknownPropertyException $e) {
            throw $e;
        } catch (MissingComponentException $e) {
            $config['errorMessage'] = $e->getMessage();
            $config['expectedType'] = $config['type'];
            unset($config['type']);

            $iconSet = new MissingIconSet($config);
        }

        return $iconSet;
    }

    public function deleteIconSetById(int $iconSetId): bool
    {
        $iconSet = $this->getIconSetById($iconSetId);

        if (!$iconSet) {
            return false;
        }

        return $this->deleteIconSet($iconSet);
    }

    public function deleteIconSet(IconSetInterface $iconSet): bool
    {
        // Fire a 'beforeDeleteIconSet' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_ICON_SET)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_ICON_SET, new IconSetEvent([
                'iconSet' => $iconSet,
            ]));
        }

        if (!$iconSet->beforeDelete()) {
            return false;
        }

        Craft::$app->getProjectConfig()->remove(self::CONFIG_ICON_SETS_KEY . '.' . $iconSet->uid, "Delete the “{$iconSet->handle}” icon set");

        return true;
    }

    public function handleDeletedIconSet(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        $iconSetRecord = $this->_getIconSetRecord($uid);

        if ($iconSetRecord->getIsNewRecord()) {
            return;
        }

        $iconSet = $this->getIconSetById($iconSetRecord->id);

        // Fire a 'beforeApplyIconSetDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_ICON_SET_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_ICON_SET_DELETE, new IconSetEvent([
                'iconSet' => $iconSet,
            ]));
        }

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            $iconSet->beforeApplyDelete();

            // Delete the iconSet
            $db->createCommand()
                ->softDelete('{{%iconpicker_iconsets}}', ['id' => $iconSetRecord->id])
                ->execute();

            $iconSet->afterDelete();

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_iconSets = null;

        // Fire an 'afterDeleteIconSet' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_ICON_SET)) {
            $this->trigger(self::EVENT_AFTER_DELETE_ICON_SET, new IconSetEvent([
                'iconSet' => $iconSet,
            ]));
        }
    }

    // Private Methods
    // =========================================================================

    private function _iconSets(): MemoizableArray
    {
        if (!isset($this->_iconSets)) {
            $iconSets = [];

            foreach ($this->_createIconSetQuery()->all() as $result) {
                $iconSets[] = $this->createIconSet($result);
            }

            $this->_iconSets = new MemoizableArray($iconSets);
        }

        return $this->_iconSets;
    }

    private function _createIconSetQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'type',
                'enabled',
                'sortOrder',
                'settings',
                'dateCreated',
                'dateUpdated',
                'uid',
            ])
            ->from(['{{%iconpicker_iconsets}}'])
            ->where(['dateDeleted' => null])
            ->orderBy(['sortOrder' => SORT_ASC]);
    }

    private function _getIconSetRecord(string $uid, bool $withTrashed = false): IconSetRecord
    {
        $query = $withTrashed ? IconSetRecord::findWithTrashed() : IconSetRecord::find();
        $query->andWhere(['uid' => $uid]);

        return $query->one() ?? new IconSetRecord();
    }
}