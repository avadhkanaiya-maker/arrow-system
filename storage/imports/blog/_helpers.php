<?php
/**
 * Helpers shared by the blog pilot (04_pilot.php) and the import (05_import.php). Require after _bootstrap.php.
 *   builderValue()   Article Body (Matrix) value from a list of blocks
 *   nb()             one block (type, fields, child blocks)
 *   importImage()    download (or copy) an image into Images → blog/ once, with its alt text
 *   liveData()       the live post data (REST export + SEO from the page <head>)
 */

use craft\elements\Asset;
use craft\elements\Entry;
use craft\helpers\Assets as AssetsHelper;

/** One block: nb('heading', ['headingText' => 'Hi']), with children nb('faq', [], [nb('faqItem', …)]) */
function nb(string $type, array $fields = [], array $children = []): array {
    return ['type' => $type, 'fields' => $fields, 'children' => $children];
}

/** Nested field that holds the child blocks of a parent block (Article Body is a Matrix field) */
const BUILDER_CHILD_FIELDS = ['steps' => 'stepItems', 'compare' => 'compareColumns', 'productList' => 'productItems', 'faq' => 'faqItems'];

/** The Article Body (Matrix) value: blocks in order; a parent's child blocks go into its nested field */
function builderValue(array $blocks): array {
    $out = ['entries' => [], 'sortOrder' => []];
    foreach (array_values($blocks) as $i => $b) {
        $fields = $b['fields'];
        if (isset(BUILDER_CHILD_FIELDS[$b['type']])) $fields[BUILDER_CHILD_FIELDS[$b['type']]] = builderValue($b['children']);
        elseif ($b['children']) throw new Exception("{$b['type']} blocks can't have child blocks");
        $key = 'new' . ($i + 1);
        $out['entries'][$key] = ['type' => $b['type'], 'enabled' => true, 'fields' => $fields];
        $out['sortOrder'][] = $key;
    }
    return $out;
}

/** Images volume folder blog/ (or another one, e.g. answers/) */
function blogFolder(string $path = 'blog'): \craft\models\VolumeFolder {
    static $folders = [];
    $volume = Craft::$app->getVolumes()->getVolumeByHandle('images');
    return $folders[$path] ??= Craft::$app->getAssets()->ensureFolderByFullPathAndVolume($path, $volume, false);
}

/**
 * The asset for an image URL (or a local file path), imported into Images → blog/ (or $folder) once (matched by file name).
 * Sets the alt text when the asset has none. Returns null (and logs to $GLOBALS['report']) if it can't be fetched.
 */
function importImage(string $src, ?string $alt = null, string $folder = 'blog'): ?Asset {
    static $cache = [];
    $src = html_entity_decode(trim($src));
    if ($src === '') return null;
    if (str_starts_with($src, '//')) $src = 'https:' . $src;
    $key = "$folder|$src";
    if (array_key_exists($key, $cache)) {
        $asset = $cache[$key];
    } else {
        $folder = blogFolder($folder);
        $path = parse_url($src, PHP_URL_PATH) ?: $src;
        $filename = AssetsHelper::prepareAssetName(rawurldecode(basename($path)));
        if (!pathinfo($filename, PATHINFO_EXTENSION)) $filename .= '.jpg';
        $asset = Asset::find()->folderId($folder->id)->filename($filename)->one();
        if (!$asset) {
            // non-ASCII characters in the path (e.g. "Looking-2400-×-600-px.webp") must be percent-encoded
            $fetch = is_file($src) ? $src : preg_replace_callback('/[^\x21-\x7e]/', fn($m) => rawurlencode($m[0]), $src);
            $data = is_file($src) ? file_get_contents($src)
                : @file_get_contents($fetch, false, stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0 (Arrow Systems staging migration)\r\n", 'timeout' => 60]]));
            if (!$data) {
                $GLOBALS['report'][] = "could not download $src";
                return $cache[$key] = null;
            }
            $tmp = Craft::$app->getPath()->getTempPath() . '/' . uniqid('blog') . '-' . $filename;
            file_put_contents($tmp, $data);
            $asset = new Asset();
            $asset->tempFilePath = $tmp;
            $asset->filename = $filename;
            $asset->volumeId = $folder->volumeId;
            $asset->newFolderId = $folder->id;
            $asset->avoidFilenameConflicts = true;
            $asset->setScenario(Asset::SCENARIO_CREATE);
            if (!Craft::$app->getElements()->saveElement($asset)) {
                $GLOBALS['report'][] = "could not save $filename: " . json_encode($asset->getErrors());
                return $cache[$key] = null;
            }
        }
        $cache[$key] = $asset;
    }
    $alt = trim(html_entity_decode((string)$alt, ENT_QUOTES | ENT_HTML5));
    if ($asset && $alt !== '' && !$asset->alt) {
        $asset->alt = $alt;
        Craft::$app->getElements()->saveElement($asset);
    }
    return $asset;
}

/** Live posts by slug: REST export (data/posts-*.json) + SEO (data/seo.json) + featured images (data/media-*.json) */
function liveData(): array {
    static $data;
    if ($data) return $data;
    $posts = [];
    foreach (glob(__DIR__ . '/data/posts-*.json') as $f) {
        foreach (json_decode(file_get_contents($f), true) as $p) $posts[$p['slug']] = $p;
    }
    $media = [];
    foreach (glob(__DIR__ . '/data/media-*.json') as $f) {
        foreach (json_decode(file_get_contents($f), true) as $m) $media[$m['id']] = $m;
    }
    $seo = json_decode(file_get_contents(__DIR__ . '/data/seo.json'), true);
    $cats = array_column(json_decode(file_get_contents(__DIR__ . '/data/categories.json'), true), null, 'id');
    $tags = [];
    foreach (glob(__DIR__ . '/data/tags-*.json') as $f) {
        foreach (json_decode(file_get_contents($f), true) as $t) $tags[$t['id']] = $t;
    }
    $redirects = [];
    foreach (file(__DIR__ . '/data/live-redirects.tsv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        [$slug, , $to] = explode("\t", $line);
        $redirects[$slug] = $to;
    }
    return $data = compact('posts', 'media', 'seo', 'cats', 'tags', 'redirects');
}

/** Craft category entries for a live post: the breadcrumb (main) category first, then the others */
function postCategories(array $post): array {
    $live = liveData();
    $ids = $post['categories'];
    $main = $live['seo'][$post['slug']]['crumbCategory'] ?? null;
    $uris = [];
    foreach ($ids as $id) {
        if (!isset($live['cats'][$id])) continue;
        $cat = $live['cats'][$id];
        $slugs = [$cat['slug']];
        while ($cat['parent'] && isset($live['cats'][$cat['parent']])) {
            $cat = $live['cats'][$cat['parent']];
            array_unshift($slugs, $cat['slug']);
        }
        $uri = 'category/' . implode('/', $slugs);
        if ($main && rtrim(parse_url($main, PHP_URL_PATH), '/') === "/$uri") array_unshift($uris, $uri);
        else $uris[] = $uri;
    }
    $out = [];
    foreach (array_unique($uris) as $uri) {
        $e = Entry::find()->section('blogCategories')->uri($uri)->status(null)->one();
        if ($e) $out[] = $e->id;
    }
    return $out;
}

/** Craft tag entry IDs for a live post */
function postTags(array $post): array {
    $live = liveData();
    $slugs = array_filter(array_map(fn($id) => $live['tags'][$id]['slug'] ?? null, $post['tags']));
    return $slugs ? Entry::find()->section('blogTags')->slug(array_values($slugs))->status(null)->ids() : [];
}

/** SEO field values for a live post: the title only when it differs from the default "{title} - Arrow Systems, Inc." */
function postSeo(array $post, string $title): array {
    $seo = liveData()['seo'][$post['slug']] ?? [];
    $out = ['seoDescription' => $seo['description'] ?? null];
    if (($seo['title'] ?? null) && $seo['title'] !== "$title - Arrow Systems, Inc.") $out['seoTitle'] = $seo['title'];
    return array_filter($out);
}

/** The post's publish date: WordPress's local `date` in live's time zone, America/New_York (as live shows it; `date_gmt`
    equals `date` on older posts, so it can be hours off). Not Craft's time zone: it was changed to Asia/Kolkata later */
function postDate(array $post): DateTime {
    return new DateTime($post['date'], new DateTimeZone('America/New_York'));
}
