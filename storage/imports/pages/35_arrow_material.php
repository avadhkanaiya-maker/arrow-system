<?php
/**
 * Pages: /arrow-material/ (live https://arrsys.com/arrow-material/: a grid of 17 materials, each to its product page),
 * on its design (demo.theglobalwebdev.com/Arrowsystem/arrow-material/, read 2026-10-28; its names and texts, from the
 * product pages). General blocks only:
 *   Page Header  "Arrow Materials for Labels and [Flexible Packaging]" + the design's intro, compact, the design's swatch
 *                fan (an SVG) as the small illustration
 *   Card Grid    Link cards, 3 per row, Filter Tabs and Search (34_card_grid_filters.php): the 17 materials in live's
 *                order; Subtitle = the application (the tabs: Self Adhesive Material 9 · Flexible Packaging 7 · Pharma &
 *                Others 1), Details = Product code and the laminates' Build-up, the photo = its product's Main Image,
 *                “View material” → the product. As live, the 330 mm WPP roll has no page of its own: it links to the
 *                Plain White Laminated Roll – 330 mm and shows White PP's photo.
 *   CTA Band     "Need help choosing a material?": Get a Free Sample · Talk to a Specialist (the design's)
 *   Newsletter   last, as every page
 *   SEO          live's title, description and og:image (the White Matt Inkjet Paper photo)
 * Then the menu item and buttons that point at the live page link here (stops if one's page has an open draft or a menu
 * has unsaved builder changes). Skips the page once it exists; --update rebuilds its blocks. Back up first.
 * Run from the project root, after 32_… and 34_card_grid_filters.php:
 *   /opt/lampp/bin/php storage/imports/pages/35_arrow_material.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';
require __DIR__ . '/_helpers.php';

use craft\db\Query;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\fields\Link;
use verbb\navigation\elements\Node;

const LIVE_URL = 'https://arrsys.com/arrow-material/';
const DESIGN_PAGE = 'https://demo.theglobalwebdev.com/Arrowsystem/arrow-material/';

Craft::$app->getEntries()->getEntryTypeByHandle('cardGrid')->getFieldLayout()->getFieldByHandle('cardFilters')
    ?? exit("no Card Grid filters: run 34_card_grid_filters.php first\n");

/** A product's Main Image (asset id) */
function productPhoto(string $slug): int {
    $p = Entry::find()->section('products')->slug($slug)->status(null)->one() ?? exit("no product $slug\n");
    return $p->getFieldValue('mediaImage')->one()?->id ?? exit("product $slug has no Main Image\n");
}

/** The design's swatch-fan illustration as an SVG in Images › pages/ (added once) */
function swatchFan(): int {
    $volume = Craft::$app->getVolumes()->getVolumeByHandle('images');
    if ($asset = Asset::find()->volumeId($volume->id)->filename('arrow-material-swatches.svg')->one()) return $asset->id;
    $html = @file_get_contents(DESIGN_PAGE, false, stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0 (Arrow Systems staging migration)\r\n", 'timeout' => 60]]))
        ?: exit('could not read ' . DESIGN_PAGE . "\n");
    preg_match('#<svg class="swatch-fan" (viewBox="[^"]+")[^>]*>(.*?)</svg>#s', $html, $m) || exit("no swatch-fan SVG in the design page\n");
    $tmp = Craft::$app->getPath()->getTempPath() . '/' . uniqid('art') . '-arrow-material-swatches.svg';
    file_put_contents($tmp, "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"240\" height=\"160\" {$m[1]}>{$m[2]}</svg>");
    $folder = Craft::$app->getAssets()->ensureFolderByFullPathAndVolume('pages', $volume, false);
    $asset = new Asset(['tempFilePath' => $tmp, 'filename' => 'arrow-material-swatches.svg', 'volumeId' => $volume->id, 'newFolderId' => $folder->id]);
    $asset->setScenario(Asset::SCENARIO_CREATE);
    Craft::$app->getElements()->saveElement($asset) || fail('asset arrow-material-swatches.svg', $asset);
    return $asset->id;
}

$sa = 'Self Adhesive Material';
$fp = 'Flexible Packaging';
$coated = 'Specially coated for water-based pigment inkjet printers.';
// [name, application, product code, text or [build-up layers], product slug, photo from product (default: the same)]
$materials = [
    ['White Matt Inkjet Paper', $sa, 'AJ-A-MP-2150', 'A white, matte inkjet paper material specially designed for use in on demand color inkjet printers.', 'white-matt-inkjet-paper'],
    ['Fasfilm Plus White/Permanent', $sa, 'AJ-A-WPP-7272', 'A bi-axially oriented, glossy pearlized white polypropylene film with a print-receptive top coating.', 'fasfilm-plus-white-permanent'],
    ['Plain Laminated Roll – 230 mm', $fp, 'AJ-TP-WP-LMW', ['12 mic PET', '50 mic White Poly'], 'plain-laminated-roll-230-mm'],
    ['White Polyethylene Film', $sa, 'AJ-A-SAPE-LMW', 'A white, blown co-extruded polyethylene film with a print-receptive top coating.', 'white-polyethylene-film'],
    ['Plain White Laminated Roll – 330 mm – WPP', $fp, 'AJ-TP-WPP-LMW', ['12 mic PET', '90 mic White Poly'], 'plain-white-laminated-roll-330mm', 'white-pp'],
    ['High Gloss Inkjet Paper', $sa, 'AJ-A-GP-2350', 'A white, high-gloss inkjet paper material specially designed for use in on demand color inkjet printers.', 'high-gloss-inkjet-paper'],
    ['Maplitho + PE Inkjet Paper', $sa, 'AJ-NC-MP-M1', $coated, 'maplitho-pe-inkjet-paper'],
    ['Holographic PSA Film', $sa, 'AJ-A-HFM-LMW', 'A seamless micro-embossed rainbow pattern holographic film, specially coated for water-based pigment inkjet printers.', 'holographic-psa-film'],
    ['PSA Chromo', $sa, 'AJ-A-CPE-M1', 'A smooth white, one side machine coated semigloss paper with good printability and converting properties.', 'psa-chromo'],
    ['Blister Foil', 'Pharma & Others', 'AJ-A-HFM-LMW', $coated, 'blister-foil'],
    ['2Mil Silver Metalized', $sa, 'AJ-A-SPP-LMW', 'A glossy, silver metalized BOPP film with a print-receptive coating.', '2mil-silver-metalized'],
    ['Clear PET 12 Micron x White Poly 90 Micron', $fp, 'AJ-TP-WP9-LMW', ['12 mic Clear PET', '90 mic White Poly'], 'clear-pet-12-micron-x-white-poly-90-micron'],
    ['Plain Laminated Roll – 381 mm', $fp, 'AJ-TP-SP-LMW', ['12 mic PET', '12 mic Met PET', '100 mic Natural Poly'], 'plain-laminated-roll-381-mm'],
    ['Glassie + PE Inkjet Paper', $fp, 'AJ-NC-GP-M1', $coated, 'glassie-pe-inkjet-paper'],
    ['Silver Metalised Paper 80 GSM', $sa, 'AJ-A-SMP-LMW', 'A bright silver metalized paper with good printability and conversion.', 'silver-metalised-paper-80-gsm'],
    ['White PP', $fp, 'AJ-C-WPP-LMW', $coated, 'white-pp'],
    ['Plain White Laminated Roll – 330 mm', $fp, 'AJ-TP-WAP-LMW', ['12 mic PET', '90 mic Al. Foil', '90 mic Natural Poly'], 'plain-white-laminated-roll-330mm'],
];
$lead = 'Self-adhesive label stocks, laminated flexible packaging rolls and specialty films, coated for water-based pigment inkjet printers.';

$cards = array_map(fn($m) => ['card', [
    'headingText' => $m[0], 'subheading' => $m[1],
    'richText' => is_string($m[3]) ? '<p>' . htmlspecialchars($m[3]) . '</p>' : null,
    'specItems' => [['term' => 'Product code', 'description' => $m[2]], ...(is_array($m[3]) ? [['term' => 'Build-up', 'description' => implode(' · ', $m[3])]] : [])],
    'mediaImage' => [productPhoto($m[5] ?? $m[4])],
    'ctaLink' => designLink("https://arrsys.com/product/{$m[4]}/", 'View material'),
]], $materials);
$blocks = [
    ['industryHeader', ['eyebrow' => 'Label materials', 'sectionHeading' => 'Arrow Materials for Labels and [Flexible Packaging]', 'sectionLead' => $lead,
        'headerStyle' => 'illustration', 'mediaImage' => [swatchFan()]]],
    ['cardGrid', ['cardStyle' => 'link', 'cardColumns' => '3', 'cardFilters' => true, 'sectionBackground' => 'surface', 'cardItems' => nested($cards, 'c')]],
    ['ctaBand', ['sectionHeading' => 'Need help choosing a material?', 'sectionLead' => 'Tell us your application, materials and run lengths.',
        'ctaLink' => designLink('https://arrsys.com/try-a-free-sample/', 'Get a Free Sample'), 'secondaryCta' => designLink('tel:+18886972749', 'Talk to a Specialist')]],
    ['newsletter', []],
];

// ---- the page
$pb = Craft::$app->getFields()->getFieldByHandle('pageBuilder')->id;
$page = Entry::find()->section('pages')->slug('arrow-material')->status(null)->one();
if ($page && !in_array('--update', $argv, true)) {
    say('  = /arrow-material/ exists (--update rebuilds its blocks)');
} else {
    if ($page) (new Query())->from('{{%drafts}}')->where(['canonicalId' => $page->id])->exists() && exit("/arrow-material/ has an open draft: apply or delete it, then run again\n");
    $page ??= new Entry(['sectionId' => Craft::$app->getEntries()->getSectionByHandle('pages')->id,
        'typeId' => Craft::$app->getEntries()->getEntryTypeByHandle('page')->id, 'title' => 'Arrow Material', 'slug' => 'arrow-material']);
    $page->setFieldValues(['seoTitle' => 'Explore Arrow Material from Arrow Systems, Inc.',
        'seoDescription' => 'Get reliable and high-quality Arrow Material for advanced digital printing and labeling applications. Trusted by professionals worldwide.',
        'socialImage' => [productPhoto('white-matt-inkjet-paper')], 'cardTagline' => $lead, 'pageBuilder' => nested($blocks, 'b')]);
    Craft::$app->getElements()->saveElement($page) || fail('page arrow-material', $page);
    $page->uri === 'arrow-material' || exit("the page got /{$page->uri}/: another entry uses /arrow-material/\n");
    $saved = Entry::find()->ownerId($page->id)->fieldId($pb)->status(null)->all();
    count($saved) === count($blocks) || exit('arrow-material: ' . count($saved) . ' of ' . count($blocks) . " blocks saved\n");
    ($n = (int)$saved[1]->getFieldValue('cardItems')->status(null)->count()) === count($materials) || exit("arrow-material: $n of " . count($materials) . " materials saved\n");
    say('  ~ /arrow-material/: Page Header, Card Grid (' . count($materials) . ' materials, filters), CTA Band, Newsletter');
}

// ---- the menu item and buttons pointing at the live page → this page (label kept); stop on open drafts / unsaved menus
$isLive = fn($url) => rtrim((string)$url, '/') . '/' === LIVE_URL;
$root = function($el) { while ($el instanceof Entry && $el->getOwner()) $el = $el->getOwner(); return $el; };
foreach (array_filter(Node::find()->status(null)->all(), fn($n) => $isLive($n->url)) as $n) {
    foreach ((new Query())->from('{{%navigation_build_sessions}}')->where(['menuId' => $n->menuId])->all() as $s) {
        !array_filter([$s['structureMoves'], $s['addedNodeIds'], $s['stagedDeletes'], $s['nodeDraftMap'], $s['menuContentDraft']])
            || exit("menu {$n->menuId} has unsaved builder changes: Save or Discard them, then run again\n");
    }
    $n->type = Entry::class;
    $n->elementId = $page->id;
    $n->url = null;
    Craft::$app->getElements()->saveElement($n) || fail("menu node {$n->id}", $n);
    say("  ~ menu {$n->menu->handle} “{$n->title}” → /arrow-material/");
}
$ids = (new Query())->select('es.elementId')->from(['es' => '{{%elements_sites}}'])->innerJoin(['e' => '{{%elements}}'], '[[e.id]] = [[es.elementId]]')
    ->where(['e.revisionId' => null, 'e.draftId' => null, 'e.dateDeleted' => null])->andWhere(['like', 'es.content', '%arrsys.com%arrow-material%', false])->column();
foreach ($ids as $id) {
    $el = Craft::$app->getElements()->getElementById($id, null, null, ['status' => null]);
    if (!$el || !$el->getFieldLayout()) continue;
    $handles = array_values(array_filter(array_map(fn($f) => $f instanceof Link && $isLive($el->getFieldValue($f->handle)?->url) ? $f->handle : null, $el->getFieldLayout()->getCustomFields())));
    if (!$handles) continue;
    $top = $root($el);
    (new Query())->from('{{%drafts}}')->where(['canonicalId' => $top->id])->exists() && exit('“' . ($top->title ?? $top::displayName()) . "” has an open draft: apply or delete it, then run again\n");
    foreach ($handles as $h) {
        $v = $el->getFieldValue($h);
        $el->setFieldValue($h, ['type' => 'entry', 'value' => $page->id, 'label' => $v->label, 'target' => $v->target]);
    }
    Craft::$app->getElements()->saveElement($el) || fail("element $id", $el);
    say('  ~ ' . ($el instanceof Entry ? $el->type->handle : $el::displayName()) . " $id on “" . ($top->title ?? $top::displayName()) . '”: ' . implode(', ', $handles) . ' → /arrow-material/');
}

// ---- self-check: the page loads with every material and its tabs
$html = @file_get_contents('http://localhost:8081/arrow-material/') ?: exit("/arrow-material/ does not load\n");
($n = substr_count($html, 'class="more-card"')) === count($materials) || exit("/arrow-material/: $n of " . count($materials) . " material cards\n");
foreach (['data-filter="self-adhesive-material"', 'data-filter="flexible-packaging"', 'data-filter="pharma-others"', 'data-filter-grid'] as $needle) {
    str_contains($html, $needle) || exit("/arrow-material/: no $needle\n");
}
say('  = /arrow-material/ loads with ' . count($materials) . ' materials and the filter tabs');
