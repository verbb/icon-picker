<?php
namespace verbb\iconpicker\iconsets;

use verbb\iconpicker\IconPicker;
use verbb\iconpicker\base\IconSet;
use verbb\iconpicker\models\Icon;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use GuzzleHttp\Exception\RequestException;

use Throwable;

class FontAwesome extends IconSet
{
    // Constants
    // =========================================================================

    public const TYPE_KIT = 'kit';
    public const TYPE_CDN = 'cdn';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('icon-picker', 'Font Awesome');
    }


    // Properties
    // =========================================================================

    public ?string $type = null;
    public ?string $apiKey = null;
    public array $kits = [];
    public array|string $styles = '*';
    public ?string $cdnLicense = null;
    public ?string $cdnVersion = null;
    public array $cdnCollections = [];

    private ?string $_apiError = null;


    // Public Methods
    // =========================================================================

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('icon-picker/icon-sets/font-awesome', [
            'iconSet' => $this,
        ]);
    }

    public function fetchIcons(): void
    {
        if ($this->type === self::TYPE_KIT) {
            foreach ($this->kits as $kit) {
                [$kitToken, $version, $license] = explode(':', $kit);

                $data = $this->getKit($kitToken, $license);
                $customIcons = $data['iconUploads'] ?? [];
                $icons = $data['release']['icons'] ?? [];

                // Support both custom and core icons
                foreach ($icons as $icon) {
                    // Create a new icon for each style
                    $styles = $icon['familyStylesByLicense'] ?? [];

                    foreach ($styles as $styleKey => $style) {
                        foreach ($style as $key => $familyStyle) {
                            $class = $this->_getAbbreviationForFamilyStyle($familyStyle);

                            if ($this->_shouldIncludeStyle($familyStyle)) {
                                $this->icons[] = new Icon([
                                    'type' => Icon::TYPE_CSS,
                                    'iconSetHandle' => $this->handle,
                                    'value' => $class . ' fa-' . $icon['id'],
                                    'label' => $icon['label'],
                                    'keywords' => $icon['label'],
                                ]);
                            }
                        }
                    }
                }

                foreach ($customIcons as $customIcon) {
                    $this->icons[] = new Icon([
                        'type' => Icon::TYPE_CSS,
                        'value' => $customIcon['iconDefinition']['prefix'] . ' fa-' . $customIcon['iconDefinition']['iconName'],
                        'label' => $customIcon['name'],
                        'keywords' => $customIcon['name'],
                    ]);
                }

                $this->scripts[] = [
                    'type' => 'remote',
                    'url' => "https://kit.fontawesome.com/{$kitToken}.js",
                ];
            }
        }

        if ($this->type === self::TYPE_CDN) {
            $icons = [];

            // Because we can pick individual collections of icons, we want to fetch them all first, order them
            // alphabetically, and then create the icons.
            foreach ($this->cdnCollections as $collection) {
                $collectionName = ($collection === '*') ? 'all' : $collection;
                $iconPath = __DIR__ . "/../json/font-awesome-{$this->cdnVersion}-{$this->cdnLicense}-{$collectionName}.json";

                if (file_exists($iconPath)) {
                    $json = Json::decode(file_get_contents($iconPath));

                    foreach ($json as $definition) {
                        $icons[] = $definition;
                    }
                }
            }

            // Order icons alphabetically, as we might've added them in order of collection
            usort($icons, fn($a, $b) => strcmp($a['label'], $b['label']));

            foreach ($icons as $icon) {
                $this->icons[] = new Icon([
                    'type' => Icon::TYPE_CSS,
                    'iconSetHandle' => $this->handle,
                    'value' => $icon['classes'],
                    'label' => $icon['label'],
                    'keywords' => $icon['label'],
                ]);
            }

            $urls = [];

            $domain = $this->cdnLicense === 'free' ? 'https://use.fontawesome.com' : 'https://pro.fontawesome.com';

            foreach ($this->cdnCollections as $collection) {
                if ($collection === '*') {
                    $urls[] =  "{$domain}/releases/v{$this->cdnVersion}/css/all.css";
                } else {
                    $urls[] = "{$domain}/releases/v{$this->cdnVersion}/css/fontawesome.css";
                    $urls[] = "{$domain}/releases/v{$this->cdnVersion}/css/{$collection}.css";
                }
            }

            $urls = array_values(array_unique($urls));

            $this->fonts[] = [
                'type' => 'remote',
                'name' => 'Font Awesome',
                'url' => $urls,
            ];
        }
    }

    public function getKitOptions(): array
    {
        $options = [];

        if ($this->type === self::TYPE_KIT) {
            foreach ($this->getKits() as $kit) {
                $options[] = [
                    'label' => "{$kit['name']} ({$kit['token']})",
                    'value' => "{$kit['token']}:{$kit['version']}:{$kit['licenseSelected']}",
                ];
            }
        }

        return $options;
    }

    public function getKits(): array
    {
        $apiKey = App::parseEnv($this->apiKey);
        $cacheKey = 'icon-picker:fa-kits-cache:' . $apiKey;
        $cacheDuration = 60 * 60; // 1 hour

        return Craft::$app->getCache()->getOrSet($cacheKey, function() use ($apiKey) {
            try {
                if ($apiKey) {
                    // Get an access token first
                    $response = $this->request('POST', 'token', [
                        'headers' => [
                            'Authorization' => "Bearer {$apiKey}",
                        ],
                    ]);

                    $accessToken = $response['access_token'] ?? '';

                    $response = $this->request('POST', '/', [
                        'headers' => [
                            'Authorization' => "Bearer {$accessToken}",
                        ],
                        'form_params' => [
                            'query' => '
                                query {
                                    me {
                                        kits {
                                            name
                                            version
                                            technologySelected
                                            licenseSelected
                                            minified
                                            token
                                            shimEnabled
                                            autoAccessibilityEnabled
                                            status
                                        }
                                    }
                                }
                            ',
                        ],
                    ]);

                    return $response['data']['me']['kits'] ?? [];
                }
            } catch (Throwable $e) {
                $messageText = $e->getMessage();

                // Check for Guzzle errors, which are truncated in the exception `getMessage()`.
                if ($e instanceof RequestException && $e->getResponse()) {
                    $messageText = (string)$e->getResponse()->getBody()->getContents();
                }

                $this->_apiError = Craft::t('icon-picker', '{name} API error: “{message}” {file}:{line}', [
                    'name' => $this->name,
                    'message' => $messageText,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                IconPicker::error($this->_apiError);
            }
        }) ?? [];
    }

    public function getKit(string $kitId, string $license): array
    {
        $styles = is_array($this->styles) ? implode('-', $this->styles) : $this->styles;
        $cacheKey = 'icon-picker:fa-icons-' . $kitId . '-' . $styles . '-cache';
        $cacheDuration = 60 * 60; // 1 hour

        return Craft::$app->getCache()->getOrSet($cacheKey, function() use ($kitId, $license) {
            try {
                if ($apiKey = App::parseEnv($this->apiKey)) {
                    // Get an access token first
                    $response = $this->request('POST', 'token', [
                        'headers' => [
                            'Authorization' => "Bearer {$apiKey}",
                        ],
                    ]);

                    $accessToken = $response['access_token'] ?? '';

                    // Only fetch free icons if restricted. Fetch both Pro and Free for pro.
                    $iconsParam = $license === 'free' ? 'icons(license: "free")' : 'icons';

                    $response = $this->request('POST', '/', [
                        'headers' => [
                            'Authorization' => "Bearer {$accessToken}",
                        ],
                        'form_params' => [
                            'query' => '
                                query {
                                    me {
                                        kit(token: "' . $kitId . '") {
                                            iconUploads {
                                                name
                                                unicode
                                                version
                                                width
                                                height
                                                pathData
                                                html
                                                iconDefinition
                                            }

                                            release {
                                                ' . $iconsParam . ' {
                                                    id
                                                    label
                                                    unicode
                                                    familyStylesByLicense {
                                                        ' . $license . ' {
                                                            family
                                                            style
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            ',
                        ],
                    ]);

                    return $response['data']['me']['kit'] ?? [];
                }
            } catch (Throwable $e) {
                $messageText = $e->getMessage();

                // Check for Guzzle errors, which are truncated in the exception `getMessage()`.
                if ($e instanceof RequestException && $e->getResponse()) {
                    $messageText = (string)$e->getResponse()->getBody()->getContents();
                }

                $this->_apiError = Craft::t('icon-picker', '{name} API error: “{message}” {file}:{line}', [
                    'name' => $this->name,
                    'message' => $messageText,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                IconPicker::error($this->_apiError);
            }
        }) ?? [];
    }

    public function getApiError(): ?string
    {
        return $this->_apiError;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey'], 'required', 'when' => fn() => $this->type === self::TYPE_KIT];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function getClient()
    {
        return Craft::createGuzzleClient([
            'base_uri' => 'https://api.fontawesome.com',
        ]);
    }

    private function request(string $method, string $uri, array $options = [])
    {
        $response = $this->getClient()->request($method, ltrim($uri, '/'), $options);

        return Json::decode((string)$response->getBody());
    }

    private function _getAbbreviationForFamilyStyle($familyStyle): string
    {
        $classes = [];
        $family = $familyStyle['family'] ?? '';
        $style = $familyStyle['style'] ?? '';

        // https://docs.fontawesome.com/v6/web/dig-deeper/styles

        // Families
        if ($family === 'classic') {
            $classes[] = 'fa-classic';
        }

        if ($family === 'duotone') {
            $classes[] = 'fa-duotone';
        }

        if ($family === 'sharp') {
            $classes[] = 'fa-sharp';
        }

        if ($family === 'sharp-duotone') {
            $classes[] = 'fa-sharp-duotone';
        }

        if ($family === 'chisel') {
            $classes[] = 'fa-chisel';
        }

        if ($family === 'etch') {
            $classes[] = 'fa-etch';
        }

        if ($family === 'jelly') {
            $classes[] = 'fa-jelly';
        }

        if ($family === 'jelly-fill') {
            $classes[] = 'fa-jelly-fill';
        }

        if ($family === 'jelly-duo') {
            $classes[] = 'fa-jelly-duo';
        }

        if ($family === 'notdog') {
            $classes[] = 'fa-notdog';
        }

        if ($family === 'notdog-duo') {
            $classes[] = 'fa-notdog-duo';
        }

        if ($family === 'slab') {
            $classes[] = 'fa-slab';
        }

        if ($family === 'slab-press') {
            $classes[] = 'fa-slab-press';
        }

        if ($family === 'thumbprint') {
            $classes[] = 'fa-thumbprint';
        }

        if ($family === 'utility') {
            $classes[] = 'fa-utility';
        }

        if ($family === 'utility-fill') {
            $classes[] = 'fa-utility-fill';
        }

        if ($family === 'utility-duo') {
            $classes[] = 'fa-utility-duo';
        }

        if ($family === 'whiteboard') {
            $classes[] = 'fa-whiteboard';
        }

        // Styles
        if ($style === 'brands') {
            $classes[] = 'fa-brands';
        }

        if ($style === 'solid') {
            $classes[] = 'fa-solid';
        }

        if ($style === 'regular') {
            $classes[] = 'fa-regular';
        }

        if ($style === 'light') {
            $classes[] = 'fa-light';
        }

        if ($style === 'thin') {
            $classes[] = 'fa-thin';
        }

        if ($style === 'semibold') {
            $classes[] = 'fa-semibold';
        }

        return implode(' ', $classes);
    }

    private function _shouldIncludeStyle(array $familyStyle): bool
    {
        if ($this->styles === '*') {
            return true;
        }

        $family = $familyStyle['family'] ?? null;
        $style = $familyStyle['style'] ?? null;
        $key = implode(':', array_filter([$family, $style]));

        if (is_array($this->styles) && in_array($key, $this->styles)) {
            return true;
        }

        return false;
    }
}
