<?php
/**
 * Product List block (common page-builder block): dynamic products with photos (2026-10-21).
 * Each Product (nested productItem) can link to a product ENTRY: its photo (Card Photo, else Main Image), name (Card
 * Title, else title) and text (Card Tagline) are then shown automatically, like the Recommended Products cards.
 * The item's own fields override them: + Image (new, optional), Name (no longer required), Description; Tag Line stays.
 * A pasted URL (no entry) works as before, with the manual Name / Image / Description.
 * Keeps the item's existing layout elements (their uids hold the content). Idempotent. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/builder/02_product_list_dynamic.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\fieldlayoutelements\CustomField;

$fields = Craft::$app->getFields();
$entries = Craft::$app->getEntries();
$item = $entries->getEntryTypeByHandle('productItem') ?? exit("no productItem entry type\n");
$layout = $item->getFieldLayout();
if ($layout->getFieldByHandle('mediaImage')) exit(say('= productItem already dynamic') ?? 0);

$tabs = $layout->getTabs();
$els = [];
foreach ($tabs[0]->getElements() as $el) {
    $handle = $el instanceof CustomField ? $el->attribute() : null;
    if ($handle === 'ctaLink') {
        $el->label = 'Product or Link';
        $el->instructions = 'Choose a product entry: its photo, name and short description appear automatically (fill the fields below only to change them). Or paste a URL, then fill in the Name.';
        $els[] = $el;
        $els[] = new CustomField($fields->getFieldByHandle('mediaImage'), ['label' => 'Image',
            'instructions' => 'Optional. Empty = the product’s card photo (when a product entry is chosen above).']);
        continue;
    }
    if ($handle === 'productName') {
        $el->required = false;
        $el->instructions = 'Optional when a product entry is chosen (empty = the product’s name). Needed for a URL.';
    }
    if ($handle === 'sectionBody') $el->instructions = 'Optional. Empty = the product’s Card Tagline.';
    $els[] = $el;
}
// put "Product or Link" first, as the main choice
usort($els, fn($a, $b) => ($b instanceof CustomField && $b->attribute() === 'ctaLink') <=> ($a instanceof CustomField && $a->attribute() === 'ctaLink'));
$tabs[0]->setElements($els);
$layout->setTabs($tabs);
$item->setFieldLayout($layout);
$item->description = 'A product: choose its entry (photo, name and text come from it) or paste a link.';
$entries->saveEntryType($item) || fail('entry type productItem', $item);
saveProjectConfig();
say('  ~ productItem: Product or Link first; + Image; Name optional; Description falls back to the Card Tagline');
