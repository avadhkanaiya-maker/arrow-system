<?php
/**
 * Products, Page Builder: everything below the product header is one builder field instead of the separate Tabs and
 * Video tabs, so editors add, remove and drag the blocks themselves (like the blog's Article Body).
 *
 *   Page Builder (productBuilder)   Matrix, Blocks view, "Add block" menu as an icon grid (modules/builderui)
 *     Layout:  Tabs (tabsBlock: the Tabs field, i.e. Text / Specifications / Downloads tabs, as before)
 *     Content: Rich Text (richText, shared with the blog) · Specifications (specsBlock: Heading + rows) ·
 *              Downloads (downloadsBlock: Heading + files) · FAQ (faq, shared with the blog)
 *     Media:   Video (productVideo: YouTube URL, Poster, Caption, Show In) · Image (image, shared with the blog)
 *              Show In = Header (default: under the buttons, where the design puts the video) or Page (full width, in block order)
 *   Fixed header stays: name, image, Request A Sample form, headline, intro, highlights, buttons.
 *   Product layout: the "Tabs" and "Video" tabs are replaced by one "Page Builder" tab (after "Product").
 *
 * Content: each product's Video (if any) then its Tabs become a Video block + a Tabs block (the same order as the
 * design: the video above the tabs); the old Tabs / Video values are then emptied. Products already holding builder
 * content are skipped (idempotent). Back up first. Run from the project root:
 *   /opt/lampp/bin/php storage/imports/products/04_page_builder.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';
require __DIR__ . '/_helpers.php';

use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Dropdown;
use craft\fields\Matrix;
use craft\models\FieldLayoutTab;

$fields = Craft::$app->getFields();
$entries = Craft::$app->getEntries();
$elements = Craft::$app->getElements();
$f = fn(string $handle, array $opts = []) => new CustomField($fields->getFieldByHandle($handle) ?? throw new Exception("No field $handle"), $opts);

// ---- Structure ------------------------------------------------------------------------------------------------------
say('Blocks');
$tabsBlock = blockType('Tabs', 'tabsBlock', 'folder-open', [
    $f('productTabs', ['label' => 'Tabs', 'instructions' => 'One tab per block, in this order. Text = rich text (e.g. Overview); Specifications = label / value rows (e.g. Features); Downloads = PDFs (e.g. Brochure).']),
], null, 'A set of tabs (e.g. Overview / Features / Brochure).');
$video = blockType('Product Video', 'productVideo', 'circle-play', [
    $f('videoUrl', ['required' => true, 'instructions' => 'YouTube link. Shown as a still with a play button; the video loads on click.']),
    $f('videoPoster', ['instructions' => 'The still shown before the video plays (16:9). Empty = YouTube\'s own still.']),
    $f('videoCaption'),
], '{videoCaption}', 'A click-to-play YouTube video.');
// the video's place: the header (design) or the page (in block order); added to blocks made before this option existed
field(Dropdown::class, ['name' => 'Show In', 'handle' => 'placement', 'options' => options([
    'header' => 'Header (under the buttons, as the design)', 'page' => 'Page (full width, in block order)'], 'header'),
    'instructions' => 'Header = beside the product photo, under the buttons (as the design). Page = a full-width video at this block\'s place.']);
if (!$video->getFieldLayout()->getFieldByHandle('placement')) {
    $layout = $video->getFieldLayout();
    $tab = $layout->getTabs()[0];
    $tab->setElements(array_merge($tab->getElements(), [new CustomField($fields->getFieldByHandle('placement'))]));
    $video->setFieldLayout($layout);
    $entries->saveEntryType($video) || fail('entry type productVideo', $video);
    say('  ~ productVideo: + Show In');
}
$specs = blockType('Specifications', 'specsBlock', 'table-list', [
    $f('headingText', ['label' => 'Heading', 'instructions' => 'e.g. “Technical Specifications”.']),
    $f('specItems', ['label' => 'Specifications', 'required' => true]),
], '{headingText}', 'Label / value rows (outside the tabs).');
$downloads = blockType('Downloads', 'downloadsBlock', 'download', [
    $f('headingText', ['label' => 'Heading', 'instructions' => 'e.g. “Brochures”.']),
    $f('downloadItems'),
], '{headingText}', 'PDFs and other files to download (outside the tabs).');
$shared = fn(string $h) => $entries->getEntryTypeByHandle($h) ?? throw new Exception("No entry type $h");

say('Field');
if (!$fields->getFieldByHandle('productBuilder')) {
    $m = new Matrix(['name' => 'Page Builder', 'handle' => 'productBuilder', 'viewMode' => Matrix::VIEW_MODE_BLOCKS, 'createButtonLabel' => 'Add block',
        'instructions' => 'Everything below the product header, top to bottom. Add blocks, drag to reorder.']);
    $m->setEntryTypes(array_map(fn($row) => ['uid' => $row[1]->uid, 'group' => $row[0]], [
        ['Layout', $tabsBlock],
        ['Content', $shared('richText')], ['Content', $specs], ['Content', $downloads], ['Content', $shared('faq')],
        ['Media', $video], ['Media', $shared('image')],
    ]));
    $fields->saveField($m) || fail('field productBuilder', $m);
    say('  + field productBuilder');
}
saveProjectConfig();
$fields->refreshFields();

// ---- Product layout: a "Page Builder" tab after "Product"; the old "Tabs" / "Video" tabs go once their content moved --
/** Rebuilds the Product layout: $add puts the Page Builder tab after "Product", $drop removes the given tab names */
function productLayout(bool $add, array $drop): void {
    $entries = Craft::$app->getEntries();
    $type = $entries->getEntryTypeByHandle('product');
    $layout = $type->getFieldLayout();
    $add = $add && !$layout->getFieldByHandle('productBuilder');
    $names = array_map(fn($t) => $t->name, $layout->getTabs());
    if (!$add && !array_intersect($drop, $names)) return;
    $tabs = [];
    foreach ($layout->getTabs() as $tab) {
        if (in_array($tab->name, $drop, true)) { say("  ~ product layout: tab “{$tab->name}” removed"); continue; }
        $tabs[] = $tab;
        if ($add && $tab->name === 'Product') {
            $builderTab = new FieldLayoutTab(['name' => 'Page Builder', 'layout' => $layout]);
            $builderTab->setElements([new CustomField(Craft::$app->getFields()->getFieldByHandle('productBuilder'))]);
            $tabs[] = $builderTab;
            say('  ~ product layout: tab “Page Builder” added');
        }
    }
    $layout->setTabs($tabs);
    $type->setFieldLayout($layout);
    $entries->saveEntryType($type) || fail('entry type product', $type);
}
productLayout(true, []);

// ---- Content: Video + Tabs → blocks ---------------------------------------------------------------------------------
say('Products');
/** The Tabs field's value as a new nested value (copies the tab entries and their downloads) */
function tabsCopy(Entry $owner): array {
    $out = []; $order = [];
    foreach ($owner->productTabs->status(null)->all() as $i => $t) {
        $k = 'new' . ($i + 1);
        $order[] = $k;
        $fields = ['headingText' => $t->headingText];
        if ($t->type->handle === 'textTab') $fields['richText'] = $t->getFieldValue('richText')?->getRawContent();
        if ($t->type->handle === 'specsTab') $fields['specItems'] = array_map(fn($r) => ['term' => $r['term'], 'description' => $r['description']], $t->specItems ?? []);
        if ($t->type->handle === 'downloadsTab') {
            $items = []; $itemOrder = [];
            foreach ($t->downloadItems->status(null)->all() as $j => $d) {
                $dk = 'new' . ($j + 1);
                $itemOrder[] = $dk;
                $items[$dk] = ['type' => 'downloadItem', 'fields' => ['headingText' => $d->headingText, 'downloadFile' => $d->downloadFile->ids(), 'caption' => $d->caption]];
            }
            $fields['downloadItems'] = ['entries' => $items, 'sortOrder' => $itemOrder];
        }
        $out[$k] = ['type' => $t->type->handle, 'fields' => $fields];
    }
    return ['entries' => $out, 'sortOrder' => $order];
}
$done = 0; $skipped = 0;
$moved = !$entries->getEntryTypeByHandle('product')->getFieldLayout()->getFieldByHandle('productTabs');   // already done
foreach ($moved ? [] : Entry::find()->section('products')->status(null)->all() as $p) {
    if ($p->productBuilder->status(null)->exists()) { $skipped++; continue; }
    $blocks = []; $order = [];
    if ($p->videoUrl) {
        $blocks['new1'] = ['type' => 'productVideo', 'fields' => ['videoUrl' => $p->videoUrl, 'videoPoster' => $p->videoPoster->ids(), 'videoCaption' => $p->videoCaption]];
        $order[] = 'new1';
    }
    $tabs = tabsCopy($p);
    if ($tabs['entries']) {
        $blocks['new2'] = ['type' => 'tabsBlock', 'fields' => ['productTabs' => $tabs]];
        $order[] = 'new2';
    }
    $p->setFieldValues([
        'productBuilder' => ['entries' => $blocks, 'sortOrder' => $order],
        'productTabs' => [], 'videoUrl' => null, 'videoPoster' => [], 'videoCaption' => null,   // moved into the builder
    ]);
    $elements->saveElement($p) || fail("product {$p->slug}", $p);
    $done++;
}
say($moved ? '  = content already moved' : "  ~ $done products: Video + Tabs → Page Builder blocks ($skipped already had builder content)");
productLayout(false, ['Tabs', 'Video']);

saveProjectConfig();
