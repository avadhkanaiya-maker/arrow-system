<?php
/**
 * Pages: /thermal-lamination-film/, from the live page (https://arrsys.com/thermal-lamination-film/, read 2026-10-23; no
 * design file). Live: the H1 "Thermal Lamination Film" and a grid of 12 films (photo, name, Learn More). General blocks
 * only, as /aviation-films/ (19_aviation_films.php):
 *   Page Header           "Thermal Lamination [Film]", no image (live has none: the text across the full width)
 *   Recommended Products  "Our Lamination [Films]": the 10 live films that have a product page, picked, in the live
 *                         order (the card shows the product's own photo, name and Learn More, as live). Left out: PET
 *                         Pouch Film and Hot Stamping Foil, whose Learn More on live goes nowhere (no product page)
 *   SEO                   the live title and description
 * As live: 4 cards per row (Recommended Products gets the Card Grid's "Cards per Row" option: 2 / 3 / 4, default 3, so
 * other pages keep 3) and the BOPP film's card says "BOPP Thermal Lamination Film" (its Card Title; the product page
 * keeps its full name "Bopp Eva Thermal laminating film Glossy & Matt 15mic-30mic").
 * Then Main Menu › Materials › Thermal Lamination Film (a Custom URL to live) links here; stops if the Main Menu has
 * unsaved builder changes. Skips the page when it exists. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/pages/23_thermal_lamination_film.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\db\Query;
use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use verbb\navigation\elements\Node;
use verbb\navigation\Navigation;
use verbb\navigation\nodetypes\Entry as EntryNode;

const LIVE_URL = 'https://arrsys.com/thermal-lamination-film/';
const PRODUCTS = [   // live order, staging slugs
    'bopp-thermal-lamination-film', 'pet-thermal-lamination-film', 'pet-metalized-film', 'hologram-laser-film',
    'super-sticky-digital-film', 'soft-touch-film', 'anti-scratch-film', 'digital-sleeking-film', 'bopp-snow-white-film',
    'pvc-binding-cover',
];

// Recommended Products: "Cards per Row" (the Card Grid's field), after How Many
$type = Craft::$app->getEntries()->getEntryTypeByHandle('productShowcase');
if (!$type->getFieldLayout()->getFieldByHandle('cardColumns')) {
    $layout = $type->getFieldLayout();
    foreach ($layout->getTabs() as $tab) {
        $els = [];
        foreach ($tab->getElements() as $el) {
            $els[] = $el;
            if ($el instanceof CustomField && $el->getField()->handle === 'productLimit') $els[] = new CustomField(Craft::$app->getFields()->getFieldByHandle('cardColumns'));
        }
        $tab->setElements($els);
    }
    $type->setFieldLayout($layout);
    Craft::$app->getEntries()->saveEntryType($type) || fail('entry type productShowcase', $type);
    saveProjectConfig();
    say('  + Recommended Products: Cards per Row');
}

// the BOPP film's card name, as live (Card Title: product cards only)
$bopp = Entry::find()->section('products')->slug('bopp-thermal-lamination-film')->status(null)->one();
if (!$bopp->headingText) {
    !(new Query())->from('{{%drafts}}')->where(['canonicalId' => $bopp->id])->exists() || exit("the BOPP product has an open draft: apply or delete it, then run again\n");
    $bopp->setFieldValue('headingText', 'BOPP Thermal Lamination Film');
    Craft::$app->getElements()->saveElement($bopp) || fail('product bopp', $bopp);
    say('  ~ BOPP product: Card Title “BOPP Thermal Lamination Film”');
}

$page = Entry::find()->section('pages')->slug('thermal-lamination-film')->status(null)->one();
if (!$page) {
    $ids = [];
    foreach (PRODUCTS as $slug) $ids[] = Entry::find()->section('products')->slug($slug)->status(null)->one()?->id ?? exit("no product $slug\n");

    $page = new Entry(['sectionId' => Craft::$app->getEntries()->getSectionByHandle('pages')->id,
        'typeId' => Craft::$app->getEntries()->getEntryTypeByHandle('page')->id, 'title' => 'Thermal Lamination Film', 'slug' => 'thermal-lamination-film']);
    $page->setFieldValues([
        'seoTitle' => 'Thermal Lamination Film - Arrow Systems, Inc.',
        'seoDescription' => 'Thermal Lamination Film BOPP Thermal Lamination Film Learn More PET Thermal Lamination Film Learn More PET Metalized Film Learn More Hologram Laser Film Learn',
        'pageBuilder' => ['sortOrder' => ['b1', 'b2'], 'entries' => [
            'b1' => ['type' => 'industryHeader', 'fields' => ['eyebrow' => 'Materials', 'sectionHeading' => 'Thermal Lamination [Film]']],
            'b2' => ['type' => 'productShowcase', 'fields' => ['sectionHeading' => 'Our Lamination [Films]', 'productSource' => 'picked', 'selectedProducts' => $ids, 'cardColumns' => '4']],
        ]],
    ]);
    Craft::$app->getElements()->saveElement($page) || fail('page thermal-lamination-film', $page);
    $page->uri === 'thermal-lamination-film' || exit("the page got /{$page->uri}/: another entry uses /thermal-lamination-film/\n");
    $pb = Craft::$app->getFields()->getFieldByHandle('pageBuilder')->id;
    ($saved = (int)Entry::find()->ownerId($page->id)->fieldId($pb)->status(null)->count()) === 2 || exit("thermal-lamination-film: $saved of 2 blocks saved\n");
    $grid = Entry::find()->ownerId($page->id)->fieldId($pb)->type('productShowcase')->status(null)->one();
    $grid->getFieldValue('selectedProducts')->status(null)->ids() === $ids || exit("thermal-lamination-film: the products didn't save in order\n");
    say("  + page /{$page->uri}/ (Page Header, Recommended Products: " . count($ids) . ')');
}

$grid = Entry::find()->ownerId($page->id)->fieldId(Craft::$app->getFields()->getFieldByHandle('pageBuilder')->id)->type('productShowcase')->status(null)->one();
if (($grid->getFieldValue('cardColumns')->value ?? null) !== '4') {
    !(new Query())->from('{{%drafts}}')->where(['canonicalId' => $page->id])->exists() || exit("the page has an open draft: apply or delete it, then run again\n");
    $grid->setFieldValue('cardColumns', '4');
    Craft::$app->getElements()->saveElement($grid) || fail('products block', $grid);
    say('  ~ Recommended Products: 4 per row');
}

// menu items pointing at the live page → this page (not while the menu has unsaved builder changes)
foreach (Node::find()->status(null)->all() as $n) {
    if (rtrim((string)$n->url, '/') . '/' !== LIVE_URL) continue;
    foreach ((new Query())->from('{{%navigation_build_sessions}}')->where(['menuId' => $n->menuId])->all() as $s) {
        !array_filter([$s['structureMoves'], $s['addedNodeIds'], $s['stagedDeletes'], $s['nodeDraftMap'], $s['menuContentDraft']])
            || exit("menu {$n->menu->handle} has unsaved builder changes: Save or Discard them, then run again\n");
    }
    $n->type = EntryNode::class;
    $n->elementId = $page->id;
    $n->url = null;
    Craft::$app->getElements()->saveElement($n) || fail("menu node {$n->id}", $n);
    say("  ~ menu {$n->menu->handle} “{$n->title}” → the page");
}

// self-check: the page answers with the 10 films, and the menu links here
$html = @file_get_contents('http://localhost:8081/thermal-lamination-film/') ?: exit("/thermal-lamination-film/ does not load\n");
($n = substr_count($html, 'class="pcard"')) === count(PRODUCTS) || exit("/thermal-lamination-film/: $n product cards, expected " . count(PRODUCTS) . "\n");
!Node::find()->status(null)->all() || !array_filter(Node::find()->status(null)->all(), fn($n) => rtrim((string)$n->url, '/') . '/' === LIVE_URL) || exit("a menu item still links to live\n");
say('  = /thermal-lamination-film/: ' . count(PRODUCTS) . ' film cards; no menu item links to the live page');
