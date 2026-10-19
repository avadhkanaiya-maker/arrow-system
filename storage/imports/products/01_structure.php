<?php
/**
 * Products, structure: section, Product entry type, the flexible tabs (Matrix), a Documents volume for the PDFs.
 * Design: Arrowsystem_local/digital-label-printers/product-detail.html (header + image + Request A Sample form, tabs,
 * related products, help band). Built on the practice project's product work (craft-cms-practice: data, brands,
 * the Elementor conversion), mapped to the design's fields instead of the practice page builder.
 *
 *   Products (products)       Channel   /product/{slug}/ (as live)   products/_entry
 *   Product: Name, Slug, Small Heading, Categories, Brand, SKU · Main Image, Headline, Intro, Highlights · Tabs
 *            (Text / Specifications / Downloads tabs, in any order, any titles: the live pages use Overview / Features /
 *            Brochure but also Facestock, Adhesive, Data Sheets…) · Video · Card (title, photo, tagline, key specs) · Related
 *            products · SEO (+ Canonical URL, Robots override)
 *   Documents volume (fs "uploads", subpath documents/) for brochures and data sheets
 *
 * Since 04_page_builder.php the Tabs field and the Video fields live in Page Builder blocks, not on the Product layout.
 *
 * Idempotent (existing items are kept). Back up first. Run from the project root:
 *   /opt/lampp/bin/php storage/imports/products/01_structure.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';
require __DIR__ . '/_helpers.php';

use craft\fieldlayoutelements\CustomField;
use craft\fields\Assets;
use craft\fields\Dropdown;
use craft\fields\Entries;
use craft\fields\Matrix;
use craft\fields\PlainText;
use craft\fields\Table;
use craft\models\Volume;

$fields = Craft::$app->getFields();
$entries = Craft::$app->getEntries();
$f = fn(string $handle, array $opts = []) => new CustomField($fields->getFieldByHandle($handle) ?? throw new Exception("No field $handle"), $opts);

say('Documents volume');
$volumes = Craft::$app->getVolumes();
$docs = $volumes->getVolumeByHandle('documents');
if (!$docs) {
    $docs = new Volume(['name' => 'Documents', 'handle' => 'documents', 'fsHandle' => 'uploads', 'subpath' => 'documents']);
    $volumes->saveVolume($docs) || fail('volume documents', $docs);
    say('  + volume documents (web/uploads/documents/)');
}
$images = $volumes->getVolumeByHandle('images');
$imageSettings = ['allowedKinds' => ['image'], 'restrictFiles' => true, 'viewMode' => 'large', 'previewMode' => 'full',
    'sources' => ["volume:{$images->uid}"], 'defaultUploadLocationSource' => "volume:{$images->uid}", 'defaultUploadLocationSubpath' => 'products'];

say('Fields');
field(Dropdown::class, ['name' => 'Brand', 'handle' => 'brand', 'options' => options([
    '' => '(none)', 'arrow' => 'Arrow Systems', '3m' => '3M', 'anytron' => 'Anytron', 'gerber' => 'Gerber',
    'limitronic' => 'Limitronic', 'trojan' => 'Trojan Label', 'matan' => 'Matan', 'quicklabel' => 'QuickLabel'], '')]);
field(PlainText::class, ['name' => 'SKU', 'handle' => 'sku']);
field(PlainText::class, ['name' => 'Card Tagline', 'handle' => 'cardTagline', 'multiline' => true, 'initialRows' => 2,
    'instructions' => 'One short sentence on the product card, e.g. “Entry-level commercial inkjet label printer.”']);
field(Table::class, ['name' => 'Key Specs', 'handle' => 'keySpecs', 'addRowLabel' => 'Add spec',
    'instructions' => 'Up to 4 specs shown on the product card, e.g. Colors / CMYK.',
    'columns' => ['col1' => ['heading' => 'Label', 'handle' => 'label', 'type' => 'singleline', 'width' => '40%'],
                  'col2' => ['heading' => 'Value', 'handle' => 'value', 'type' => 'singleline', 'width' => '']]]);
field(Assets::class, ['name' => 'File', 'handle' => 'downloadFile', 'maxRelations' => 1, 'allowedKinds' => ['pdf', 'word', 'excel', 'image'],
    'restrictFiles' => true, 'sources' => ["volume:{$docs->uid}"], 'defaultUploadLocationSource' => "volume:{$docs->uid}",
    'instructions' => 'The PDF (or other document) to download.']);
field(PlainText::class, ['name' => 'Robots (advanced)', 'handle' => 'seoRobots',
    'instructions' => 'Leave empty (normal). Only to copy a special live robots value, e.g. “noindex, nofollow”.']);

say('Tabs');
$downloadItem = blockType('Download', 'downloadItem', 'file-pdf', [
    $f('headingText', ['label' => 'Label', 'required' => true, 'instructions' => 'e.g. “ArrowJet Aqua 330R Brochure”.']),
    $f('downloadFile', ['required' => true]),
    $f('caption', ['label' => 'Note', 'instructions' => 'Small text under the label, e.g. “English” or “Español”.']),
], '{headingText}');
if (!$fields->getFieldByHandle('downloadItems')) {
    $m = new Matrix(['name' => 'Downloads', 'handle' => 'downloadItems', 'viewMode' => Matrix::VIEW_MODE_BLOCKS, 'createButtonLabel' => 'Add download', 'minEntries' => 1]);
    $m->setEntryTypes([$downloadItem]);
    $fields->saveField($m) || fail('field downloadItems', $m);
    say('  + field downloadItems');
}
$tabTitle = fn() => $f('headingText', ['label' => 'Tab Title', 'required' => true, 'instructions' => 'e.g. “Overview”, “Features”, “Brochure”.']);
$textTab = blockType('Text Tab', 'textTab', 'align-left', [$tabTitle(), $f('richText', ['label' => 'Content', 'required' => true])], '{headingText}');
$specsTab = blockType('Specifications Tab', 'specsTab', 'table-list', [$tabTitle(), $f('specItems', ['label' => 'Specifications', 'required' => true])], '{headingText}');
$downloadsTab = blockType('Downloads Tab', 'downloadsTab', 'download', [$tabTitle(), $f('downloadItems')], '{headingText}');
if (!$fields->getFieldByHandle('productTabs')) {
    $m = new Matrix(['name' => 'Tabs', 'handle' => 'productTabs', 'viewMode' => Matrix::VIEW_MODE_BLOCKS, 'createButtonLabel' => 'Add tab',
        'instructions' => 'The tabs under the product header, in this order. Text = rich text (e.g. Overview); Specifications = label / value rows (e.g. Features); Downloads = PDFs (e.g. Brochure).']);
    $m->setEntryTypes([$textTab, $specsTab, $downloadsTab]);
    $fields->saveField($m) || fail('field productTabs', $m);
    say('  + field productTabs');
}

say('Products');
$categories = $entries->getSectionByHandle('productCategories');
field(Entries::class, ['name' => 'Categories', 'handle' => 'productCategories',
    'instructions' => 'The first one is the main category (breadcrumb, small heading, “More …” products).',
    'sources' => ["section:{$categories->uid}"], 'showSearchInput' => true]);
$type = entryType('Product', 'product', [
    'Product' => [
        $f('eyebrow', ['instructions' => 'Small label above the name, e.g. “Digital label printer”. Empty = the main category.']),
        $f('productCategories'),
        $f('brand'),
        $f('sku'),
        $f('mediaImage', ['label' => 'Main Image', 'instructions' => 'The large product photo (cut-out on white works best). Set its alt text.']),
        $f('subheading', ['label' => 'Headline', 'instructions' => 'The bold line next to the photo, e.g. “ArrowJet Aqua 330R Lite: High-Speed, Digital Pigment Inkjet Label Printer”.']),
        $f('richText', ['label' => 'Intro', 'instructions' => 'The paragraphs under the headline.']),
        $f('listItems', ['label' => 'Highlights', 'instructions' => 'Short bullet points, e.g. “CMYK – High Speed”.']),
    ],
    'Tabs' => [$f('productTabs')],
    'Video' => [
        $f('videoUrl', ['instructions' => 'Optional YouTube link, shown under the intro (click to play).']),
        $f('videoPoster', ['instructions' => 'The still shown before the video plays (16:9).']),
        $f('videoCaption'),
    ],
    'Card' => [
        $f('featuredImage', ['label' => 'Card Photo', 'instructions' => 'The photo on product cards (4:3, e.g. 800 × 600). Empty = the Main Image.']),
        $f('cardTagline'),
        $f('keySpecs'),
    ],
], ['icon' => 'print', 'showSlug' => true, 'titleLabel' => 'Product Name',
    'description' => 'A product page at /product/slug/ (as on the live site).']);
$section = section('Products', 'products', 'channel', [$type], 'product/{slug}', 'products/_entry');
field(Entries::class, ['name' => 'Related Products', 'handle' => 'relatedProducts', 'maxRelations' => 3,
    'instructions' => 'Optional: up to 3 products under this one. Empty = other products in the main category.',
    'sources' => ["section:{$section->uid}"], 'showSearchInput' => true]);
addTab($entries->getEntryTypeByHandle('product'), 'Card', [$fields->getFieldByHandle('relatedProducts')]);
// Card Title (as the practice project's cardTitle): a shorter name for product cards, e.g. the design listing's names
$productType = $entries->getEntryTypeByHandle('product');
if (!$productType->getFieldLayout()->getFieldByHandle('headingText')) {
    $layout = $productType->getFieldLayout();
    foreach ($layout->getTabs() as $tab) {
        if ($tab->name !== 'Card') continue;
        $tab->setElements(array_merge([$f('headingText', ['label' => 'Card Title', 'instructions' => 'Optional: a shorter name for product cards, e.g. “ArrowJet Hybrid Pro M”. Empty = the product name.'])], $tab->getElements()));
    }
    $productType->setFieldLayout($layout);
    $entries->saveEntryType($productType) || fail('entry type product', $productType);
    say("  ~ product: + Card Title (headingText) on 'Card'");
}
$seo = array_map(fn($h) => $fields->getFieldByHandle($h), ['seoTitle', 'seoDescription', 'socialImage', 'seoNoIndex', 'seoCanonical', 'seoRobots']);
addTab($entries->getEntryTypeByHandle('product'), 'SEO', $seo);

saveProjectConfig();
