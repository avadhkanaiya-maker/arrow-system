<?php
/**
 * Pages: /all-products/digital-flexible-packaging-printers/, from the live page
 * (https://arrsys.com/all-products/digital-flexible-packaging-printers/, read 2026-10-29; no design file of its own).
 * Live, top to bottom, on general blocks only:
 *   Page Header          live's banner (a section background there) as a Full-width banner, whole; the H1 + live's intro
 *   Content Block        "What is a Digital Flexible Packaging Printer?" + the Doypack pouches
 *   Recommended Products "Our Digital Packaging [Printers]": Aqua 800M, Bolt 330R Hybrid Pro M, Aqua 330R Hybrid Pro+ M
 *                        (their cards already carry live's taglines and specs)
 *   Content Block        "Our Entry Label In house Solution": live's Aqua 330R photo, tagline, specs, Learn More
 *   Card Grid            "Key Benefits …" (4)
 *   Content Block        "Seamless Integration with Finishing Equipment" + the two finishers
 *   Tabs                 "Industries Benefiting …": 4 text tabs, live's two small photos inline (as the product text tabs)
 *   Content Block × 2    "Why Invest in ArrowJet …" (the list) and "Conclusion", text only
 *   Newsletter
 *   SEO                  live's title, description and share image (the Doypack pouches)
 * Then every menu item and link field pointing at the live page links here (Footer › Flexible Packaging Printers, the
 * /flexible-packaging/ page's "All flexible packaging printers" …); links typed in rich text stay. Stops if a menu has
 * unsaved builder changes; skips a link field under an open draft. Skips the page when it exists. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/pages/41_digital_flexible_packaging_printers.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';
require __DIR__ . '/_helpers.php';

use craft\db\Query;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\fields\Link;
use verbb\navigation\elements\Node;
use verbb\navigation\nodetypes\Entry as EntryNode;

const LIVE_URL = 'https://arrsys.com/all-products/digital-flexible-packaging-printers/';
const URI = 'all-products/digital-flexible-packaging-printers';
const MEDIA = 'https://arrsys.com/media/';

$elements = Craft::$app->getElements();
$hasDraft = fn(int $id) => (new Query())->from('{{%drafts}}')->where(['canonicalId' => $id])->exists();
$product = fn(string $slug) => Entry::find()->section('products')->slug($slug)->status(null)->one() ?? exit("no product $slug\n");
$link = fn(string $uri, string $label) => designLink("https://arrsys.com/$uri/", $label);
// an inline photo in rich text, as the product text tabs have them (CKEditor's image markup, live's display size)
$inlineImage = function (string $file, string $alt, int $w, int $h): string {
    $asset = Asset::find()->id(pageImage(MEDIA . $file, $alt))->one();
    return sprintf('<figure class="image"><img src="{asset:%d:url||%s}" alt="%s" width="%d" height="%d"></figure>',
        $asset->id, parse_url($asset->getUrl(), PHP_URL_PATH), htmlspecialchars($alt), $w, $h);
};

$page = Entry::find()->section('pages')->uri(URI)->status(null)->one();
if (!$page) {
    $parent = Entry::find()->section('pages')->uri('all-products')->status(null)->one() ?? exit("no /all-products/ page\n");
    $doypack = pageImage(MEDIA . 'Doypack-Pouch-Mock-up_6-1024x684.webp', 'Printed coffee doypack pouches');
    $blocks = [
        ['industryHeader', ['eyebrow' => 'Products', 'sectionHeading' => 'Digital Flexible Packaging [Printer]',
            'sectionLead' => 'In today’s fast-paced printing industry, digital flexible packaging printers have revolutionized the way brands produce high-quality packaging. These advanced printers allow businesses to create custom, high-resolution, and vibrant packaging without the limitations of traditional printing methods. With the rising demand for short-run, personalized, and sustainable packaging solutions, digital printing technology is paving the way for the future of packaging production.',
            'mediaImage' => [pageImage(MEDIA . 'Digital-Flexible-packaging-Printer-1-1.webp', 'ArrowJet flexible packaging printers beside printed snack and coffee pouches')],
            'headerStyle' => 'banner', 'bannerHeight' => 'whole']],
        ['contentBlock', ['headingText' => 'What is a Digital Flexible Packaging Printer?',
            'richText' => '<p>A <strong>digital flexible packaging printer</strong> is a specialized printer designed to print directly onto flexible packaging materials such as films, pouches, and labels. Unlike conventional printing techniques such as flexography or gravure, <strong>digital printing eliminates the need for plates</strong>, allowing for faster turnaround times, lower costs, and greater design flexibility.</p>',
            'mediaImage' => [$doypack]]],
        ['productShowcase', ['sectionHeading' => 'Our Digital Packaging [Printers]', 'productSource' => 'picked', 'cardColumns' => '3',
            'selectedProducts' => array_map(fn($s) => $product($s)->id, ['arrowjet-aqua-800m', 'arrow-bolt-330r-hybrid-pro-m', 'arrowjet-aqua-330r-hybrid-pro-plus-m'])]],
        ['contentBlock', ['headingText' => 'Our Entry Label In house Solution',
            'richText' => '<p><strong>High-Resolution, Digital Inkjet Printer with higher speed</strong></p><ul>'
                . '<li><strong>Colors</strong>: CMYK</li><li><strong>Print Speed:</strong> 90ft or 150ft / min</li>'
                . '<li><strong>Max Printing Width</strong>: 12.75 inches</li>'
                . '<li><strong>Resolution:</strong> 90ft/min @ 1600 x 1600 dpi; 150ft/min @ 1600 x 954 dpi</li>'
                . '<li><strong>Ink</strong>: Waterbased Pigment Inks</li></ul>',
            'mediaImage' => [pageImage(MEDIA . 'Untitled-design-2025-02-11T184930.788-1.webp', 'ArrowJet Aqua 330R')],
            'ctaLink' => $link('product/arrowjet-aqua-330r-lite-2', 'Learn More')]],
        ['cardGrid', ['sectionHeading' => 'Key Benefits of Digital Flexible Packaging [Printers]', 'cardColumns' => '4', 'cardItems' => nested(array_map(
            fn($c) => ['card', ['headingText' => $c[0], 'richText' => "<p>$c[1]</p>"]], [
            ['High-Quality Printing with Vivid Colors', 'One of the primary advantages of <strong>digital flexible packaging printers</strong> is their ability to produce <strong>1600 x 1600 dpi ultra-high resolution</strong> prints. The advanced <strong>inkjet and water-based pigment technologies</strong> ensure vibrant colors, crisp details, and excellent print consistency, making them perfect for <strong>branding and product differentiation</strong>.'],
            ['Fast Turnaround and Short Runs', 'With <strong>no need for printing plates</strong>, digital printing enables businesses to produce short-run packaging <strong>on demand</strong>. This capability is crucial for industries that require frequent design updates, such as food, beverage, pharmaceuticals, and cosmetics.'],
            ['Variable Data Printing (VDP)', '<strong>Variable Data Printing (VDP)</strong> is a game-changer for packaging. Our ArrowJet packaging printers allows businesses to print <strong>unique QR codes, serial numbers, and personalized designs</strong> on each package, enhancing customer engagement and enabling secure anti-counterfeiting measures.'],
            ['Swiss Compliant Inks', 'Water-based inkjet printing reduces environmental impact by eliminating solvent-based inks. Additionally, digital printing minimizes material waste, making it an eco-friendly choice for businesses aiming for sustainable packaging solutions'],
        ]), 'c')]],
        ['contentBlock', ['headingText' => 'Seamless Integration with Finishing Equipment',
            'richText' => '<p>Our State of art <strong>digital flexible packaging printers</strong> can be integrated with finishing solutions such as <strong>Arrow EZCUT 350R, ArrowCut Nova 250R, DPR Aires 250R, and ArrowJet Eco 300</strong>, enabling inline printing and cutting for complete production efficiency.</p>',
            'mediaImage' => [pageImage(MEDIA . 'Untitled-design-2025-02-11T164825.761.webp', 'Digital label cutters')]]],
        ['tabsBlock', ['sectionHeading' => 'Industries Benefiting from Digital Flexible Packaging [Printers]', 'productTabs' => nested([
            ['textTab', ['headingText' => 'Food and Beverage Industry', 'richText' => '<p>With increasing demand for <strong>customized packaging, traceability, and compliance</strong>, digital printing ensures <strong>FDA-compliant, high-resolution, and moisture-resistant prints</strong> for food-safe packaging.</p>'
                . $inlineImage('Untitled-design-2025-02-11T173458.808-1.webp', 'Food Packaging', 300, 225)]],
            ['textTab', ['headingText' => 'Pharmaceutical and Healthcare Packaging', 'richText' => '<p>Pharmaceutical companies require <strong>precise micro-text printing</strong> and <strong>secure VDP features</strong> to meet regulatory standards and prevent counterfeiting.</p>'
                . $inlineImage('Untitled-design-2025-02-11T174331.245.webp', 'packaging for supplement', 300, 225)]],
            ['textTab', ['headingText' => 'Cosmetics and Personal Care Products', 'richText' => '<p>The beauty industry relies on <strong>luxury packaging with unique finishes</strong>. Digital printing offers <strong>metallic effects, glossy or matte finishes</strong>, and high-definition imagery to enhance product appeal.</p>']],
            ['textTab', ['headingText' => 'E-Commerce and Subscription Boxes', 'richText' => '<p>Personalization is key in e-commerce packaging. Digital printing allows brands to create <strong>branded, personalized, and interactive packaging</strong> for an enhanced unboxing experience.</p>']],
        ], 't')]],
        ['contentBlock', ['headingText' => 'Why Invest in ArrowJet Digital Flexible Packaging Printer?',
            'richText' => '<ul><li><strong>Reduce Production Costs</strong>: No need for expensive printing plates or setup fees.</li>'
                . '<li><strong>Boost Speed and Efficiency</strong>: Faster job setup and short-run capabilities.</li>'
                . '<li><strong>Enhance Brand Appeal</strong>: Vibrant colors, unique designs, and customizable options.</li>'
                . '<li><strong>Eco-Friendly Printing</strong>: Sustainable, water-based ink solutions for greener production.</li></ul>']],
        ['contentBlock', ['headingText' => 'Conclusion',
            'richText' => '<p>As businesses seek more efficient, customizable, and sustainable packaging solutions, our <strong>digital flexible packaging printers</strong> are emerging as the <strong>ultimate choice</strong> for modern printing needs. With advancements in <strong>inkjet technology, high-speed production, and inline finishing</strong>, digital printing is set to <strong>redefine the future of packaging</strong>.</p>']],
        ['newsletter', []],
    ];
    $page = new Entry(['sectionId' => Craft::$app->getEntries()->getSectionByHandle('pages')->id,
        'typeId' => Craft::$app->getEntries()->getEntryTypeByHandle('page')->id,
        'title' => 'Digital Flexible Packaging Printers', 'slug' => basename(URI)]);
    $page->setParentId($parent->id);
    $page->setFieldValues([
        'seoTitle' => 'Digital Flexible Packaging Printers | Arrow Systems',
        'seoDescription' => 'Explore advanced Digital Flexible Packaging Printers by Arrow Systems for high-speed, sustainable, and cost-effective custom packaging solutions.',
        'socialImage' => [$doypack],
        'pageBuilder' => nested($blocks, 'b'),
    ]);
    $elements->saveElement($page) || fail('page ' . URI, $page);
    $page->uri === URI || exit("the page got /{$page->uri}/: another entry uses /" . URI . "/\n");

    // check what was saved: the blocks, the cards and the tabs
    $pb = Craft::$app->getFields()->getFieldByHandle('pageBuilder')->id;
    $saved = Entry::find()->ownerId($page->id)->fieldId($pb)->status(null)->all();
    count($saved) === count($blocks) || exit(URI . ': ' . count($saved) . ' of ' . count($blocks) . " blocks saved\n");
    $byType = fn(string $t) => array_values(array_filter($saved, fn($b) => $b->type->handle === $t))[0];
    ($n = (int)$byType('cardGrid')->getFieldValue('cardItems')->status(null)->count()) === 4 || exit("$n of 4 benefit cards saved\n");
    ($n = (int)$byType('tabsBlock')->getFieldValue('productTabs')->status(null)->count()) === 4 || exit("$n of 4 industry tabs saved\n");
    say('  + page /' . URI . '/: ' . implode(', ', array_map(fn($b) => $b->type->name, $saved)));
}

// menus: stop on unsaved builder changes, then Custom URL items to the live page → this page
$isLive = fn(?string $url) => $url && rtrim($url, '/') . '/' === LIVE_URL;
$nodes = array_filter(Node::find()->status(null)->all(), fn($n) => $isLive($n->url) && $n->type !== EntryNode::class);
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
    ->andWhere(['like', 'es.content', 'arrsys.com/all-products/digital-flexible-packaging-printers'])->column();
foreach ($found as $id) {
    $el = $elements->getElementById($id, null, null, ['status' => null]);
    if (!$el || !$el->getFieldLayout()) continue;
    $changed = [];
    foreach ($el->getFieldLayout()->getCustomFields() as $f) {
        if ($f instanceof Link && $isLive((string)$el->getFieldValue($f->handle)?->url)) $changed[] = $f->handle;
    }
    if (!$changed) continue;
    for ($top = $el; $top instanceof Entry && $top->getPrimaryOwner(); $top = $top->getPrimaryOwner());
    if ($top instanceof Entry && $hasDraft($top->getCanonicalId())) { say("  ! {$top->title} has an open draft: its " . implode(', ', $changed) . ' still links to live'); continue; }
    foreach ($changed as $handle) {
        $v = $el->getFieldValue($handle);
        $el->setFieldValue($handle, ['type' => 'entry', 'value' => $page->id, 'label' => $v->label, 'target' => $v->target]);
    }
    $elements->saveElement($el) || fail("element $id", $el);
    say('  ~ ' . ($el instanceof Entry ? "{$el->type->handle} $id ({$top->title})" : $el::displayName() . ' “' . ($el->title ?? $id) . '”') . ': ' . implode(', ', $changed) . ' → the page');
}

// self-check: the page loads with every live part; no menu item left on the live address
$html = @file_get_contents('http://localhost:8081/' . URI . '/') ?: exit('/' . URI . "/ does not load\n");
($n = substr_count($html, 'class="pcard"')) === 3 || exit("$n printer cards, expected 3\n");
foreach (['page-banner', 'Digital-Flexible-packaging-Printer-1-1', 'What is a Digital Flexible Packaging Printer?', 'Our Entry Label In house Solution',
          'arrowjet-aqua-330r-lite-2', 'Swiss Compliant Inks', 'Seamless Integration', 'E-Commerce and Subscription Boxes',
          'Untitled-design-2025-02-11T174331.245', 'Eco-Friendly Printing', 'redefine the future of packaging'] as $needle) {
    str_contains($html, $needle) || exit('/' . URI . "/: no “{$needle}” on the page\n");
}
!array_filter(Node::find()->status(null)->all(), fn($n) => $isLive($n->url) && $n->type !== EntryNode::class) || exit("a menu item still links to live\n");
say('  = /' . URI . '/: banner, 3 printer cards, entry printer, 4 benefits, 4 industry tabs, Why Invest, Conclusion; menus link here');
