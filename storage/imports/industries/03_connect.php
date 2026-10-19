<?php
/**
 * Industries, connect:
 *   1. Homepage section E "Industries" (Industries Carousel) after D, with the design's 6 tiles (names + photos);
 *      tiles link to the industry entries (Wines & Spirits has no page → the Industries Page, as the design)
 *   2. Menus: items linking to a live industry page or /industries/ → linked to the entry (they follow its URL)
 *   3. Rich text everywhere (blog posts, products, …): links to live pages that now live in Craft (industries,
 *      /industries/, products, blog posts) → root-relative, the blog import's rule. CKEditor fields, and Markdown links in
 *      plain-text fields; never the SEO / canonical fields.
 *   4. Link fields (blog buttons, hero slides, product lists …) holding a live product / industry URL → an entry link to
 *      the product / industry (old product slugs followed through config/redirects.php)
 * Idempotent. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/industries/03_connect.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';
require dirname(__DIR__) . '/products/_extract.php';
require __DIR__ . '/_links.php';

use craft\ckeditor\Field as CkeditorField;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\helpers\Assets as AssetsHelper;
use verbb\navigation\elements\Node;

const DESIGN = '/home/avadh/Desktop/Arrowsystem_local';
$elements = Craft::$app->getElements();
$listing = Entry::find()->section('industriesPage')->one();
$industry = fn(string $slug) => Entry::find()->section('industries')->slug($slug)->status(null)->one();

// ---- 1. Homepage section E ------------------------------------------------------------------------------------------
say('Homepage');
$home = Entry::find()->section('homepage')->one();
$onHome = $home->homepageSections->status(null)->all();
if (array_filter($onHome, fn($s) => $s->type->handle === 'industryCarousel')) {
    say('  = Industries Carousel already on the homepage');
} else {
    $x = new DOMXPath(exDoc(file_get_contents(DESIGN . '/index.html')));
    $sec = $x->query('//section[@id="industries"]')->item(0);
    $volume = Craft::$app->getVolumes()->getVolumeByHandle('images');
    $folder = Craft::$app->getAssets()->ensureFolderByFullPathAndVolume('industries', $volume, false);
    $tiles = []; $order = [];
    foreach ($x->query('.//li[contains(@class,"industry-card")]', $sec) as $i => $li) {
        $img = $x->query('.//img', $li)->item(0);
        $a = $x->query('.//a', $li)->item(0);
        $name = exText($a);
        $href = $a->getAttribute('href');
        $target = str_starts_with($href, 'industries/index') ? $listing : $industry(trim(parse_url($href, PHP_URL_PATH), '/'));
        $file = realpath(DESIGN . '/' . $img->getAttribute('src'));
        $filename = AssetsHelper::prepareAssetName(basename($file));
        $asset = Asset::find()->folderId($folder->id)->filename($filename)->one();
        if (!$asset) {
            $tmp = Craft::$app->getPath()->getTempPath() . '/' . uniqid('tile') . '-' . $filename;
            copy($file, $tmp);
            $asset = new Asset(['tempFilePath' => $tmp, 'filename' => $filename, 'volumeId' => $volume->id, 'newFolderId' => $folder->id, 'alt' => $name]);
            $asset->setScenario(Asset::SCENARIO_CREATE);
            $elements->saveElement($asset) || fail("asset $filename", $asset);
        }
        $k = 'new' . ($i + 1);
        $order[] = $k;
        $tiles[$k] = ['type' => 'industryTile', 'fields' => [
            'ctaLink' => ['type' => 'entry', 'value' => $target->id, 'label' => $name],
            'headingText' => $name,
            'mediaImage' => [$asset->id],
        ]];
    }
    $view = $x->query('.//a[contains(@class,"text-link")]', $sec)->item(0);
    $sortOrder = [];
    foreach ($onHome as $s) {
        $sortOrder[] = $s->id;
        if ($s->type->handle === 'videoFeature') $sortOrder[] = 'new1';   // E comes after D, as the design
    }
    if (!in_array('new1', $sortOrder, true)) $sortOrder[] = 'new1';
    $home->setFieldValue('homepageSections', [
        'entries' => ['new1' => ['type' => 'industryCarousel', 'title' => 'E · Industries', 'fields' => [
            'eyebrow' => exText($x->query('.//p[contains(@class,"eyebrow")]', $sec)->item(0)),
            'sectionHeading' => exText($x->query('.//h2', $sec)->item(0)),
            'industryTiles' => ['entries' => $tiles, 'sortOrder' => $order],
            'ctaLink' => ['type' => 'entry', 'value' => $listing->id, 'label' => exText($view)],
            'anchorId' => 'industries',
        ]]],
        'sortOrder' => $sortOrder,
    ]);
    $elements->saveElement($home) || fail('homepage', $home);
    say('  + E · Industries (after D), ' . count($tiles) . ' tiles');
}

// ---- 2. Menus -----------------------------------------------------------------------------------------------------------
say('Menus');
foreach (Node::find()->status(null)->all() as $n) {
    if (!preg_match('~^https://arrsys\.com/([^/?#]+)/?$~', (string)$n->url, $m)) continue;
    $target = $m[1] === 'industries' ? $listing : $industry($m[1]);
    if (!$target) continue;
    $n->type = Entry::class;
    $n->elementId = $target->id;
    $n->url = null;
    $elements->saveElement($n) || fail("menu node {$n->id}", $n);
    say("  ~ {$n->menu->handle} “{$n->title}” → {$target->url}");
}

// ---- 3. Links in rich text ----------------------------------------------------------------------------------------------
say('Rich text links');
$slugs = array_map(fn($e) => $e->slug, Entry::find()->section('industries')->status(null)->all());
$pattern = '#href="https?://(?:www\.)?arrsys\.com/(?:product/|industries/|' . implode('/|', array_map('preg_quote', $slugs)) . '/)#i';
$ids = (new craft\db\Query())->select(['es.elementId'])->distinct()->from(['es' => '{{%elements_sites}}'])
    ->innerJoin(['e' => '{{%elements}}'], '[[e.id]] = [[es.elementId]]')
    ->where(['e.revisionId' => null, 'e.draftId' => null, 'e.dateDeleted' => null])
    ->andWhere(['or', ['like', 'es.content', 'arrsys.com/product/'], ['like', 'es.content', 'arrsys.com/industries/'],
        ...array_map(fn($s) => ['like', 'es.content', "arrsys.com/$s/"], $slugs)])
    ->column();
$changed = 0; $links = 0;
foreach ($ids as $id) {
    $el = $elements->getElementById($id);
    if (!$el || !$el->getFieldLayout()) continue;
    $dirty = false;
    foreach ($el->getFieldLayout()->getCustomFields() as $field) {
        $markdown = function (string $text) use (&$links, $pattern) {
            return preg_replace_callback('#\]\((https?://(?:www\.)?arrsys\.com/[^)\s]*)\)#i', function ($m) use (&$links, $pattern) {
                $local = localHref($m[1]);
                if ($local !== $m[1] && preg_match($pattern, 'href="' . $m[1])) { $links++; return '](' . $local . ')'; }
                return $m[0];
            }, $text);
        };
        if ($field instanceof craft\fields\PlainText && !str_starts_with($field->handle, 'seo')) {   // Markdown links, e.g. [MTS-48](https://arrsys.com/product/…)
            $text = (string)$el->getFieldValue($field->handle);
            if (($new = $markdown($text)) !== $text) { $el->setFieldValue($field->handle, $new); $dirty = true; }
            continue;
        }
        if ($field instanceof craft\fields\Table) {   // Markdown links in table cells (e.g. a Spec List description)
            $rows = $el->getFieldValue($field->handle) ?? [];
            $out = []; $rowDirty = false;
            foreach ($rows as $row) {
                foreach ($row as $col => $cell) if (is_string($cell) && ($new = $markdown($cell)) !== $cell) { $row[$col] = $new; $rowDirty = true; }
                $out[] = $row;
            }
            if ($rowDirty) { $el->setFieldValue($field->handle, $out); $dirty = true; }
            continue;
        }
        if (!$field instanceof CkeditorField) continue;
        $value = $el->getFieldValue($field->handle);
        $html = $value?->getRawContent();
        if (!$html || !preg_match($pattern, $html)) continue;
        $new = preg_replace_callback('#href="(https?://(?:www\.)?arrsys\.com/[^"]*)"#i', function ($m) use (&$links, $pattern) {
            $local = localHref(html_entity_decode($m[1]));
            if ($local !== html_entity_decode($m[1]) && preg_match($pattern, $m[0])) { $links++; return 'href="' . htmlspecialchars($local) . '"'; }
            return $m[0];
        }, $html);
        if ($new !== $html) { $el->setFieldValue($field->handle, $new); $dirty = true; }
    }
    if ($dirty) { $elements->saveElement($el) || fail("element $id", $el); $changed++; }
}
say("  ~ $links links in $changed elements → root-relative (industries, /industries/, products)");

// ---- 4. Link fields (buttons, hero slides, product lists …) to a live product / industry page → the entry -----------
say('Link fields');
$byUrl = function (string $url) use ($industry, $listing): ?Entry {
    if (!preg_match('~^https?://(?:www\.)?arrsys\.com/(product/)?([^/?#]+)/?(?:[?#].*)?$~i', trim($url), $m)) return null;
    if ($m[1]) {
        $slug = $m[2];   // an old slug → its product (config/redirects.php)
        foreach (require Craft::getAlias('@config/redirects.php') as $r) if (is_array($r) && ($r['from'] ?? '') === 'product/' . $m[2] && preg_match('#^/product/([^/]+)/$#', $r['to'] ?? '', $t)) $slug = $t[1];
        return Entry::find()->section('products')->slug($slug)->status(null)->one();
    }
    return $m[2] === 'industries' ? $listing : $industry($m[2]);
};
$ids = (new craft\db\Query())->select(['es.elementId'])->distinct()->from(['es' => '{{%elements_sites}}'])
    ->innerJoin(['e' => '{{%elements}}'], '[[e.id]] = [[es.elementId]]')
    ->where(['e.revisionId' => null, 'e.draftId' => null, 'e.dateDeleted' => null])
    ->andWhere(['or', ['like', 'es.content', '"value":"https://arrsys.com/product/'], ['like', 'es.content', '"value":"https://arrsys.com/industries/'],
        ...array_map(fn($s) => ['like', 'es.content', "\"value\":\"https://arrsys.com/$s/"], $slugs)])
    ->column();
$changed = 0; $count = 0; $left = [];
foreach ($ids as $id) {
    $el = $elements->getElementById($id);
    if (!$el || !$el->getFieldLayout()) continue;
    $dirty = false;
    foreach ($el->getFieldLayout()->getCustomFields() as $field) {
        if (!$field instanceof craft\fields\Link) continue;
        $link = $el->getFieldValue($field->handle);
        if (!$link || $link->getType() !== 'url') continue;
        $target = $byUrl((string)$link->getValue());
        if (!$target) { if (preg_match('~arrsys\.com/product/~', (string)$link->getValue())) $left[] = $link->getValue(); continue; }
        if (!in_array('entry', $field->types, true)) { $left[] = "{$field->handle} (no entry links): {$link->getValue()}"; continue; }
        $el->setFieldValue($field->handle, ['type' => 'entry', 'value' => $target->id, 'label' => $link->label, 'target' => $link->target]);
        $dirty = true; $count++;
    }
    if ($dirty) { $elements->saveElement($el) || fail("element $id", $el); $changed++; }
}
say("  ~ $count links in $changed elements → the product / industry entries");
foreach (array_unique($left) as $l) say("  ! left as is: $l");
saveProjectConfig();
