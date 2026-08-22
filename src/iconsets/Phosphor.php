<?php
namespace verbb\iconpicker\iconsets;

use verbb\iconpicker\base\RemoteCssIconSet;

use Craft;

class Phosphor extends RemoteCssIconSet
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('icon-picker', 'Phosphor');
    }


    // Properties
    // =========================================================================

    /** @var string[]|null */
    public ?array $variants = ['*'];


    // Public Methods
    // =========================================================================

    protected function getVariantSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('icon-picker/icon-sets/phosphor', [
            'iconSet' => $this,
        ]);
    }


    // Protected Methods
    // =========================================================================

    protected function catalogFiles(): array
    {
        $all = [
            'thin' => 'phosphor-thin.json',
            'light' => 'phosphor-light.json',
            'regular' => 'phosphor-regular.json',
            'bold' => 'phosphor-bold.json',
            'fill' => 'phosphor-fill.json',
            'duotone' => 'phosphor-duotone.json',
        ];

        $files = [];

        foreach ($this->variants ?? ['*'] as $variant) {
            if ($variant === '*') {
                return $all;
            }

            if (isset($all[$variant])) {
                $files[$variant] = $all[$variant];
            }
        }

        return $files;
    }

    protected function defaultVersion(): string
    {
        return '2.1.1';
    }

    protected static function cssFontName(): string
    {
        return 'phosphor';
    }

    protected function cssUrls(): array|string
    {
        $urls = [];

        foreach (array_keys($this->catalogFiles()) as $weight) {
            $urls[] = "https://cdn.jsdelivr.net/npm/@phosphor-icons/web@{$this->version()}/src/{$weight}/style.css";
        }

        return $urls;
    }

    protected function buildCssValue(string $label, ?string $variant = null): string
    {
        $weight = $variant ?: 'regular';
        $prefix = $weight === 'regular' ? 'ph' : 'ph-' . $weight;

        return $prefix . ' ph-' . $label;
    }
}
