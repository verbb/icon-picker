/** Seed a local SVG icon set and an Icon Picker field for the feature screenshot. */

use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use verbb\iconpicker\IconPicker;
use verbb\iconpicker\fields\IconPickerField;
use verbb\iconpicker\iconsets\SvgFolder;

$iconDirectory = Craft::getAlias('@webroot/icon-picker/screenshots');
FileHelper::createDirectory($iconDirectory);

$icons = [
    'bars', 'bell', 'bookmark', 'calendar', 'camera', 'check', 'clock', 'cloud', 'code', 'compass',
    'download', 'eye', 'file', 'filter', 'flag', 'folder', 'gear', 'heart', 'image', 'link',
    'list', 'lock', 'map', 'message', 'music', 'phone', 'share', 'star', 'tag', 'trash',
    'upload', 'user', 'users', 'video',
];
$craftIconDirectory = CRAFT_VENDOR_PATH . '/craftcms/cms/src/icons/solid';

foreach ($icons as $name) {
    copy($craftIconDirectory . DIRECTORY_SEPARATOR . $name . '.svg', $iconDirectory . DIRECTORY_SEPARATOR . $name . '.svg');
}

$iconSets = IconPicker::$plugin->getIconSets();
$iconSet = $iconSets->getIconSetByHandle('screenshotIcons');

if (!$iconSet) {
    $iconSet = new SvgFolder([
        'name' => 'Interface icons',
        'handle' => 'screenshotIcons',
        'enabled' => true,
        'folder' => 'screenshots',
    ]);

    if (!$iconSets->saveIconSet($iconSet)) {
        throw new RuntimeException('Unable to save screenshot icon set: ' . Json::encode($iconSet->getErrors()));
    }
}

$fields = Craft::$app->getFields();
$entries = Craft::$app->getEntries();
$site = Craft::$app->getSites()->getPrimarySite();
$fieldHandle = 'docsScreenshotIcon';
$sectionHandle = 'docsScreenshotIconPicker';
$field = $fields->getFieldByHandle($fieldHandle);

if (!$field instanceof IconPickerField) {
    $field = new IconPickerField([
        'name' => 'Icon Picker',
        'handle' => $fieldHandle,
        'iconSets' => '*',
        'showLabels' => false,
    ]);

    if (!$fields->saveField($field)) {
        throw new RuntimeException('Unable to save Icon Picker field: ' . Json::encode($field->getErrors()));
    }
}

IconPicker::$plugin->getService()->clearAndRegenerateCache([$iconSet]);
$section = $entries->getSectionByHandle($sectionHandle);

if (!$section) {
    $entryType = new EntryType(['name' => 'Icon Picker showcase', 'handle' => $sectionHandle . 'Type', 'hasTitleField' => true]);
    $layout = new FieldLayout(['type' => Entry::class]);
    $tab = new FieldLayoutTab(['name' => Craft::t('app', 'Content'), 'layout' => $layout]);
    $tab->setElements([new CustomField($field)]);
    $layout->setTabs([$tab]);
    $entryType->setFieldLayout($layout);

    if (!$entries->saveEntryType($entryType)) {
        throw new RuntimeException('Unable to save Icon Picker entry type: ' . Json::encode($entryType->getErrors()));
    }

    $section = new Section(['name' => 'Icon Picker showcase', 'handle' => $sectionHandle, 'type' => Section::TYPE_CHANNEL]);
    $section->setEntryTypes([$entryType]);
    $section->setSiteSettings([new Section_SiteSettings(['siteId' => $site->id, 'enabledByDefault' => true, 'hasUrls' => false])]);

    if (!$entries->saveSection($section)) {
        throw new RuntimeException('Unable to save Icon Picker section: ' . Json::encode($section->getErrors()));
    }
}

$entryType = $entries->getEntryTypesBySectionId($section->id)[0] ?? null;
$entry = Entry::find()->sectionId($section->id)->siteId($site->id)->status(null)->one();

if (!$entry) {
    $entry = new Entry(['sectionId' => $section->id, 'typeId' => $entryType->id, 'siteId' => $site->id, 'slug' => 'icon-picker-showcase', 'enabled' => true]);
}

$entry->title = 'Icon Picker showcase';

if (!Craft::$app->getElements()->saveElement($entry)) {
    throw new RuntimeException('Unable to save Icon Picker entry: ' . Json::encode($entry->getErrors()));
}

echo Json::encode([
    'entryEditRoute' => parse_url((string)$entry->getCpEditUrl(), PHP_URL_PATH),
    'iconSetRoute' => '/admin/icon-picker/settings/icon-sets/edit/' . $iconSet->id,
], JSON_THROW_ON_ERROR);
