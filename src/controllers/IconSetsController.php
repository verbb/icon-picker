<?php
namespace verbb\iconpicker\controllers;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\base\IconSet;
use verbb\iconpicker\base\IconSetInterface;
// use verbb\iconpicker\helpers\Plugin;
use verbb\iconpicker\models\MissingIconSet;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use craft\web\Controller;

use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class IconSetsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        $iconSets = IconPicker::$plugin->getIconSets()->getAllIconSets();

        return $this->renderTemplate('icon-picker/settings/icon-sets', compact('iconSets'));
    }

    public function actionEdit(int $iconSetId = null, IconSetInterface $iconSet = null): Response
    {
        $iconSetsService = IconPicker::$plugin->getIconSets();

        $registeredIconSets = $iconSetsService->getRegisteredIconSets();

        $missingIconSetPlaceholder = null;

        if ($iconSet === null) {
            $firstIconSetType = ArrayHelper::firstValue($registeredIconSets);

            if ($iconSetId !== null) {
                $iconSet = $iconSetsService->getIconSetById($iconSetId);

                if ($iconSet === null) {
                    throw new NotFoundHttpException('Icon set not found');
                }

                if ($iconSet instanceof MissingIconSet) {
                    $missingIconSetPlaceholder = $iconSet->getPlaceholderHtml();
                    $iconSet = $iconSet->createFallback($firstIconSetType);
                }
            } else {
                $iconSet = $iconSetsService->createIconSet($firstIconSetType);
            }
        }

        // Make sure the selected iconSet class is in there
        if (!in_array(get_class($iconSet), $registeredIconSets, true)) {
            $registeredIconSets[] = get_class($iconSet);
        }

        $iconSetInstances = [];
        $iconSetTypeOptions = [];

        foreach ($registeredIconSets as $class) {
            $iconSetInstances[$class] = $iconSetsService->createIconSet($class);

            $iconSetTypeOptions[] = [
                'value' => $class,
                'label' => $class::displayName(),
            ];
        }

        // Sort them by name
        ArrayHelper::multisort($iconSetTypeOptions, 'label', SORT_ASC, SORT_NATURAL | SORT_FLAG_CASE);

        $isNewIconSet = !$iconSet->id;

        if ($isNewIconSet) {
            $title = Craft::t('icon-picker', 'Create a new icon set');
        } else {
            $title = trim($iconSet->name) ?: Craft::t('icon-picker', 'Edit icon set');
        }

        $baseUrl = 'icon-picker/settings/icon-sets';
        $continueEditingUrl = 'icon-picker/settings/icon-sets/edit/{id}';

        return $this->renderTemplate('icon-picker/settings/icon-sets/_edit', [
            'iconSet' => $iconSet,
            'isNewIconSet' => $isNewIconSet,
            'iconSetTypes' => $registeredIconSets,
            'iconSetTypeOptions' => $iconSetTypeOptions,
            'missingIconSetPlaceholder' => $missingIconSetPlaceholder,
            'iconSetInstances' => $iconSetInstances,
            'baseUrl' => $baseUrl,
            'continueEditingUrl' => $continueEditingUrl,
            'title' => $title,
        ]);
    }

    public function actionSave(): ?Response
    {
        $savedIconSet = null;
        $this->requirePostRequest();

        $iconSetsService = IconPicker::$plugin->getIconSets();
        $type = $this->request->getParam('type');
        $iconSetId = (int)$this->request->getParam('id');

        $settings = $this->request->getParam('types.' . $type, []);

        if ($iconSetId) {
            $savedIconSet = $iconSetsService->getIconSetById($iconSetId);

            if (!$savedIconSet) {
                throw new BadRequestHttpException("Invalid icon set ID: $iconSetId");
            }

            // Be sure to merge with any existing settings, but make sure we also check if it's the same
            // type. If we're changing the type of icon set, that would bleed incorrect settings
            // Have we changed type? Wipe the settings
            if ($type === get_class($savedIconSet)) {
                // Be sure to merge with any existing settings
                $settings = array_merge($savedIconSet->settings, $settings);
            }
        }

        $iconSetData = [
            'id' => $iconSetId ?: null,
            'name' => $this->request->getParam('name'),
            'handle' => $this->request->getParam('handle'),
            'type' => $type,
            'sortOrder' => $savedIconSet->sortOrder ?? null,
            'enabled' => (bool)$this->request->getParam('enabled'),
            'settings' => $settings,
            'uid' => $savedIconSet->uid ?? null,
        ];

        $iconSet = $iconSetsService->createIconSet($iconSetData);

        if (!$iconSetsService->saveIconSet($iconSet)) {
            $this->setFailFlash(Craft::t('icon-picker', 'Couldn’t save icon set.'));

            // Send the iconSet back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'iconSet' => $iconSet,
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('icon-picker', 'Icon set saved.'));

        // Re-generate the cache for this icon set
        IconPicker::$plugin->getService()->clearAndRegenerateCache([$iconSet]);

        return $this->redirectToPostedUrl($iconSet);
    }

    public function actionReorder(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $iconSetsIds = Json::decode($this->request->getRequiredParam('ids'));
        IconPicker::$plugin->getIconSets()->reorderIconSets($iconSetsIds);

        return $this->asJson(['success' => true]);
    }

    public function actionDelete(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $iconSetsId = $request->getRequiredParam('id');

        IconPicker::$plugin->getIconSets()->deleteIconSetById($iconSetsId);

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
            ]);
        }

        $this->setSuccessFlash(Craft::t('icon-picker', 'Icon set deleted.'));

        return $this->redirectToPostedUrl();
    }

}
