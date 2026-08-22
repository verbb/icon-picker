<?php
namespace verbb\iconpicker\iconsets;

use verbb\iconpicker\base\RemoteCssIconSet;

use Craft;

class RemixIcon extends RemoteCssIconSet
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('icon-picker', 'Remix Icon');
    }


    // Properties
    // =========================================================================

    /** @var string[]|null */
    public ?array $variants = ['*'];


    // Public Methods
    // =========================================================================

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('icon-picker/icon-sets/remix-icon', [
            'iconSet' => $this,
        ]);
    }


    // Protected Methods
    // =========================================================================

    protected function catalogFiles(): array
    {
        $map = [
            'line' => 'remix-line.json',
            'fill' => 'remix-fill.json',
        ];

        $files = [];

        foreach ($this->variants ?? ['*'] as $variant) {
            if ($variant === '*') {
                return [
                    'line' => 'remix-line.json',
                    'fill' => 'remix-fill.json',
                ];
            }

            if (isset($map[$variant])) {
                $files[$variant] = $map[$variant];
            }
        }

        return $files;
    }

    protected function defaultVersion(): string
    {
        return '4.6.0';
    }

    protected static function cssFontName(): string
    {
        return 'remix-icon';
    }

    protected function cssUrls(): array|string
    {
        return "https://cdn.jsdelivr.net/npm/remixicon@{$this->version()}/fonts/remixicon.css";
    }

    protected function buildCssValue(string $label, ?string $variant = null): string
    {
        $style = $variant ?: 'line';

        return 'ri-' . $label . '-' . $style;
    }
}
