<?php
/**
 * Regenerate name-only catalogs for remote icon sets (metadata only — no SVG/font files).
 *
 * Usage: php scripts/generate-icon-catalogs.php
 *
 * Keep versions in sync with each icon set’s defaultVersion() (and Lucide/Heroicons class constants).
 */

declare(strict_types=1);

$jsonDir = dirname(__DIR__) . '/src/json';

// Plugin-pinned npm versions — bump here and in icon set classes on release.
$versions = [
    'bootstrap' => '1.13.1',
    'remix' => '4.9.1',
    'tabler' => '3.46.0',
    'cssGg' => '2.1.4',
    'ionicons' => '8.1.0',
    'lucide' => '1.33.0',
    'heroicons' => '2.2.0',
    'octicons' => '19.12.0',
];

function fetchJson(string $url): array
{
    $raw = file_get_contents($url);

    if ($raw === false) {
        throw new RuntimeException("Failed to fetch {$url}");
    }

    return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
}

function writeCatalog(string $path, array $icons): void
{
    $json = json_encode($icons, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    file_put_contents($path, $json);
    echo 'Wrote ' . basename($path) . ' (' . count($icons) . ' icons)' . PHP_EOL;
}

function iconRow(string $label, ?string $keywords = null): array
{
    return array_filter([
        'label' => $label,
        'keywords' => $keywords,
    ]);
}

// Bootstrap Icons — name list from official glyph map.
$bootstrapVersion = $versions['bootstrap'];
$bootstrap = fetchJson("https://cdn.jsdelivr.net/npm/bootstrap-icons@{$bootstrapVersion}/font/bootstrap-icons.json");
$bootstrapIcons = [];

foreach (array_keys($bootstrap) as $name) {
    $bootstrapIcons[] = iconRow((string)$name);
}

writeCatalog($jsonDir . '/bootstrap-icons.json', $bootstrapIcons);

// Remix Icon — glyph map keys end with -line / -fill.
$remixVersion = $versions['remix'];
$remixGlyphs = fetchJson("https://cdn.jsdelivr.net/npm/remixicon@{$remixVersion}/fonts/remixicon.glyph.json");
$remixLine = [];
$remixFill = [];

foreach (array_keys($remixGlyphs) as $name) {
    if (str_ends_with($name, '-line')) {
        $remixLine[] = iconRow(substr($name, 0, -5));
    } elseif (str_ends_with($name, '-fill')) {
        $remixFill[] = iconRow(substr($name, 0, -5));
    }
}

usort($remixLine, fn($a, $b) => strcmp($a['label'], $b['label']));
usort($remixFill, fn($a, $b) => strcmp($a['label'], $b['label']));
writeCatalog($jsonDir . '/remix-line.json', $remixLine);
writeCatalog($jsonDir . '/remix-fill.json', $remixFill);

// Material Design Icons — Iconify mdi collection (avoids utility classes in CSS).
$mdi = fetchJson('https://api.iconify.design/collection?prefix=mdi');
$mdiIcons = array_map(fn(string $name) => iconRow($name), $mdi['uncategorized'] ?? []);
sort($mdiIcons);
writeCatalog($jsonDir . '/mdi.json', $mdiIcons);

// css.gg — icon names from package icons.json (catalog pin; CSS URL remains legacy all.css).
$cssGgVersion = $versions['cssGg'];
$cssGgIcons = fetchJson("https://cdn.jsdelivr.net/npm/css.gg@{$cssGgVersion}/icons/icons.json");
$cssGgRows = array_map(fn(string $name) => iconRow($name), array_keys($cssGgIcons));
sort($cssGgRows);
writeCatalog($jsonDir . '/css-gg.json', $cssGgRows);

// Phosphor — group Iconify ph collection by base name and weight suffix.
$phCollection = fetchJson('https://api.iconify.design/collection?prefix=ph');
$phByWeight = [
    'thin' => [],
    'light' => [],
    'regular' => [],
    'bold' => [],
    'fill' => [],
    'duotone' => [],
];
$weightSuffixes = [
    '-thin' => 'thin',
    '-light' => 'light',
    '-bold' => 'bold',
    '-fill' => 'fill',
    '-duotone' => 'duotone',
];

$allPhNames = $phCollection['uncategorized'] ?? [];

foreach ($allPhNames as $name) {
    $matched = false;

    foreach ($weightSuffixes as $suffix => $weight) {
        if (str_ends_with($name, $suffix)) {
            $base = substr($name, 0, -strlen($suffix));
            $phByWeight[$weight][] = iconRow($base);
            $matched = true;
            break;
        }
    }

    if (!$matched) {
        $phByWeight['regular'][] = iconRow($name);
    }
}

foreach ($phByWeight as $weight => $icons) {
    usort($icons, fn($a, $b) => strcmp($a['label'], $b['label']));
    writeCatalog($jsonDir . "/phosphor-{$weight}.json", $icons);
}

// Lucide — icon names from lucide-static SVGs.
$lucideVersion = $versions['lucide'];
$lucidePackage = fetchJson("https://data.jsdelivr.com/v1/package/npm/lucide-static@{$lucideVersion}/flat");
$lucideIcons = [];

foreach ($lucidePackage['files'] ?? [] as $file) {
    $path = $file['name'] ?? '';

    if (!str_starts_with($path, '/icons/') || !str_ends_with($path, '.svg')) {
        continue;
    }

    $lucideIcons[] = iconRow(substr(basename($path), 0, -4));
}

usort($lucideIcons, fn($a, $b) => strcmp($a['label'], $b['label']));
writeCatalog($jsonDir . '/lucide.json', $lucideIcons);

// Tabler — outline + filled from icons.json
$tablerVersion = $versions['tabler'];
$tablerMeta = fetchJson("https://cdn.jsdelivr.net/npm/@tabler/icons@{$tablerVersion}/icons.json");
$tablerOutline = [];
$tablerFilled = [];

foreach ($tablerMeta as $entry) {
    $name = $entry['name'] ?? null;

    if (!$name) {
        continue;
    }

    $tags = isset($entry['tags']) ? implode(' ', $entry['tags']) : null;

    if (isset($entry['styles']['outline'])) {
        $tablerOutline[] = iconRow($name, $tags);
    }

    if (isset($entry['styles']['filled'])) {
        $tablerFilled[] = iconRow($name, $tags);
    }
}

usort($tablerOutline, fn($a, $b) => strcmp($a['label'], $b['label']));
usort($tablerFilled, fn($a, $b) => strcmp($a['label'], $b['label']));
writeCatalog($jsonDir . '/tabler-outline.json', $tablerOutline);
writeCatalog($jsonDir . '/tabler-filled.json', $tablerFilled);

// Heroicons — icon names from npm heroicons@2.
$heroiconsVersion = $versions['heroicons'];
$heroiconsPackage = fetchJson("https://data.jsdelivr.com/v1/package/npm/heroicons@{$heroiconsVersion}/flat");
$heroOutline = [];
$heroSolid = [];

foreach ($heroiconsPackage['files'] ?? [] as $file) {
    $path = $file['name'] ?? '';

    if (!str_ends_with($path, '.svg')) {
        continue;
    }

    if (str_contains($path, '/24/outline/')) {
        $heroOutline[] = iconRow(substr(basename($path), 0, -4));
    } elseif (str_contains($path, '/24/solid/')) {
        $heroSolid[] = iconRow(substr(basename($path), 0, -4));
    }
}

usort($heroOutline, fn($a, $b) => strcmp($a['label'], $b['label']));
usort($heroSolid, fn($a, $b) => strcmp($a['label'], $b['label']));
writeCatalog($jsonDir . '/heroicons-outline.json', $heroOutline);
writeCatalog($jsonDir . '/heroicons-solid.json', $heroSolid);

// Ionicons (modern SVG) — outline, sharp, and default (no suffix) sets.
$ioniconsVersion = $versions['ionicons'];
$ioniconsPackage = fetchJson("https://data.jsdelivr.com/v1/package/npm/ionicons@{$ioniconsVersion}/flat");
$ioniconsOutline = [];
$ioniconsSharp = [];
$ioniconsDefault = [];

foreach ($ioniconsPackage['files'] ?? [] as $file) {
    $path = $file['name'] ?? '';

    if (!str_contains($path, '/dist/ionicons/svg/') || !str_ends_with($path, '.svg')) {
        continue;
    }

    $name = substr(basename($path), 0, -4);

    if (str_ends_with($name, '-outline')) {
        $ioniconsOutline[] = iconRow($name);
    } elseif (str_ends_with($name, '-sharp')) {
        $ioniconsSharp[] = iconRow($name);
    } else {
        $ioniconsDefault[] = iconRow($name);
    }
}

usort($ioniconsOutline, fn($a, $b) => strcmp($a['label'], $b['label']));
usort($ioniconsSharp, fn($a, $b) => strcmp($a['label'], $b['label']));
usort($ioniconsDefault, fn($a, $b) => strcmp($a['label'], $b['label']));
writeCatalog($jsonDir . '/ionicons-modern-outline.json', $ioniconsOutline);
writeCatalog($jsonDir . '/ionicons-modern-sharp.json', $ioniconsSharp);
writeCatalog($jsonDir . '/ionicons-modern-default.json', $ioniconsDefault);

// Octicons — default to 24px SVG filenames ({name}-24.svg).
$octiconsVersion = $versions['octicons'];
$octiconsData = fetchJson("https://cdn.jsdelivr.net/npm/@primer/octicons@{$octiconsVersion}/build/data.json");
$octicons24 = [];

foreach ($octiconsData as $name => $entry) {
    if (!isset($entry['heights']['24'])) {
        continue;
    }

    $octicons24[] = iconRow($name . '-24', $name);
}

usort($octicons24, fn($a, $b) => strcmp($a['label'], $b['label']));
writeCatalog($jsonDir . '/octicons-24.json', $octicons24);

echo 'Done.' . PHP_EOL;
