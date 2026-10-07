<?php
namespace verbb\iconpicker\helpers;

use verbb\iconpicker\fields\IconPickerField;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Json;

use yii\web\ForbiddenHttpException;

class CpInputContext
{
    // Static Methods
    // =========================================================================

    public static function create(IconPickerField $field, ?ElementInterface $element): ?string
    {
        $request = Craft::$app->getRequest();
        $userId = Craft::$app->getUser()->getId();

        if ($request->getIsConsoleRequest() || !$request->getIsCpRequest() || !$userId || !$field->id) {
            return null;
        }

        return Craft::$app->getSecurity()->hashData(Json::encode([
            'userId' => $userId,
            'fieldId' => (int)$field->id,
            'elementType' => $element ? $element::class : null,
            'elementId' => $element?->id ? (int)$element->id : null,
            'siteId' => (int)($element?->siteId ?? Craft::$app->getSites()->getCurrentSite()->id),
            'expires' => time() + 86400,
        ]));
    }

    public static function validate(string $token, int $fieldId, int $siteId, ?int $elementId, ?string $elementType): void
    {
        $raw = Craft::$app->getSecurity()->validateData($token);
        $data = $raw === false ? null : Json::decodeIfJson($raw);

        if (!is_array($data)
            || ($data['userId'] ?? null) !== Craft::$app->getUser()->getId()
            || ($data['fieldId'] ?? null) !== $fieldId
            || ($data['elementType'] ?? null) !== $elementType
            || ($data['elementId'] ?? null) !== $elementId
            || ($data['siteId'] ?? null) !== $siteId
            || ($data['expires'] ?? 0) < time()) {
            throw new ForbiddenHttpException(Craft::t('icon-picker', 'Invalid or expired Icon Picker input context. Reload the editor.'));
        }
    }
}
