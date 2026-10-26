<?php
/**
 * Pages: /arrowjet-uv-label-printers/, from the live page (https://arrsys.com/arrowjet-uv-label-printers/, read
 * 2026-10-23; no design file of its own: the cards are the design's product listing cards, digital-label-printers/).
 * Live: the H1, an intro and 3 printers (photo, name, tagline, specs, Learn More). General blocks only:
 *   Page Header           "ArrowJet UV Label [Printers]" + live's intro, no image (live has none)
 *   Recommended Products  "Our UV [Printers]": ArrowJet UV 330H, ArrowJet UV, ArrowJet MTS-48, live order
 *   SEO                   the live title and description
 * The cards show each product's own Card Tagline and Key Specs, empty until now: filled with live's text (they show
 * wherever these products appear as cards). Skipped for a product with an open draft or values already set.
 * Then every menu item and link field pointing at the live page links here (Footer › UV Printers, Main Menu › ArrowJet
 * UV / ArrowJet UV 330H, the UV & Direct-to-Package View All Text URL); links typed in rich text stay. Stops if a
 * menu has unsaved builder changes. Skips the page when it exists. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/pages/24_arrowjet_uv_label_printers.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\db\Query;
use craft\elements\Entry;
use craft\fields\Link;
use verbb\navigation\elements\Node;
use verbb\navigation\nodetypes\Entry as EntryNode;

const LIVE_URL = 'https://arrsys.com/arrowjet-uv-label-printers/';
const PRODUCTS = [   // live order: slug => [tagline, [label, value]…], live's text
    'arrowjet-uv-330h' => ['One System. Infinite Possibilities.', [
        ['Color', 'CMYK + White/Clear/Varnish'], ['Max Print Width', '320 mm'],
        ['Print Resolution', 'Up to 720 × 4800 DPI'], ['Print Speed', '5–20 m/min (based on substrate)']]],
    'arrowjet-uv' => ['Single Pass UV LED Inkjet with 4 to 7 color options', [
        ['Colors', 'CMYK+W (up to 7 colors)'], ['Ink Type', 'UV Curable'], ['Max. Print Width', '432 mm (multiple of 108 mm)'],
        ['Resolution', 'Up to 600 dpi'], ['Speed', 'Up to 120 m/min']]],
    'arrowjet-mts-48' => ['Compact Power. Industrial Performance.', [
        ['Print Speed', 'Up to 580 ft²/hr'], ['Max Printing Width', '2500 × 1300 mm'], ['Resolution', '1200 dpi']]],
];

$elements = Craft::$app->getElements();
$ids = [];
foreach (PRODUCTS as $slug => [$tagline, $specs]) {
    $p = Entry::find()->section('products')->slug($slug)->status(null)->one() ?? exit("no product $slug\n");
    $ids[] = $p->id;
    if ($p->cardTagline || $p->getFieldValue('keySpecs')) continue;   // already filled: keep
    if ((new Query())->from('{{%drafts}}')->where(['canonicalId' => $p->id])->exists()) { say("  ! {$p->title}: open draft, card text not set"); continue; }
    $p->setFieldValue('cardTagline', $tagline);
    $p->setFieldValue('keySpecs', array_map(fn($r) => ['col1' => $r[0], 'col2' => $r[1]], $specs));
    $elements->saveElement($p) || fail("product $slug", $p);
    say("  ~ {$p->title}: Card Tagline + " . count($specs) . ' Key Specs (live)');
}

$page = Entry::find()->section('pages')->slug('arrowjet-uv-label-printers')->status(null)->one();
if (!$page) {
    $page = new Entry(['sectionId' => Craft::$app->getEntries()->getSectionByHandle('pages')->id,
        'typeId' => Craft::$app->getEntries()->getEntryTypeByHandle('page')->id, 'title' => 'ArrowJet UV Label Printers', 'slug' => 'arrowjet-uv-label-printers']);
    $page->setFieldValues([
        'seoTitle' => 'ArrowJet UV Label Printers - Arrow Systems, Inc.',
        'seoDescription' => 'Step into the next generation of industrial printing. The ArrowJet UV series unites ultra-fast single-pass UV LED technology with exceptional substrate',
        'pageBuilder' => ['sortOrder' => ['b1', 'b2'], 'entries' => [
            'b1' => ['type' => 'industryHeader', 'fields' => ['eyebrow' => 'Products', 'sectionHeading' => 'ArrowJet UV Label [Printers]',
                'sectionLead' => 'Step into the next generation of industrial printing. The ArrowJet UV series unites ultra-fast single-pass UV LED technology with exceptional substrate flexibility, producing high-quality labels and flexible packaging at the pace your business demands.']],
            'b2' => ['type' => 'productShowcase', 'fields' => ['sectionHeading' => 'Our UV [Printers]', 'productSource' => 'picked', 'selectedProducts' => $ids]],
        ]],
    ]);
    $elements->saveElement($page) || fail('page arrowjet-uv-label-printers', $page);
    $page->uri === 'arrowjet-uv-label-printers' || exit("the page got /{$page->uri}/: another entry uses /arrowjet-uv-label-printers/\n");
    $pb = Craft::$app->getFields()->getFieldByHandle('pageBuilder')->id;
    ($saved = (int)Entry::find()->ownerId($page->id)->fieldId($pb)->status(null)->count()) === 2 || exit("arrowjet-uv-label-printers: $saved of 2 blocks saved\n");
    say("  + page /{$page->uri}/ (Page Header with the live intro, Recommended Products: " . count($ids) . ')');
}

// menus: stop on unsaved builder changes, then Custom URL items to the live page → this page
$isLive = fn(?string $url) => $url && rtrim($url, '/') . '/' === LIVE_URL;
$nodes = array_filter(Node::find()->status(null)->all(), fn($n) => $isLive($n->url));
foreach (array_unique(array_map(fn($n) => $n->menuId, $nodes)) as $menuId) {
    foreach ((new Query())->from('{{%navigation_build_sessions}}')->where(['menuId' => $menuId])->all() as $s) {
        !array_filter([$s['structureMoves'], $s['addedNodeIds'], $s['stagedDeletes'], $s['nodeDraftMap'], $s['menuContentDraft']])
            || exit("menu $menuId has unsaved builder changes: Save or Discard them, then run again\n");
    }
}
foreach ($nodes as $n) {
    $n->type = EntryNode::class;
    $n->elementId = $page->id;
    $n->url = null;
    $elements->saveElement($n) || fail("menu node {$n->id}", $n);
    say("  ~ menu {$n->menu->handle} “{$n->title}” → the page");
}

// link fields pointing at the live page → this page, label and new-tab kept (menu items' View All Text URL included)
$found = (new Query())->select('es.elementId')->from(['es' => '{{%elements_sites}}'])
    ->innerJoin(['e' => '{{%elements}}'], '[[e.id]] = [[es.elementId]]')
    ->where(['e.revisionId' => null, 'e.draftId' => null, 'e.dateDeleted' => null])
    ->andWhere(['like', 'es.content', 'arrsys.com/arrowjet-uv-label-printers'])->column();
foreach ($found as $id) {
    $el = $elements->getElementById($id, null, null, ['status' => null]);
    if (!$el || !$el->getFieldLayout()) continue;
    $changed = [];
    foreach ($el->getFieldLayout()->getCustomFields() as $f) {
        if (!$f instanceof Link || !$isLive((string)$el->getFieldValue($f->handle)?->url)) continue;
        $v = $el->getFieldValue($f->handle);
        $el->setFieldValue($f->handle, ['type' => 'entry', 'value' => $page->id, 'label' => $v->label, 'target' => $v->target]);
        $changed[] = $f->handle;
    }
    if (!$changed) continue;
    $elements->saveElement($el) || fail("element $id", $el);
    say('  ~ ' . ($el instanceof Entry ? "{$el->type->handle} $id" : $el::displayName() . ' “' . ($el->title ?? $id) . '”') . ': ' . implode(', ', $changed) . ' → the page');
}

// self-check: 3 cards with their specs, no menu item or link field left on the live address
$html = @file_get_contents('http://localhost:8081/arrowjet-uv-label-printers/') ?: exit("/arrowjet-uv-label-printers/ does not load\n");
($n = substr_count($html, 'class="pcard"')) === count(PRODUCTS) || exit("$n product cards, expected " . count(PRODUCTS) . "\n");
substr_count($html, 'class="pcard__specs"') === count(PRODUCTS) || exit("not every card shows its specs\n");
!array_filter(Node::find()->status(null)->all(), fn($n) => $isLive($n->url)) || exit("a menu item still links to live\n");
say('  = /arrowjet-uv-label-printers/: ' . count(PRODUCTS) . ' printer cards with specs; menus link here');
