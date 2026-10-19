<?php
/**
 * Products, new product page design (Arrowsystem/digital-label-printers/product-detail.html, rebuilt to the client's
 * prototype on 2026-10-14): structure only (the content: 06_new_design_content.php).
 *
 *   Globals → Stats Strip (statsStrip): the company figures (number, label, icon), shown by the "Stats Strip" block
 *     anywhere it is placed: product / industry / blog builders and the homepage sections
 *   Images volume: + Caption (the gallery's image label, e.g. "Front view")
 *   Product: + Gallery (more photos after the Main Image), Header Specs (the 4 tiles: value, label, note, icon); the
 *     Headline is shown as the red subtitle, the Intro as the description (the old bullet Highlights: 07_simplify.php)
 *   Tabs: + Highlights tab (panel heading + items: icon, title, text)
 *   Blocks: + Stats Strip (statsStrip, common: all builders + homepage) · + What You Can Print (productApplications,
 *     product builder: industries picked, else automatic = the industries recommending this product)
 * Idempotent. Back up first. Run from the project root, then storage/imports/builder/01_common_blocks.php:
 *   /opt/lampp/bin/php storage/imports/products/05_new_design.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';
require __DIR__ . '/_helpers.php';

use craft\elements\GlobalSet;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Entries;
use craft\fields\Table;
use craft\fields\Assets;

$fields = Craft::$app->getFields();
$entries = Craft::$app->getEntries();
$f = fn(string $handle, array $opts = []) => new CustomField($fields->getFieldByHandle($handle) ?? throw new Exception("No field $handle"), $opts);

// the site's line icons (_partials/icon-sprite.twig) offered in the icon dropdowns
const ICONS = ['gauge' => 'Gauge (speed)', 'scan' => 'Scan (resolution)', 'ruler' => 'Ruler (width / size)', 'droplet' => 'Droplet (ink)',
    'printer' => 'Printer', 'layers' => 'Layers (materials)', 'plus' => 'Plus (options)', 'calendar' => 'Calendar (years)',
    'headset' => 'Headset (support / customers)', 'factory' => 'Factory', 'globe' => 'Globe (countries)', 'leaf' => 'Leaf (sustainable)',
    'wrench' => 'Wrench (service)', 'cpu' => 'Chip (technology)', 'tag' => 'Tag (labels)', 'scissors' => 'Scissors (cutting)',
    'package' => 'Package', 'shield' => 'Shield (quality)', 'check' => 'Check', 'thermometer' => 'Thermometer', 'columns' => 'Columns', 'info' => 'Info', 'lock' => 'Lock', 'sparkles' => 'Sparkles (quality)', 'shirt' => 'Shirt (apparel)', 'wine' => 'Wine (beverage)', 'pill' => 'Pill (pharma)', 'flask' => 'Flask (chemical)', 'clock' => 'Clock (time)', 'refresh' => 'Refresh', 'file' => 'File', 'image' => 'Image'];
$iconColumn = fn(string $key) => [$key => ['heading' => 'Icon', 'handle' => 'icon', 'type' => 'select', 'width' => '18%',
    'options' => array_map(fn($v, $l) => ['label' => $l, 'value' => $v, 'default' => false], array_keys(ICONS), ICONS)]];

say('Fields');
field(Table::class, ['name' => 'Stats', 'handle' => 'statItems', 'addRowLabel' => 'Add figure', 'maxRows' => 6,
    'instructions' => 'The company figures, e.g. “40+” / “Years in label & packaging equipment”.',
    'columns' => ['col1' => ['heading' => 'Number', 'handle' => 'value', 'type' => 'singleline', 'width' => '18%'],
                  'col2' => ['heading' => 'Label', 'handle' => 'label', 'type' => 'singleline', 'width' => '']] + $iconColumn('col3')]);
field(Assets::class, ['name' => 'Gallery', 'handle' => 'gallery', 'allowedKinds' => ['image'], 'restrictFiles' => true,
    'instructions' => 'More photos after the Main Image (side view, details …), drag to order. Each photo’s Caption is its label (“Front view”). The product video is added as the last thumbnail automatically.',
    'sources' => ['volume:' . Craft::$app->getVolumes()->getVolumeByHandle('images')->uid]]);
field(Table::class, ['name' => 'Header Specs', 'handle' => 'headerSpecs', 'addRowLabel' => 'Add spec', 'maxRows' => 4,
    'instructions' => 'Up to 4 tiles next to the photo, e.g. “195 ft/min” / “Max print speed” / note “(1600 × 1600)”. Empty = no tiles.',
    'columns' => ['col1' => ['heading' => 'Value', 'handle' => 'value', 'type' => 'singleline', 'width' => '20%'],
                  'col2' => ['heading' => 'Label', 'handle' => 'label', 'type' => 'singleline', 'width' => '28%'],
                  'col3' => ['heading' => 'Note', 'handle' => 'note', 'type' => 'singleline', 'width' => '']] + $iconColumn('col4')]);
field(Table::class, ['name' => 'Highlights', 'handle' => 'highlightItems', 'addRowLabel' => 'Add highlight',
    'columns' => $iconColumn('col1') + ['col2' => ['heading' => 'Title', 'handle' => 'title', 'type' => 'singleline', 'width' => '28%'],
                  'col3' => ['heading' => 'Text', 'handle' => 'text', 'type' => 'multiline', 'width' => '']]]);
field(Entries::class, ['name' => 'Applications', 'handle' => 'applications',
    'instructions' => 'Optional: the industries to show, in order. Empty = automatic: the industries whose “Recommended Products” include this product.',
    'sources' => ['section:' . $entries->getSectionByHandle('industries')->uid], 'showSearchInput' => true]);

// ---- Globals → Stats Strip ----------------------------------------------------------------------------------------
say('Global set');
if (!Craft::$app->getGlobals()->getSetByHandle('statsStrip')) {
    $set = new GlobalSet(['name' => 'Stats Strip', 'handle' => 'statsStrip']);
    $set->setFieldLayout(layout(['Content' => [$f('statItems')]], GlobalSet::class));
    Craft::$app->getGlobals()->saveSet($set) || fail('global set statsStrip', $set);
    say('  + global set statsStrip');
}

// ---- Images volume: Caption (the gallery label) -------------------------------------------------------------------
$volume = Craft::$app->getVolumes()->getVolumeByHandle('images');
$vl = $volume->getFieldLayout();
if (!$vl->getFieldByHandle('caption')) {
    $tabs = $vl->getTabs();
    if (!$tabs) { $vl = layout(['Content' => []], craft\elements\Asset::class); $tabs = $vl->getTabs(); }
    $tabs[0]->setElements(array_merge($tabs[0]->getElements(), [$f('caption', ['instructions' => 'Optional label shown with the photo, e.g. “Front view” in a product gallery.'])]));
    $vl->setTabs($tabs);
    $volume->setFieldLayout($vl);  // no validation: the existing volume shares its filesystem without a subpath (already so)
    Craft::$app->getVolumes()->saveVolume($volume, false) || fail('volume images', $volume);
    say('  ~ images volume: + Caption');
}

// ---- Product: gallery, header specs ------------------------------------------------------------
$product = $entries->getEntryTypeByHandle('product');
$pl = $product->getFieldLayout();
if (!$pl->getFieldByHandle('headerSpecs')) {
    foreach ($pl->getTabs() as $tab) {
        if ($tab->name !== 'Product') continue;
        $els = [];
        foreach ($tab->getElements() as $el) {
            $handle = $el instanceof CustomField ? $el->attribute() : null;
            if ($handle === 'subheading') { $el->label = 'Subtitle'; $el->instructions = 'The red line under the name, e.g. “High-speed digital pigment inkjet label printer”.'; }
            $els[] = $el;
            if ($handle === 'mediaImage') $els[] = $f('gallery');
            if ($handle === 'subheading') $els[] = $f('headerSpecs');
        }
        $tab->setElements($els);
    }
    $product->setFieldLayout($pl);
    $entries->saveEntryType($product) || fail('entry type product', $product);
    say('  ~ product: + Gallery, Header Specs; Headline → Subtitle');
}

// ---- Tabs: + Highlights tab ---------------------------------------------------------------------------------------
say('Tabs + blocks');
$hl = blockType('Highlights Tab', 'highlightsTab', 'star', [
    $f('headingText', ['label' => 'Tab Title', 'required' => true, 'instructions' => 'e.g. “Overview”.']),
    $f('sectionHeading', ['label' => 'Panel Heading', 'instructions' => 'e.g. “Highlights”.']),
    $f('highlightItems', ['label' => 'Highlights', 'required' => true]),
], '{headingText}', 'Icon cards (title + short text) and a link to the Specifications tab.');
$tabsField = $fields->getFieldByHandle('productTabs');
$tabTypes = $tabsField->getEntryTypes();
if (!in_array('highlightsTab', array_map(fn($t) => $t->handle, $tabTypes), true)) {
    $tabsField->setEntryTypes(array_merge([$hl], $tabTypes));
    $fields->saveField($tabsField) || fail('field productTabs', $tabsField);
    say('  ~ productTabs: + Highlights Tab');
}

// ---- Blocks: Stats Strip (common) + What You Can Print (product) ---------------------------------------------------
$stats = blockType('Stats Strip', 'statsStrip', 'chart-simple', [], 'Stats strip',
    'The company figures from Globals → Stats Strip (edit them there; they are the same everywhere).');
blockType('What You Can Print', 'productApplications', 'grip', [
    $f('eyebrow', ['instructions' => 'e.g. “Applications”.']),
    $f('sectionHeading', ['label' => 'Heading', 'instructions' => '[brackets] = red, e.g. “What You [Can Print]”.']),
    $f('sectionLead', ['label' => 'Intro', 'instructions' => '{name} = the product name.']),
    $f('applications'),
    $f('ctaLink', ['label' => 'View All Link', 'instructions' => 'Empty = “View all industries” → the Industries page.']),
], '{sectionHeading}', 'Industry cards (photo, name, summary): picked, else the industries recommending this product.');

// the Stats Strip joins the common set (Article Body → all builders via builder/01_common_blocks.php) …
$body = $fields->getFieldByHandle('articleBody');
$rows = array_map(fn($t) => ['uid' => $t->uid, 'group' => $t->group], $body->getEntryTypes());
if (!in_array($stats->uid, array_column($rows, 'uid'), true)) {
    $rows[] = ['uid' => $stats->uid, 'group' => 'Other'];
    $body->setEntryTypes($rows);
    $fields->saveField($body) || fail('field articleBody', $body);
    say('  ~ articleBody: + Stats Strip (group Other)');
}
// … and the homepage sections
$home = $fields->getFieldByHandle('homepageSections');
$homeTypes = $home->getEntryTypes();
if (!in_array('statsStrip', array_map(fn($t) => $t->handle, $homeTypes), true)) {
    $home->setEntryTypes(array_merge($homeTypes, [$stats]));
    $fields->saveField($home) || fail('field homepageSections', $home);
    say('  ~ homepageSections: + Stats Strip');
}
saveProjectConfig();
