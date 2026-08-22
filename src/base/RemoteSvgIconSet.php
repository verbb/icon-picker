<?php
namespace verbb\iconpicker\base;

use verbb\iconpicker\models\Icon;

use Craft;
use craft\helpers\Json;

/**
 * Icon set backed by a name catalog and CDN SVG URLs (no local icon files).
 */
abstract class RemoteSvgIconSet extends IconSet
{
    // Properties
    // =========================================================================

    public ?string $cdnVersion = null;

    /** @var string[]|null */
    public ?array $variants = ['*'];


    // Public Methods
    // =========================================================================

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('icon-picker/icon-sets/_remote-package', [
            'iconSet' => $this,
            'defaultVersion' => $this->defaultVersion(),
            'variantSettingsHtml' => $this->getVariantSettingsHtml(),
        ]);
    }

    public function fetchIcons(): void
    {
        $icons = [];
        $catalogMap = $this->catalogMap();
        $selected = [];

        foreach ($this->variants ?? ['*'] as $variant) {
            if ($variant === '*') {
                $selected = $catalogMap;
                break;
            }

            if (isset($catalogMap[$variant])) {
                $selected[$variant] = $catalogMap[$variant];
            }
        }

        foreach ($selected as $variant => $filename) {
            $path = __DIR__ . '/../json/' . $filename;

            if (!file_exists($path)) {
                continue;
            }

            $json = Json::decode(file_get_contents($path));

            foreach ($json as $row) {
                $row['variant'] = $variant;
                $icons[] = $row;
            }
        }

        usort($icons, fn($a, $b) => strcmp($a['label'] ?? '', $b['label'] ?? ''));

        foreach ($icons as $row) {
            $label = $row['label'] ?? null;

            if (!$label) {
                continue;
            }

            $variant = $row['variant'] ?? $this->defaultVariant();

            $this->icons[] = new Icon([
                'type' => Icon::TYPE_SVG,
                'iconSetHandle' => $this->handle,
                'iconSet' => $variant,
                'value' => $label,
                'label' => $label,
                'keywords' => $row['keywords'] ?? $label,
            ]);
        }
    }

    public function resolveSvgUrl(Icon $icon): string
    {
        $variant = $icon->iconSet ?: $this->defaultVariant();

        return $this->buildSvgUrl($icon->value ?? '', $variant);
    }


    // Protected Methods
    // =========================================================================

    protected function version(): string
    {
        return $this->cdnVersion ?: $this->defaultVersion();
    }

    /**
     * @return array<string, string> Variant key → JSON filename under src/json/.
     */
    abstract protected function catalogMap(): array;

    abstract protected function defaultVariant(): string;

    abstract protected function defaultVersion(): string;

    abstract protected function buildSvgUrl(string $iconName, string $variant): string;

    protected function getVariantSettingsHtml(): ?string
    {
        return null;
    }

}
