<?php
/**
 * Pages: /all-products/printers/thermal-transfer-printer/, from live's product category page
 * (https://arrsys.com/product-category/printers/thermal-transfer-printer/, read 2026-10-29; client request). Live is a
 * plain shop category: breadcrumb, the H1, its one product (Matan SprinG3), the newsletter; no text, no banner, no menu
 * item links to it. Here, as the other printer category pages (25_all_products.php: Trojan, QuickLabel …):
 *   Page Header          eyebrow "Product category", "Thermal Transfer [Printer]"
 *   Recommended Products the products of the Thermal Transfer Printer category (Show = Products from the categories
 *                        below), so a product added to the category later shows here too, as on live
 *   Newsletter
 *   SEO                  live's title (live has no description)
 * Live's URL redirects here (config/redirects.php). Skips the page when it exists. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/pages/42_thermal_transfer_printer.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\elements\Entry;

const URI = 'all-products/printers/thermal-transfer-printer';

if (Entry::find()->section('pages')->uri(URI)->status(null)->exists()) exit('/' . URI . "/ exists already\n");
$parent = Entry::find()->section('pages')->uri('all-products/printers')->status(null)->one() ?? exit("no /all-products/printers/ page\n");
$category = Entry::find()->section('productCategories')->slug('thermal-transfer-printer')->status(null)->one() ?? exit("no Thermal Transfer Printer category\n");

$page = new Entry([
    'sectionId' => Craft::$app->getEntries()->getSectionByHandle('pages')->id,
    'typeId' => Craft::$app->getEntries()->getEntryTypeByHandle('page')->id,
    'title' => 'Thermal Transfer Printer', 'slug' => basename(URI),
]);
$page->setParentId($parent->id);
$page->setFieldValues([
    'seoTitle' => 'Thermal Transfer Printer - Arrow Systems, Inc.',
    'pageBuilder' => ['sortOrder' => ['b0', 'b1', 'b2'], 'entries' => [
        'b0' => ['type' => 'industryHeader', 'fields' => ['eyebrow' => 'Product category', 'sectionHeading' => 'Thermal Transfer [Printer]']],
        'b1' => ['type' => 'productShowcase', 'fields' => ['sectionHeading' => 'Thermal Transfer [Printers]', 'productSource' => 'category',
            'selectedCategories' => [$category->id], 'productLimit' => 12]],
        'b2' => ['type' => 'newsletter'],
    ]],
]);
Craft::$app->getElements()->saveElement($page) || fail('page ' . URI, $page);
$page->uri === URI || exit("the page got /{$page->uri}/, expected /" . URI . "/\n");
say("  + page /{$page->uri}/ (" . Entry::find()->section('products')->relatedTo(['targetElement' => $category, 'field' => 'productCategories'])->status('live')->count() . ' products in the category)');
