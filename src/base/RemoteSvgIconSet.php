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

    private ?array $_catalogLookup = null;


    // Public Methods
    // =========================================================================

    public function settingsAttributes(): array
    {
        // Craft excludes properties declared on abstract base classes by default.
        return array_values(array_unique(array_merge(parent::settingsAttributes(), ['cdnVersion', 'variants'])));
    }

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
        $this->_catalogLookup = null;
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

    public function resolveSvgUrl(Icon $icon): ?string
    {
        $this->populateIcons();

        $variant = $icon->iconSet ?: $this->defaultVariant();
        $key = $variant . "\0" . ($icon->value ?? '');

        if (!isset($this->_getCatalogLookup()[$key])) {
            return null;
        }

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


    // Private Methods
    // =========================================================================

    /**
     * Remote values are posted by the browser, so only catalog entries may be
     * converted into fetchable URLs. Cache the index to keep grid rendering O(n).
     */
    private function _getCatalogLookup(): array
    {
        if ($this->_catalogLookup !== null) {
            return $this->_catalogLookup;
        }

        $this->_catalogLookup = [];

        foreach ($this->icons as $icon) {
            if (!$icon instanceof Icon || $icon->value === null) {
                continue;
            }

            $variant = $icon->iconSet ?: $this->defaultVariant();
            $this->_catalogLookup[$variant . "\0" . $icon->value] = true;
        }

        return $this->_catalogLookup;
    }

}
