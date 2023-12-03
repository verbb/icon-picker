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
    public ?string $cdnLicense = null;
    public ?string $cdnVersion = null;
    public array $cdnCollections = [];

    private ?string $_apiError = null;


    // Public Methods
    // =========================================================================

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey'], 'required', 'when' => fn() => $this->type === self::TYPE_KIT];

        return $rules;
    }

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

                $icons = $this->getKit($kitToken, $license);

                foreach ($icons as $icon) {
                    // Create a new icon for each style
                    $styles = $icon['familyStylesByLicense'] ?? [];

                    foreach ($styles as $styleKey => $style) {
                        foreach ($style as $key => $familyStyle) {
                            $class = $this->_getAbbreviationForFamilyStyle($familyStyle);

                            $this->icons[] = new Icon([
                                'type' => Icon::TYPE_CSS,
                                'value' => $class . ' fa-' . $icon['id'],
                                'label' => $icon['label'],
                                'keywords' => $icon['label'],
                            ]);
                        }
                    }
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
        $cacheKey = 'icon-picker:fa-icons-' . $kitId . '-cache';
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

                    return $response['data']['me']['kit']['release']['icons'] ?? [];
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
        });
    }

    public function getApiError(): ?string
    {
        return $this->_apiError;
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
        $family = $familyStyle['family'] ?? '';
        $style = $familyStyle['style'] ?? '';

        if ($family === 'duotone') {
            return 'fad';
        }

        if ($style === 'brands') {
            return 'fab';
        }

        if ($style === 'solid') {
            return 'fas';
        }

        if ($style === 'regular') {
            return 'far';
        }

        if ($style === 'light') {
            return 'fal';
        }

        if ($style === 'thin') {
            return 'fat';
        }

        return 'fa';
    }
}
