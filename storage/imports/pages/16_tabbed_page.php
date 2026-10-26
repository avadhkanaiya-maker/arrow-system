<?php
/**
 * Pages: a page of tabs, from a data file taken from the live page (pages/data/<name>.json: the page details, the intro,
 * the tabs as HTML, optional feature lists). For the live material pages built as an intro + Elementor tabs, each tab a
 * text, a table and links (read 2026-10-21; no design file):
 *   gerber_foils                    /gerber-foils/: six foil series + four feature lists
 *   gerber_premium_cast_vinyl_film  /gerber-premium-cast-vinyl-film/: four vinyl films
 * Text, tables and links word for word. The banner is the image uploaded with the live page (its media library), the
 * social image the live og:image. General blocks only:
 *   Page Header  Full-width banner: the heading ([red] words), the intro, the banner (whole); button Request a Quote
 *   Tabs         one text tab per live tab
 *   Card Grid    the feature lists, two per row (when the data has any)
 *   SEO          the live title (empty = the default format) and description, the social image
 * Then the menu items and link fields pointing at the live page link here. Skips the page when it exists. Back up first.
 * Run from the project root, after pages/15_tabs_block.php: /opt/lampp/bin/php storage/imports/pages/16_tabbed_page.php <name>
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\db\Query;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\fields\Link;
use verbb\navigation\elements\Node;

$name = $argv[1] ?? exit("usage: … 16_tabbed_page.php <data name, e.g. gerber_foils>\n");
$data = json_decode(@file_get_contents(__DIR__ . "/data/$name.json") ?: 'null', true) ?? exit("no pages/data/$name.json\n");
$p = $data['page'];
define('LIVE_URL', $p['liveUrl']);

/** An image in Images → pages/ from a URL, added once (by file name) */
function pageImage(string $src, string $alt): Asset {
    $volume = Craft::$app->getVolumes()->getVolumeByHandle('images');
    $folder = Craft::$app->getAssets()->ensureFolderByFullPathAndVolume('pages', $volume, false);
    $filename = basename(parse_url($src, PHP_URL_PATH));
    if ($asset = Asset::find()->folderId($folder->id)->filename($filename)->one()) return $asset;
    $bytes = @file_get_contents($src, false, stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0 (Arrow Systems staging migration)\r\n", 'timeout' => 90]]))
        ?: exit("could not read $src\n");
    $tmp = Craft::$app->getPath()->getTempPath() . '/' . uniqid('page') . '-' . $filename;
    file_put_contents($tmp, $bytes);
    $asset = new Asset(['tempFilePath' => $tmp, 'filename' => $filename, 'volumeId' => $volume->id, 'newFolderId' => $folder->id]);
    $asset->alt = $alt;
    $asset->setScenario(Asset::SCENARIO_CREATE);
    Craft::$app->getElements()->saveElement($asset) || fail("asset $filename", $asset);
    return $asset;
}

$page = Entry::find()->section('pages')->slug($p['slug'])->status(null)->one();
if (!$page) {
    $data['tabs'] || exit("$name.json: no tabs\n");
    $banner = pageImage($p['banner']['url'], $p['banner']['alt']);
    $social = pageImage($p['social']['url'], $p['social']['alt']);
    $quote = Entry::find()->section('pages')->slug('request-quote')->one();

    $tabs = ['sortOrder' => [], 'entries' => []];
    foreach ($data['tabs'] as $i => $t) {
        $tabs['sortOrder'][] = "t$i";
        $tabs['entries']["t$i"] = ['type' => 'textTab', 'fields' => ['headingText' => $t['title'], 'richText' => $t['html']]];
    }
    $cards = ['sortOrder' => [], 'entries' => []];
    foreach ($data['features'] as $i => $f) {
        $cards['sortOrder'][] = "c$i";
        $cards['entries']["c$i"] = ['type' => 'card', 'fields' => ['headingText' => $f['title'],
            'richText' => '<ul>' . implode('', array_map(fn($li) => '<li>' . htmlspecialchars($li) . '</li>', $f['items'])) . '</ul>']];
    }

    $page = new Entry(['sectionId' => Craft::$app->getEntries()->getSectionByHandle('pages')->id,
        'typeId' => Craft::$app->getEntries()->getEntryTypeByHandle('page')->id, 'title' => $p['title'], 'slug' => $p['slug']]);
    $page->setFieldValues([
        'seoTitle' => $p['seoTitle'],
        'seoDescription' => $p['seoDescription'],
        'socialImage' => [$social->id],
        'pageBuilder' => ['sortOrder' => $data['features'] ? ['b1', 'b2', 'b3'] : ['b1', 'b2'], 'entries' => array_filter([
            'b1' => ['type' => 'industryHeader', 'fields' => ['eyebrow' => $p['eyebrow'], 'sectionHeading' => $p['heading'],
                'sectionLead' => $data['intro'][0], 'mediaImage' => [$banner->id], 'headerStyle' => 'banner', 'bannerHeight' => 'whole',
                'primaryCta' => $quote ? ['type' => 'entry', 'value' => $quote->id, 'label' => 'Request a Quote'] : ['type' => 'url', 'value' => 'https://arrsys.com/request-quote/', 'label' => 'Request a Quote']]],
            'b2' => ['type' => 'tabsBlock', 'fields' => ['productTabs' => $tabs, 'spacingTop' => 'small']],
            'b3' => $data['features'] ? ['type' => 'cardGrid', 'fields' => ['cardColumns' => '2', 'cardItems' => $cards, 'spacingTop' => 'none']] : null,
        ])],
    ]);
    Craft::$app->getElements()->saveElement($page) || fail("page {$p['slug']}", $page);
    $page->uri === $p['slug'] || exit("the page got /{$page->uri}/: another entry uses /{$p['slug']}/\n");
    $pb = Craft::$app->getFields()->getFieldByHandle('pageBuilder')->id;
    $saved = (int)Entry::find()->ownerId($page->id)->fieldId($pb)->status(null)->count();
    $saved === ($want = $data['features'] ? 3 : 2) || exit("{$p['slug']}: $saved of $want blocks saved\n");
    $tabsBlock = Entry::find()->ownerId($page->id)->fieldId($pb)->type('tabsBlock')->status(null)->one();
    ($n = (int)$tabsBlock->getFieldValue('productTabs')->status(null)->count()) === count($data['tabs']) || exit("{$p['slug']}: $n of " . count($data['tabs']) . " tabs saved\n");
    say("  + page /{$page->uri}/ (Page Header banner, Tabs: $n" . ($data['features'] ? ', Card Grid: ' . count($data['features']) . ' feature lists' : '') . ')');
}

// menu items pointing at the live page → this page
foreach (Node::find()->status(null)->all() as $n) {
    if (rtrim((string)$n->url, '/') . '/' !== LIVE_URL) continue;
    $n->type = Entry::class;
    $n->elementId = $page->id;
    $n->url = null;
    Craft::$app->getElements()->saveElement($n) || fail("menu node {$n->id}", $n);
    say("  ~ menu {$n->menu->handle} “{$n->title}” → the page");
}

// link fields (block buttons, Site Settings) pointing at the live page → this page, label kept; rich text links stay
$ids = (new Query())->select('es.elementId')->from(['es' => '{{%elements_sites}}'])
    ->innerJoin(['e' => '{{%elements}}'], '[[e.id]] = [[es.elementId]]')
    ->where(['e.revisionId' => null, 'e.draftId' => null, 'e.dateDeleted' => null])
    ->andWhere(['like', 'es.content', str_replace('https://', '', rtrim(LIVE_URL, '/'))])->column();
foreach ($ids as $id) {
    $el = Craft::$app->getElements()->getElementById($id, null, null, ['status' => null]);
    if (!$el || !$el->getFieldLayout()) continue;
    $changed = [];
    foreach ($el->getFieldLayout()->getCustomFields() as $f) {
        if (!$f instanceof Link) continue;
        $v = $el->getFieldValue($f->handle);
        if (!$v || rtrim((string)$v->url, '/') . '/' !== LIVE_URL) continue;
        $el->setFieldValue($f->handle, ['type' => 'entry', 'value' => $page->id, 'label' => $v->label, 'target' => $v->target]);
        $changed[] = $f->handle;
    }
    if (!$changed) continue;
    Craft::$app->getElements()->saveElement($el) || fail("element $id", $el);
    say('  ~ ' . ($el instanceof Entry ? "{$el->type->handle} $id" : $el::displayName() . ' ' . ($el->title ?? $el->name ?? $id)) . ': ' . implode(', ', $changed) . ' → the page');
}
