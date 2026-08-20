/**
 * Seed an Icon Picker field + a section/entry for docs screenshots.
 *
 * Echoes JSON: fieldId, fieldHandle, settingsRoute, entryEditRoute.
 * Note: no opening PHP tag — @verbb/docs-screenshots injects this into a bootstrap.
 *
 * This is a starter seed: it creates the field with defaults so the field-settings and
 * entry-edit routes resolve. Tune the field settings (icon sets, remote sets, etc.) here
 * as the Phase 1 field UI lands, mirroring hyper/seed-docs-field.php for richer scenes.
 */

use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\Json;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\models\Site;
use verbb\iconpicker\fields\IconPickerField;

const DOCS_FIELD_HANDLE = 'docsScreenshotIconPicker';
const DOCS_SECTION_HANDLE = 'docsScreenshotIconPicker';
const DOCS_ENTRY_HANDLE = 'docs-screenshot-entry';

function docsScreenshotSite(): Site
{
    return Craft::$app->getSites()->getPrimarySite();
}

function docsScreenshotAdminPath(): string
{
    return Craft::$app->getConfig()->getGeneral()->cpTrigger ?: 'admin';
}

function docsScreenshotSection(string $handle, string $name): Section
{
    $entriesService = Craft::$app->getEntries();
    $section = $entriesService->getSectionByHandle($handle);
    $site = docsScreenshotSite();

    if (!$section) {
        $entryType = new EntryType([
            'name' => $name,
            'handle' => $handle . 'Type',
            'hasTitleField' => true,
        ]);

        if (!$entriesService->saveEntryType($entryType)) {
            throw new RuntimeException('Unable to save entry type: ' . Json::encode($entryType->getErrors()));
        }

        $section = new Section([
            'name' => $name,
            'handle' => $handle,
            'type' => Section::TYPE_CHANNEL,
        ]);
        $section->setEntryTypes([$entryType]);
        $section->setSiteSettings([
            new Section_SiteSettings([
                'siteId' => $site->id,
                'enabledByDefault' => true,
                'hasUrls' => false,
            ]),
        ]);

        if (!$entriesService->saveSection($section)) {
            throw new RuntimeException('Unable to save section: ' . Json::encode($section->getErrors()));
        }

        $section = $entriesService->getSectionByHandle($handle);
    }

    if (!$section) {
        throw new RuntimeException("Section `{$handle}` could not be reloaded.");
    }

    return $section;
}

function docsScreenshotIconPickerField(): IconPickerField
{
    $fields = Craft::$app->getFields();
    $existing = $fields->getFieldByHandle(DOCS_FIELD_HANDLE);

    $field = $existing instanceof IconPickerField ? $existing : new IconPickerField([
        'handle' => DOCS_FIELD_HANDLE,
    ]);

    $field->name = 'Icon';
    $field->instructions = 'Pick an icon from the available sets.';

    if (!$fields->saveField($field)) {
        throw new RuntimeException('Unable to save Icon Picker field: ' . Json::encode($field->getErrors()));
    }

    $saved = $fields->getFieldByHandle(DOCS_FIELD_HANDLE);

    if (!$saved instanceof IconPickerField) {
        throw new RuntimeException('Icon Picker field could not be reloaded.');
    }

    return $saved;
}

function docsScreenshotAttachField(Section $section, IconPickerField $field): void
{
    $entriesService = Craft::$app->getEntries();
    $entryType = $entriesService->getEntryTypesBySectionId($section->id)[0] ?? null;

    if (!$entryType) {
        throw new RuntimeException("Section `{$section->handle}` has no entry types.");
    }

    $layout = $entryType->getFieldLayout() ?? new FieldLayout(['type' => Entry::class]);
    $tabs = $layout->getTabs();

    if (!$tabs) {
        $tabs = [new FieldLayoutTab(['name' => Craft::t('app', 'Content'), 'layout' => $layout])];
    }

    $tab = $tabs[0];
    $elements = array_values(array_filter(
        $tab->getElements(),
        static fn($element) => !($element instanceof CustomField && $element->getField()?->id === $field->id),
    ));
    $elements[] = new CustomField($field);
    $tab->setElements($elements);
    $layout->setTabs($tabs);
    $entryType->setFieldLayout($layout);

    if (!$entriesService->saveEntryType($entryType)) {
        throw new RuntimeException('Unable to attach Icon Picker field: ' . Json::encode($entryType->getErrors()));
    }
}

function docsScreenshotUpsertEntry(Section $section, string $slug, string $title): Entry
{
    $site = docsScreenshotSite();
    $entryType = Craft::$app->getEntries()->getEntryTypesBySectionId($section->id)[0] ?? null;

    if (!$entryType) {
        throw new RuntimeException("Section `{$section->handle}` has no entry types.");
    }

    $entry = Entry::find()->sectionId($section->id)->slug($slug)->siteId($site->id)->status(null)->one();

    if (!$entry) {
        $entry = new Entry([
            'sectionId' => $section->id,
            'typeId' => $entryType->id,
            'siteId' => $site->id,
            'slug' => $slug,
            'enabled' => true,
        ]);
    }

    $entry->title = $title;
    $entry->enabled = true;

    if (!Craft::$app->getElements()->saveElement($entry)) {
        throw new RuntimeException('Unable to save entry: ' . Json::encode($entry->getErrors()));
    }

    return $entry;
}

$adminPath = docsScreenshotAdminPath();
$section = docsScreenshotSection(DOCS_SECTION_HANDLE, 'Icon Picker Demo');
$field = docsScreenshotIconPickerField();
docsScreenshotAttachField($section, $field);

$entry = docsScreenshotUpsertEntry($section, DOCS_ENTRY_HANDLE, 'Demo');

$entryEditUrl = $entry->getCpEditUrl();
$entryEditPath = parse_url((string)$entryEditUrl, PHP_URL_PATH)
    ?: "/{$adminPath}/entries/{$section->handle}/{$entry->id}-{$entry->slug}";

echo Json::encode([
    'fieldId' => (int)$field->id,
    'fieldHandle' => $field->handle,
    'settingsRoute' => "/{$adminPath}/settings/fields/edit/{$field->id}",
    'entryEditRoute' => $entryEditPath,
], JSON_THROW_ON_ERROR);
