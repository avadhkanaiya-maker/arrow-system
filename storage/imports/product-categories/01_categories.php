<?php
/**
 * Product categories (from WooCommerce) + homepage section C "Equipment" with selectable categories.
 *
 *   Product Categories (productCategories)   Structure, 2 levels, NO pages yet (category pages come later; their URL
 *       format, /product-category/… as WooCommerce or /all-products/… as the live Elementor pages, is decided then)
 *       the 13 live WooCommerce product categories (data/product_cat.json = REST /wp-json/wp/v2/product_cat), as live:
 *       same names, slugs and parent (Digital Printing → Membrane Touch Switch), A–Z per level
 *       Product Category: Card Title (optional shorter name for cards), Card Photo, Description, Card Link (until the
 *       category pages exist: where the card links to)
 *       the products import will assign products to these categories (by WooCommerce slug)
 *   Homepage Sections → "Equipment Categories" block (categoryShowcase), section C: pick the categories to show
 *       (empty = the first 3 in the structure order), placed after B, with the design's 3 cards
 *
 * Idempotent (existing fields / types / entries are kept; --update refreshes the 3 design cards). Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/product-categories/01_categories.php [--update]
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\elements\Asset;
use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Entries;
use craft\helpers\Assets as AssetsHelper;

const DESIGN = '/home/avadh/Desktop/Arrowsystem_local';
$update = in_array('--update', $argv, true);
$fields = Craft::$app->getFields();
$entries = Craft::$app->getEntries();
$elements = Craft::$app->getElements();
$f = fn(string $handle, array $opts = []) => new CustomField($fields->getFieldByHandle($handle) ?? throw new Exception("No field $handle"), $opts);

// ---- Structure ------------------------------------------------------------------------------------------------------
say('Product Categories');
$type = entryType('Product Category', 'productCategory', ['Content' => [
    $f('headingText', ['label' => 'Card Title', 'instructions' => 'Optional: a shorter name for cards, e.g. “Digital Label Printers”. Empty = the category name.']),
    $f('featuredImage', ['label' => 'Card Photo', 'instructions' => 'The photo on the category card (homepage). 5:4 works best, e.g. 600 × 480.']),
    $f('sectionLead', ['label' => 'Description', 'instructions' => 'Optional: a short description of the category.']),
    $f('ctaLink', ['label' => 'Card Link', 'instructions' => 'Where the card links to until the category pages are built in Craft, e.g. the live /all-products/… page.']),
]], ['icon' => 'folder-tree', 'showSlug' => true, 'titleLabel' => 'Category Name',
    'description' => 'A product category (from WooCommerce). Nest it under another one for a sub-category.']);
$section = section('Product Categories', 'productCategories', 'structure', [$type], null, null, ['maxLevels' => 2]);

say('Homepage block: Equipment Categories (section C)');
field(Entries::class, ['name' => 'Categories to Display', 'handle' => 'selectedCategories', 'maxRelations' => 6,
    'instructions' => 'Pick the product categories to show and drag them into order (the design shows 3). Empty = the first 3 categories.',
    'sources' => ["section:{$section->uid}"], 'showSearchInput' => true]);
$block = entryType('Equipment Categories', 'categoryShowcase', ['Content' => [
    $f('eyebrow'),
    $f('sectionHeading', ['required' => true]),
    $f('selectedCategories'),
    $f('ctaLink', ['label' => 'View All Link', 'instructions' => 'The link under the cards, e.g. “View all label printers”.']),
    $f('anchorId'),
]], ['icon' => 'grip', 'titleLabel' => 'Name in the list',
    'description' => 'Section C: product category cards (photo + name), picked in this block.']);
$matrix = $fields->getFieldByHandle('homepageSections');
$types = $matrix->getEntryTypes();
if (!in_array('categoryShowcase', array_map(fn($t) => $t->handle, $types), true)) {
    $types[] = $block;
    $matrix->setEntryTypes($types);
    $fields->saveField($matrix) || fail('homepageSections', $matrix);
    say('  ~ homepageSections: + Equipment Categories');
}
saveProjectConfig();
$fields->refreshFields();

// ---- The 13 WooCommerce categories (as live) ------------------------------------------------------------------------
say('Categories from WooCommerce');
$wc = json_decode(file_get_contents(__DIR__ . '/data/product_cat.json'), true);
$byWpId = [];
$add = function (int $parentWpId, ?Entry $parent) use (&$add, &$byWpId, $wc, $section, $type, $elements) {
    $children = array_filter($wc, fn($c) => (int)$c['parent'] === $parentWpId);
    usort($children, fn($a, $b) => strcasecmp(html_entity_decode($a['name']), html_entity_decode($b['name'])));
    foreach ($children as $c) {
        $e = Entry::find()->section('productCategories')->slug($c['slug'])->status(null)->one();
        if (!$e) {
            $e = new Entry(['sectionId' => $section->id, 'typeId' => $type->id,
                'title' => html_entity_decode($c['name'], ENT_QUOTES | ENT_HTML5), 'slug' => $c['slug']]);
            $e->setFieldValue('sectionLead', trim(strip_tags(html_entity_decode((string)$c['description']))) ?: null);
            if ($parent) $e->setParentId($parent->id);
            $elements->saveElement($e) || fail("category {$c['slug']}", $e);
            say(sprintf('  + %-28s %s(%d products on live)', $c['slug'], $parent ? "in {$parent->slug} " : '', $c['count']));
        }
        $byWpId[$c['id']] = $e;
        $add((int)$c['id'], $e);
    }
};
$add(0, null);

// ---- The design's 3 homepage cards (index.html section C): card title, photo, link ---------------------------------
/** An image from the design folder into Images → categories/ (once, by file name), with alt text */
function catImage(string $file, string $alt): ?Asset {
    $volume = Craft::$app->getVolumes()->getVolumeByHandle('images');
    $folder = Craft::$app->getAssets()->ensureFolderByFullPathAndVolume('categories', $volume, false);
    $name = AssetsHelper::prepareAssetName(basename($file));
    $asset = Asset::find()->folderId($folder->id)->filename($name)->one();
    if (!$asset) {
        $tmp = Craft::$app->getPath()->getTempPath() . '/' . uniqid('cat') . '-' . $name;
        copy($file, $tmp);
        $asset = new Asset(['tempFilePath' => $tmp, 'filename' => $name, 'volumeId' => $volume->id, 'newFolderId' => $folder->id]);
        $asset->setScenario(Asset::SCENARIO_CREATE);
        Craft::$app->getElements()->saveElement($asset) || fail("asset $name", $asset);
    }
    if (!$asset->alt) { $asset->alt = $alt; Craft::$app->getElements()->saveElement($asset); }
    return $asset;
}
$cards = [   // WooCommerce slug => [card title, design photo, alt, live page the design links to]
    'digital-label-printers' => ['Digital Label Printers', 'category-digital-label-printers.webp', 'ArrowJet digital label printer', 'https://arrsys.com/all-products/digital-label-printers/arrow-digital-label-printers/'],
    'label-finishing-equipments' => ['Digital Label Finishers', 'category-digital-label-finishers.webp', 'Arrow EZCut digital label finisher', 'https://arrsys.com/all-products/label-finishers/arrow-label-finishers/'],
    'print-and-cut-systems' => ['Print & Cut Inline', 'category-print-cut-inline.webp', 'ArrowJet Nova print and cut inline system', 'https://arrsys.com/all-products/print-and-cut-systems/'],
];
say('Design cards');
$picked = [];
foreach ($cards as $slug => [$cardTitle, $photo, $alt, $link]) {
    $e = Entry::find()->section('productCategories')->slug($slug)->status(null)->one() ?? fail("category $slug", new craft\base\Model());
    $picked[] = $e->id;
    if (!$update && $e->featuredImage->exists()) { say("  = $slug"); continue; }
    $asset = catImage(DESIGN . "/assets/categories/web/$photo", $alt);
    $e->setFieldValues([
        'headingText' => $cardTitle,
        'featuredImage' => [$asset->id],
        'ctaLink' => ['type' => 'url', 'value' => $link, 'label' => $cardTitle],
    ]);
    $elements->saveElement($e) || fail("category $slug", $e);
    say("  ~ $slug: “{$cardTitle}”, photo, link");
}

// ---- Homepage: section C after B ------------------------------------------------------------------------------------
say('Homepage');
$home = Entry::find()->section('homepage')->one();
$onHome = $home->homepageSections->status(null)->all();
if (array_filter($onHome, fn($s) => $s->type->handle === 'categoryShowcase')) {
    say('  = Equipment Categories already on the homepage');
} else {
    $sortOrder = [];
    foreach ($onHome as $s) {
        $sortOrder[] = $s->id;
        if ($s->type->handle === 'benefitStrip') $sortOrder[] = 'new1';
    }
    if (!in_array('new1', $sortOrder, true)) $sortOrder[] = 'new1';
    $home->setFieldValue('homepageSections', [
        'entries' => ['new1' => ['type' => 'categoryShowcase', 'title' => 'C · Equipment categories', 'fields' => [
            'eyebrow' => 'Equipment',
            'sectionHeading' => 'Arrow [Label & Packaging] Printers',
            'selectedCategories' => $picked,
            'ctaLink' => ['type' => 'url', 'value' => 'https://arrsys.com/all-products/digital-label-printers/arrow-digital-label-printers/', 'label' => 'View all label printers'],
            'anchorId' => 'equipment',
        ]]],
        'sortOrder' => $sortOrder,
    ]);
    $elements->saveElement($home) || fail('homepage', $home);
    say('  + C · Equipment categories (after B), showing ' . count($picked) . ' categories');
}
