<?php
/**
 * Pages: the /all-products/ tree, as nested Pages (the Pages structure: parent page → /all-products/<child>/…), from the
 * live pages (read 2026-10-23). General blocks only, as /arrowjet-uv-label-printers/: Page Header + Recommended
 * Products (picked, live order; the product cards show each product's own Card Title, tagline and Key Specs).
 * Built so far (PAGES below; add the other category pages there):
 *   /all-products/                                      "All Products": printers (9) + finishers (10)
 *   /all-products/label-finishers/                      "Label Finishers" (10)
 *   /all-products/label-finishers/arrow-label-finishers/ "Digital Label Finishing Equipments" (10)
 *   /all-products/printers/                              "Printer" (9: live's ArrowJet VDP left out, its product address now
 *                                                        redirects to the Limitronic printers page and it isn't on staging)
 *   /all-products/printers/gerber-printers/              "Printers by Gerber" (1: Gerber EDGE FX, a wide card)
 * A page with 'banner' (live's banner image, set as a section background there) gets it in its Page Header as a
 * Full-width banner, shown whole (it has text and logos in it): downloaded from live into Assets › Images › pages.
 * Live's mix-ups not copied: "Arrow EZCut 330A" links to the EZCut 350R (the 350R shows once), "Arrow ezcut 330S"
 * links to the EZCut 330R+ (shown with the 330R+'s own text); "Aries Laser Label Finishing" links to an address that
 * redirects to the Taurus Laser Finisher (shown as Taurus); "Arrow slitter" links to an address that now redirects to
 * the homepage (shown as its product, Arrow Slitter 1500: same photo).
 * Finisher cards (CARDS): empty Card Tagline / Key Specs filled with live's text (from arrow-label-finishers, which
 * names them right; the slitter from label-finishers); a short Card Title where the product name is long. Skipped
 * for a product with an open draft; values already set are kept.
 * Then every menu item and link field pointing at a built page's live address links here (rich text links stay);
 * stops if a menu has unsaved builder changes. Live redirects: config/redirects.php. Skips pages that exist.
 * Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/pages/25_all_products.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\db\Query;
use craft\elements\Entry;
use craft\fields\Link;
use verbb\navigation\elements\Node;
use verbb\navigation\nodetypes\Entry as EntryNode;

const FINISHERS = ['aries', 'taurus-laser-finisher', 'arrowcut-nova-330r-roll-to-roll-laser-cutter', 'arrowcut-nova-250r-laser-label-cutter',
    'arrow-eco-300-label-cutter', 'arrow-ezcut-330r', 'arrowezcut-350r', 'arrowezcut-330rplus', 'arrow-ezcut-240', 'arrow-arrow-slitter-1500'];
// path => page; parent = the path of the parent page; blocks = [heading, product slugs, optional "view all" [label, live
// url]]: a "view all" to a page built here is turned into a link to that page below, like every other link
const PAGES = [
    'all-products' => ['title' => 'All Products', 'parent' => null, 'eyebrow' => 'Products', 'h1' => 'All [Products]',
        'seoTitle' => 'All Products - Arrow Systems, Inc.',
        'seoDescription' => 'Experience the ultimate blend of digital speed and analog finesse for premium labels.',
        'blocks' => [
            ['ArrowJet Digital Label [Printers]', ['arrowjet-aqua-330r-hybrid-pro-plus-m', 'arrowjet-aqua-330r-hybrid-pro-m', 'arrowjet-aqua-800m',
                'arrow-bolt-330r-hybrid-pro-m', 'arrowjet-uv', 'arrowjet-aqua-330r-lite-2', 'arrowjet-nova-330r', 'arrow-overjet-pro-700',
                'arrowjet-nova250r-inline-digital-printer-and-laser-die-cutter-2'],
                ['View all digital label printers', 'https://arrsys.com/all-products/digital-label-printers/arrow-digital-label-printers/']],
            ['Digital Label [Finishers]', FINISHERS, ['View all label finishers', 'https://arrsys.com/all-products/label-finishers/']],
        ]],
    'all-products/label-finishers' => ['title' => 'Label Finishers', 'parent' => 'all-products', 'eyebrow' => 'Product category', 'h1' => 'Label [Finishers]',
        'seoTitle' => 'High-Performance Label Finishers | Arrow Systems, Inc.',
        'seoDescription' => 'Explore Arrow Systems’ advanced Label Finishers for precise cutting, slitting, and laminating—ideal for custom labels and short-run jobs.',
        'blocks' => [['Our Label [Finishers]', FINISHERS, null]]],
    'all-products/label-finishers/arrow-label-finishers' => ['title' => 'Arrow Label Finishers', 'parent' => 'all-products/label-finishers',
        'eyebrow' => 'Product category', 'h1' => 'Digital Label Finishing [Equipments]',
        'seoTitle' => 'Digital Label Finishers: Enhance Your Labeling Process',
        'seoDescription' => 'Discover how Digital Label Finishers can elevate your printing capabilities. Learn about features, benefits, and why they’re vital for your business.',
        'banner' => ['Label-Finishers.jpg', 'Label finishers: labelled whiskey, honey and green tea packs beside an ArrowCut Nova 330R laser finisher'],
        'blocks' => [['Arrow Label [Finishers]', ['aries', 'taurus-laser-finisher', 'arrowcut-nova-330r-roll-to-roll-laser-cutter', 'arrowezcut-330rplus',
            'arrowcut-nova-250r-laser-label-cutter', 'arrow-eco-300-label-cutter', 'arrow-ezcut-330r', 'arrowezcut-350r', 'arrow-ezcut-240', 'arrow-lam-380'], null]]],
    'all-products/printers' => ['title' => 'Printers', 'parent' => 'all-products', 'eyebrow' => 'Product category', 'h1' => '[Printer]',
        'seoTitle' => 'Advanced Digital Printers | Arrow Systems, Inc.',
        'seoDescription' => 'Discover high-performance digital printers from Arrow Systems—designed for labels, packaging, and industrial printing with unmatched precision.',
        'blocks' => [['ArrowJet Digital Label [Printers]', ['arrowjet-aqua-330r-hybrid-pro-plus-m', 'arrowjet-aqua-330r-hybrid-pro-m', 'arrowjet-aqua-800m',
            'arrow-bolt-330r-hybrid-pro-m', 'arrowjet-uv', 'arrowjet-aqua-330r-lite-2', 'arrow-overjet-pro-700',
            'arrowjet-nova250r-inline-digital-printer-and-laser-die-cutter-2', 'arrowjet-nova-330r'],
            ['View all digital label printers', 'https://arrsys.com/all-products/digital-label-printers/arrow-digital-label-printers/']]]],
    'all-products/printers/gerber-printers' => ['title' => 'Gerber Printers', 'parent' => 'all-products/printers', 'eyebrow' => 'Product category',
        'h1' => 'Printers by [Gerber]',
        'seoTitle' => 'Printers by Gerber - Arrow Systems, Inc.',
        'seoDescription' => 'Printing Technology: Thermal transfer',
        'banner' => ['GERBER-PRINTERS.jpg', 'Gerber Technology EDGE FX thermal transfer printer beside aircraft safety placards'],
        'blocks' => [['Gerber [Printers]', ['gerber-edge-fx'], null]]],
];
// product slug => [Card Title (null = keep the product name), tagline, [label, value]…], live's text
const CARDS = [
    'aries' => ['Aries Semi-Rotary Finishing', 'The future of high volume label finishing with ARIES',
        [['Max Roll Diameter', '500mm'], ['Cutting Media Width', '330mm'], ['Max. Die cutting speed', '30m/min']]],
    'taurus-laser-finisher' => [null, 'Precision Meets Efficiency in Label Finishing',
        [['Max Roll Diameter', '500mm'], ['Cutting Media Width', '330mm'], ['Max. Die cutting speed', '70m/min']]],
    'arrowcut-nova-330r-roll-to-roll-laser-cutter' => ['ArrowCut Nova 330R', 'Roll to Roll Laser Finisher',
        [['Max Web Width', '350mm'], ['Max Web Speed', '30m/min'], ['Max Roll Diameter', '400 mm'], ['Cutting Area', '300mm x 300mm']]],
    'arrowezcut-330rplus' => [null, 'An advanced roll-to-roll digital label die cutter designed for precision, flexibility, and efficiency.',
        [['Max. Roll Diameter', '450 mm'], ['Max die-cutting Speed', '6m/min'], ['Slitting Width', '10 – 310 mm'], ['Slitting Blades', 'Up to 15 Blades']]],
    'arrowcut-nova-250r-laser-label-cutter' => ['ArrowCut Nova 250R', 'Laser Die Cutter',
        [['Max Web Width', '250mm'], ['Max Web Speed', '25m/min'], ['Max Roll Diameter', '300 mm'], ['Cutting Area', '225mm x 225mm']]],
    'arrow-eco-300-label-cutter' => ['Arrow Eco-300', 'Roll-to-Roll Desktop Finisher',
        [['Max Roll OD', '355mm'], ['Max Cutting Width', '305mm'], ['Max Cutting Speed', '3’/min'], ['Max Media Size', '406mm']]],
    'arrow-ezcut-330r' => ['Arrow EZCut 330R', 'Roll to Roll Multi Blade Digital Label Die-Cutter',
        [['Max Media Diameter', '450mm'], ['Cutting Media Width', '40-340mm'], ['Max. Die cutting speed', '6m/min / 100m/min'], ['Slitting Width', '10~310mm']]],
    'arrowezcut-350r' => [null, 'Multiple Blades Digital Label Cutter',
        [['Max Roll Diameter', '450mm'], ['Cutting Media Width', '100-350mm'], ['Max. Die cutting speed', '9m/min']]],
    'arrow-ezcut-240' => [null, 'Automatic Digital Roll Label Cutter',
        [['Max Roll Diameter', '290mm'], ['Media label Width', '100- 230mm'], ['Max. Cutting Speed', '8m/min (single unit)'], ['No. of Cutting Heads/ Slitter', '2 sets']]],
    'arrow-arrow-slitter-1500' => ['Arrow Slitter 1500', 'A perfect offline tool for slitting and rewinding rolls',
        [['Max. Media Width', '500mm'], ['Max Slitting Speed', '100m/min'], ['Min Slitting Width', '12mm'], ['Slitting Blades', 'Up to 10 Blades']]],
    'arrow-lam-380' => [null, 'Reel-to-Reel Thermal Laminating Machine', []],
    'gerber-edge-fx' => [null, '15″ Thermal Transfer Printer',
        [['Printing Technology', 'Thermal transfer'], ['Max. Printing Width', '11.8″ (30 cm)'], ['Material Width', '15″ (38 cm) punched media'],
         ['Resolution', '300, 600 or 1200 dpi'], ['Colors', 'Over 75 color foils available']]],
];

$elements = Craft::$app->getElements();
$product = fn(string $slug) => Entry::find()->section('products')->slug($slug)->status(null)->one() ?? exit("no product $slug\n");
$hasDraft = fn(int $id) => (new Query())->from('{{%drafts}}')->where(['canonicalId' => $id])->exists();

// product cards: fill what's empty
foreach (CARDS as $slug => [$cardTitle, $tagline, $specs]) {
    $p = $product($slug);
    $set = [];
    if ($cardTitle && !$p->headingText) $set['headingText'] = $cardTitle;
    if (!$p->cardTagline) $set['cardTagline'] = $tagline;
    if ($specs && !$p->getFieldValue('keySpecs')) $set['keySpecs'] = array_map(fn($r) => ['col1' => $r[0], 'col2' => $r[1]], $specs);
    if (!$set) continue;
    if ($hasDraft($p->id)) { say("  ! {$p->title}: open draft, card not filled"); continue; }
    $p->setFieldValues($set);
    $elements->saveElement($p) || fail("product $slug", $p);
    say("  ~ {$p->title}: " . implode(', ', array_keys($set)));
}

// live's banner image → Assets › Images › pages (downloaded once)
$bannerAsset = function (array $banner) use ($elements): craft\elements\Asset {
    [$file, $alt] = $banner;
    $volume = Craft::$app->getVolumes()->getVolumeByHandle('images');
    $folder = Craft::$app->getAssets()->ensureFolderByFullPathAndVolume('pages', $volume, false);
    if ($a = craft\elements\Asset::find()->folderId($folder->id)->filename($file)->one()) return $a;
    $bytes = @file_get_contents("https://arrsys.com/media/$file", false, stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0 (Arrow Systems staging migration)\r\n", 'timeout' => 90]]))
        ?: exit("could not download $file\n");
    $tmp = Craft::$app->getPath()->getTempPath() . '/' . uniqid('banner') . "-$file";
    file_put_contents($tmp, $bytes);
    $a = new craft\elements\Asset(['tempFilePath' => $tmp, 'filename' => $file, 'volumeId' => $volume->id, 'newFolderId' => $folder->id]);
    $a->alt = $alt;
    $a->setScenario(craft\elements\Asset::SCENARIO_CREATE);
    $elements->saveElement($a) || fail("banner $file", $a);
    say("  + asset pages/$file");
    return $a;
};
$bannerFields = fn(array $banner) => ['mediaImage' => [$bannerAsset($banner)->id], 'headerStyle' => 'banner', 'bannerHeight' => 'whole'];

// pages, parents first
$pagesSection = Craft::$app->getEntries()->getSectionByHandle('pages');
$built = [];
foreach (PAGES as $path => $spec) {
    $page = Entry::find()->section('pages')->uri($path)->status(null)->one();
    if (!$page) {
        $blocks = ['b0' => ['type' => 'industryHeader', 'fields' => ['eyebrow' => $spec['eyebrow'], 'sectionHeading' => $spec['h1']]
            + (isset($spec['banner']) ? $bannerFields($spec['banner']) : [])]];
        foreach ($spec['blocks'] as $i => [$heading, $slugs, $viewAll]) {
            $blocks['b' . ($i + 1)] = ['type' => 'productShowcase', 'fields' => ['sectionHeading' => $heading, 'productSource' => 'picked',
                'selectedProducts' => array_map(fn($s) => $product($s)->id, $slugs),
                'ctaLink' => $viewAll ? ['type' => 'url', 'value' => $viewAll[1], 'label' => $viewAll[0]] : null]];
        }
        $page = new Entry(['sectionId' => $pagesSection->id, 'typeId' => Craft::$app->getEntries()->getEntryTypeByHandle('page')->id,
            'title' => $spec['title'], 'slug' => basename($path)]);
        if ($spec['parent']) $page->setParentId(Entry::find()->section('pages')->uri($spec['parent'])->status(null)->one()?->id ?? exit("no parent page {$spec['parent']}\n"));
        $page->setFieldValues(['seoTitle' => $spec['seoTitle'], 'seoDescription' => $spec['seoDescription'],
            'pageBuilder' => ['sortOrder' => array_keys($blocks), 'entries' => $blocks]]);
        $elements->saveElement($page) || fail("page $path", $page);
        $page->uri === $path || exit("the page got /{$page->uri}/, expected /$path/\n");
        say("  + page /{$page->uri}/ (" . implode(', ', array_map(fn($b) => count($b[1]) . ' products', $spec['blocks'])) . ')');
    }
    // a banner added to the spec after the page was built: into its Page Header (not under an open draft)
    $header = Entry::find()->ownerId($page->id)->type('industryHeader')->status(null)->one();
    if (isset($spec['banner']) && $header && !$header->getFieldValue('mediaImage')->exists()) {
        if ($hasDraft($page->id)) say("  ! /$path/ has an open draft: banner not added");
        else {
            $header->setFieldValues($bannerFields($spec['banner']));
            $elements->saveElement($header) || fail("header of $path", $header);
            say("  ~ /$path/: Page Header banner {$spec['banner'][0]}");
        }
    }
    $built["https://arrsys.com/$path/"] = $page;
}
// menus and link fields pointing at a built page's live address → the page
$target = fn(?string $url) => $url ? ($built[rtrim($url, '/') . '/'] ?? null) : null;
$nodes = array_filter(Node::find()->status(null)->all(), fn($n) => $target($n->url));
foreach (array_unique(array_map(fn($n) => $n->menuId, $nodes)) as $menuId) {
    foreach ((new Query())->from('{{%navigation_build_sessions}}')->where(['menuId' => $menuId])->all() as $s) {
        !array_filter([$s['structureMoves'], $s['addedNodeIds'], $s['stagedDeletes'], $s['nodeDraftMap'], $s['menuContentDraft']])
            || exit("menu $menuId has unsaved builder changes: Save or Discard them, then run again\n");
    }
}
foreach ($nodes as $n) {
    $page = $target($n->url);
    $n->type = EntryNode::class;
    $n->elementId = $page->id;
    $n->url = null;
    $elements->saveElement($n) || fail("menu node {$n->id}", $n);
    say("  ~ menu {$n->menu->handle} “{$n->title}” → /{$page->uri}/");
}
$q = (new Query())->select('es.elementId')->from(['es' => '{{%elements_sites}}'])->innerJoin(['e' => '{{%elements}}'], '[[e.id]] = [[es.elementId]]')
    ->where(['e.revisionId' => null, 'e.draftId' => null, 'e.dateDeleted' => null])->andWhere(['like', 'es.content', 'arrsys.com/all-products']);
foreach ($q->column() as $id) {
    $el = $elements->getElementById($id, null, null, ['status' => null]);
    if (!$el || !$el->getFieldLayout()) continue;
    $changed = [];
    foreach ($el->getFieldLayout()->getCustomFields() as $f) {
        if (!$f instanceof Link || !($page = $target((string)$el->getFieldValue($f->handle)?->url))) continue;
        $v = $el->getFieldValue($f->handle);
        $el->setFieldValue($f->handle, ['type' => 'entry', 'value' => $page->id, 'label' => $v->label, 'target' => $v->target]);
        $changed[] = "$f->handle → /{$page->uri}/";
    }
    if (!$changed) continue;
    for ($top = $el; $top instanceof Entry && $top->getPrimaryOwner(); $top = $top->getPrimaryOwner());   // the page / post that holds the block
    if ($hasDraft($top->id)) { say("  ! “{$top->title}” has an open draft: its link to live is left; apply or delete the draft, then run again"); continue; }
    $elements->saveElement($el) || fail("element $id", $el);
    say('  ~ ' . ($el instanceof Entry ? "{$el->type->handle} “" . ($el->getPrimaryOwner()?->title ?? $el->title) . '”' : $el::displayName() . ' “' . ($el->title ?? $id) . '”') . ': ' . implode(', ', $changed));
}

// self-check: each page loads at its live path with its cards, the breadcrumb shows its parents, nothing links to live
foreach (PAGES as $path => $spec) {
    $html = @file_get_contents("http://localhost:8081/$path/") ?: exit("/$path/ does not load\n");
    $want = array_sum(array_map(fn($b) => count($b[1]), $spec['blocks']));
    ($n = substr_count($html, 'class="pcard"')) === $want || exit("/$path/: $n cards, expected $want\n");
    !$spec['parent'] || str_contains($html, '>' . PAGES[$spec['parent']]['title'] . '</a></li>') || exit("/$path/: breadcrumb without its parent\n");
    !isset($spec['banner']) || str_contains($html, 'page-banner__cover') && str_contains($html, pathinfo($spec['banner'][0], PATHINFO_FILENAME)) || exit("/$path/: no banner\n");
}
!array_filter(Node::find()->status(null)->all(), fn($n) => $target($n->url)) || exit("a menu item still links to a live page that is built\n");
say('  = ' . count(PAGES) . ' pages at their live paths with their product cards and breadcrumbs; menus link here');
