<?php
/**
 * Page builder, general (2026-10-28, for the Flexible Packaging design, demo.theglobalwebdev.com/Arrowsystem/flexible-packaging/,
 * the "standard information page"; any page or industry can use them):
 *   Page Header        + Highlights (Icon, Title, Text): a row of points under the heading. With the "Cut-out image beside
 *                      text" style the image then shows on wide screens only (the points need the width)
 *   Content Block      + Points (Icon, Title, Text) under the text; a block with points has its image on the right, framed
 *   Product Spotlight  a new block: small heading, heading, intro; points | image (empty = the product's photo) | a card with
 *                      the product's name, Specifications (Icon + Title rows) and two buttons
 *   Card Grid          + Card Style "Image tiles" (the title on the image, e.g. industries) and "Image cards" (no box: the
 *                      image framed, title, text, e.g. pouch formats); + Cards per Row 5 and 6
 *   Card               + Details (Term, Description rows, e.g. Application / Properties), shown by Standard cards
 * Points, Highlights and Specifications are the Highlights table (highlightItems, as the products' Highlights tab); it is
 * saved into project config first (it was in the database only). Templates: industries/blocks/{industryHeader,
 * contentBlock,productSpotlight,cardGrid}; styles in craft.css.
 * Idempotent. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/pages/32_information_page_blocks.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';
require dirname(__DIR__) . '/products/_helpers.php';

use craft\fieldlayoutelements\CustomField;
use craft\models\EntryType;

$fields = Craft::$app->getFields();
$entries = Craft::$app->getEntries();
$f = fn(string $h) => $fields->getFieldByHandle($h) ?? exit("no field $h\n");
$type = fn(string $h) => $entries->getEntryTypeByHandle($h) ?? exit("no entry type $h\n");

/** Put layout elements next to the field $next (after it); fields already in the layout are skipped (as 30_cards_tab.php) */
function placeAfter(EntryType $et, string $next, array $elements): void {
    $layout = $et->getFieldLayout();
    $elements = array_values(array_filter($elements, fn(CustomField $e) => !$layout->getFieldByHandle($e->getField()->handle)));
    if (!$elements) return;
    foreach ($layout->getTabs() as $tab) {
        $els = $tab->getElements();
        foreach ($els as $i => $el) {
            if (!$el instanceof CustomField || $el->getField()->handle !== $next) continue;
            array_splice($els, $i + 1, 0, $elements);
            $tab->setElements($els);
            $et->setFieldLayout($layout);
            Craft::$app->getEntries()->saveEntryType($et) || fail("entry type {$et->handle}", $et);
            say("  ~ {$et->handle}: + " . implode(', ', array_map(fn($e) => $e->label ?: $e->getField()->name, $elements)) . " after $next");
            return;
        }
    }
    exit("{$et->handle}: no field $next in its layout\n");
}

/** Add [value => label] options to a Dropdown field (existing values kept) */
function addOptions(string $handle, array $opts): void {
    $field = Craft::$app->getFields()->getFieldByHandle($handle);
    $have = array_column($field->options, 'value');
    $new = array_filter($opts, fn($v) => !in_array((string)$v, $have, true), ARRAY_FILTER_USE_KEY);
    if (!$new) return;
    foreach ($new as $v => $l) $field->options[] = ['label' => $l, 'value' => (string)$v, 'default' => false];
    Craft::$app->getFields()->saveField($field) || fail("field $handle", $field);
    say("  ~ $handle: + " . implode(', ', $new));
}

// ---- the Highlights table into project config (it existed in the database only, so new layouts could not ship it)
$hl = $f('highlightItems');
if (!Craft::$app->getProjectConfig()->get("fields.{$hl->uid}")) {
    $fields->saveField($hl) || fail('field highlightItems', $hl);
    say('  ~ highlightItems: saved to project config');
}
$hlHelp = 'One per row: an Icon, a short Title and a line of Text.';

// ---- Page Header: Highlights; Content Block: Points
placeAfter($type('industryHeader'), 'listItems', [new CustomField($hl, ['label' => 'Highlights', 'instructions' => "Optional points under the heading. $hlHelp"])]);
placeAfter($type('contentBlock'), 'richText', [new CustomField($hl, ['label' => 'Points',
    'instructions' => "Optional points under the text. $hlHelp A block with points shows its image on the right, in a frame."])]);

// ---- Product Spotlight
$spotlight = blockType('Product Spotlight', 'productSpotlight', 'star', [
    new CustomField($f('eyebrow'), ['label' => 'Small Heading', 'instructions' => 'e.g. “With ArrowJet Aqua 800M”.']),
    new CustomField($f('sectionHeading'), ['label' => 'Heading', 'instructions' => 'e.g. “Print Beyond [Limits]” ([words] in red).']),
    new CustomField($f('sectionLead'), ['label' => 'Intro']),
    new CustomField($hl, ['label' => 'Points', 'instructions' => "Shown left of the image. $hlHelp"]),
    new CustomField($f('selectedProducts'), ['label' => 'Product', 'instructions' => 'The product in the spotlight (the first one picked): its name links to its page.']),
    new CustomField($f('mediaImage'), ['label' => 'Image', 'instructions' => 'Empty = the product’s Main Image. A cut-out (transparent or white background) works best.']),
    new CustomField($hl, ['label' => 'Specifications', 'handle' => 'specChips', 'instructions' => 'Under the product name: an Icon and the spec as the Title per row, e.g. Gauge · “Speed: up to 150 meters/min”. Text is not used.']),
    new CustomField($f('primaryCta'), ['label' => 'Button 1', 'instructions' => 'e.g. “Request Quote” → Request a Quote.']),
    new CustomField($f('secondaryCta'), ['label' => 'Button 2', 'instructions' => 'e.g. “View Printer” → the product.']),
], '{sectionHeading}', 'A product with its key points, photo, specifications and buttons.');
addTab($spotlight, 'Section Settings', array_map($f, ['spacingTop', 'spacingBottom', 'sectionBackground', 'cssClass', 'anchorId']));
foreach (['pageBuilder', 'industryBuilder'] as $handle) {
    $field = $f($handle);
    $rows = array_map(fn($t) => ['uid' => $t->uid, 'group' => $t->group], $field->getEntryTypes());
    if (in_array($spotlight->uid, array_column($rows, 'uid'), true)) continue;
    $after = array_search($type('productShowcase')->uid, array_column($rows, 'uid'), true);
    array_splice($rows, $after === false ? count($rows) : $after + 1, 0, [['uid' => $spotlight->uid, 'group' => $after === false ? null : $rows[$after]['group']]]);
    $field->setEntryTypes($rows);
    $fields->saveField($field) || fail("field $handle", $field);
    say("  ~ $handle: + Product Spotlight");
}

// ---- Card Grid: two more styles, 5 and 6 per row; Card: Details
addOptions('cardStyle', ['tile' => 'Image tiles', 'plain' => 'Image cards']);
addOptions('cardColumns', ['5' => '5', '6' => '6']);
$style = $f('cardStyle');
$help = 'Link cards: the image (or else the card’s Icon) on top, the Subtitle as a small heading above the title and the link’s Label (e.g. “View films”) at the bottom; the heading is centred. For “More from …” sections. Image tiles: the title on the image (e.g. industries). Image cards: no box, the image framed, then title and text (e.g. pouch formats).';
if ($style->instructions !== $help) {
    $style->instructions = $help;
    $fields->saveField($style) || fail('field cardStyle', $style);
}
placeAfter($type('card'), 'richText', [new CustomField($f('specItems'), ['label' => 'Details',
    'instructions' => 'Optional rows under the text of a Standard card, e.g. “Application” / “Food Packaging”.'])]);

saveProjectConfig();

// ---- check
Craft::$app->getProjectConfig()->get("fields.{$hl->uid}") || exit("highlightItems: not in project config\n");
foreach (['industryHeader' => ['highlightItems'], 'contentBlock' => ['highlightItems'], 'card' => ['specItems'],
          'productSpotlight' => ['highlightItems', 'specChips', 'selectedProducts', 'mediaImage', 'primaryCta', 'secondaryCta', 'anchorId']] as $h => $want) {
    $layout = $type($h)->getFieldLayout();
    foreach ($want as $w) $layout->getFieldByHandle($w) || exit("$h: no $w\n");
}
foreach (['pageBuilder', 'industryBuilder'] as $handle) {
    in_array('productSpotlight', array_map(fn($t) => $t->handle, $f($handle)->getEntryTypes()), true) || exit("$handle: no Product Spotlight\n");
}
array_diff(['tile', 'plain'], array_column($f('cardStyle')->options, 'value')) && exit("cardStyle: options missing\n");
array_diff(['5', '6'], array_column($f('cardColumns')->options, 'value')) && exit("cardColumns: options missing\n");
say('  = Header Highlights, Content Block Points, Product Spotlight, Card Grid tiles / image cards / 5-6 per row, Card Details');
