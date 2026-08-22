<?php
namespace verbb\iconpicker\base;

use verbb\iconpicker\models\Icon;

use Craft;
use craft\helpers\Json;

/**
 * Icon set backed by a name catalog JSON file and one or more remote stylesheets.
 * Does not bundle icon geometry — only metadata for search/browse.
 */
abstract class RemoteCssIconSet extends IconSet
{
    // Properties
    // =========================================================================

    /** Pin a package version, or null to use defaultVersion(). */
    public ?string $cdnVersion = null;


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
        foreach ($this->loadCatalogIcons() as $row) {
            $label = $row['label'] ?? null;

            if (!$label) {
                continue;
            }

            $variant = $row['variant'] ?? null;

            $this->icons[] = new Icon([
                'type' => Icon::TYPE_CSS,
                'iconSetHandle' => $this->handle,
                'iconSet' => $variant,
                'value' => $this->buildCssValue($label, $variant),
                'label' => $label,
                'keywords' => $row['keywords'] ?? $label,
            ]);
        }

        $this->fonts[] = [
            'type' => 'remote',
            'name' => static::cssFontName(),
            'url' => $this->cssUrls(),
        ];
    }


    // Protected Methods
    // =========================================================================

    /**
     * @return array<int, array{label: string, keywords?: string, variant?: string}>
     */
    protected function loadCatalogIcons(): array
    {
        $icons = [];

        foreach ($this->catalogFiles() as $variant => $relativePath) {
            $path = __DIR__ . '/../json/' . $relativePath;

            if (!file_exists($path)) {
                continue;
            }

            $json = Json::decode(file_get_contents($path));

            foreach ($json as $row) {
                if ($variant !== '') {
                    $row['variant'] = $variant;
                }

                $icons[] = $row;
            }
        }

        usort($icons, fn($a, $b) => strcmp($a['label'] ?? '', $b['label'] ?? ''));

        return $icons;
    }

    protected function version(): string
    {
        return $this->cdnVersion ?: $this->defaultVersion();
    }

    /**
     * Map variant key → JSON filename under src/json/.
     *
     * @return array<string, string>
     */
    abstract protected function catalogFiles(): array;

    abstract protected function defaultVersion(): string;

    abstract protected static function cssFontName(): string;

    /**
     * @return array<int, string>|string
     */
    abstract protected function cssUrls(): array|string;

    abstract protected function buildCssValue(string $label, ?string $variant = null): string;

    protected function getVariantSettingsHtml(): ?string
    {
        return null;
    }

}
