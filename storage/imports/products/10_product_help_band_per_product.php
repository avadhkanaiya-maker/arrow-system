<?php
/**
 * Product pages' closing band "Not sure which product fits?" managed per product (review 2026-10-22: "not global,
 * manageable per product; front end the same"). Replaces 09_product_help_band.php's Site Settings tab:
 *   - Site Settings: the "Product Help Band" tab removed
 *   - Products: a "Help Band" tab (after Page Builder): Heading ([red] words; empty = no band), Text, Button, Second
 *     Button (page, URL, email or phone; each left out when empty)
 *   - every product filled with what it shows today: "Not sure which printer fits?" (main category's slug has
 *     "printer", as the template decided) or "…product fits?", "Tell us your application, materials and run lengths.",
 *     Talk to a Specialist (call the toll-free number), Request a Quote (the Request a Quote Link)
 * products/_entry.twig reads the product's own fields. New products: fill the tab to get the band.
 * Stops if a product has an open draft (applying it later would undo the change). Idempotent. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/products/10_product_help_band_per_product.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\db\Query;
use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\models\FieldLayoutTab;

$fields = Craft::$app->getFields();
$entries = Craft::$app->getEntries();
$globals = Craft::$app->getGlobals();

// open drafts of products: stop
$drafts = (new Query())->from(['d' => '{{%drafts}}'])->innerJoin(['e' => '{{%entries}}'], '[[e.id]] = [[d.canonicalId]]')
    ->where(['e.sectionId' => $entries->getSectionByHandle('products')->id])->count();
(int)$drafts === 0 || exit("$drafts product draft(s) open: apply or delete them first\n");

// Site Settings: no global band tab
$set = $globals->getSetByHandle('siteSettings');
$layout = $set->getFieldLayout();
$tabs = array_values(array_filter($layout->getTabs(), fn($t) => $t->name !== 'Product Help Band'));
if (count($tabs) !== count($layout->getTabs())) {
    $layout->setTabs($tabs);
    $set->setFieldLayout($layout);
    $globals->saveSet($set) || fail('global set siteSettings', $set);
    say('  - Site Settings: Product Help Band tab');
}

// Products: Help Band tab
$type = $entries->getEntryTypeByHandle('product');
$layout = $type->getFieldLayout();
if (!$layout->getFieldByHandle('sectionHeading')) {
    $tab = new FieldLayoutTab(['name' => 'Help Band', 'layout' => $layout]);
    $tab->setElements([
        new CustomField($fields->getFieldByHandle('sectionHeading'), ['label' => 'Heading', 'instructions' =>
            'The maroon band at the bottom of this product page, e.g. “Not sure which product fits?”; [words] in brackets are underlined. Empty = no band.']),
        new CustomField($fields->getFieldByHandle('sectionLead'), ['label' => 'Text']),
        new CustomField($fields->getFieldByHandle('ctaLink'), ['label' => 'Button', 'instructions' => 'White button, e.g. “Talk to a Specialist” as a phone link. Empty = no button.']),
        new CustomField($fields->getFieldByHandle('secondaryCta'), ['label' => 'Second Button', 'instructions' => 'Outlined button, e.g. “Request a Quote”. Empty = no button.']),
    ]);
    $tabs = $layout->getTabs();
    $at = array_search('Page Builder', array_map(fn($t) => $t->name, $tabs), true);
    array_splice($tabs, $at === false ? count($tabs) : $at + 1, 0, [$tab]);
    $layout->setTabs($tabs);
    $type->setFieldLayout($layout);
    $entries->saveEntryType($type) || fail('entry type product', $type);
    say('  + Products: Help Band tab (Heading, Text, Button, Second Button)');
}
saveProjectConfig();

// every product: today's band
$settings = $globals->getSetByHandle('siteSettings');
$phone = (string)$settings->getFieldValue('tollFreePhone');
$digits = preg_replace('/\D/', '', $phone);   // as the templates' links.tel macro
$tel = str_starts_with(trim($phone), '+') || (strlen($digits) === 11 && $digits[0] === '1') ? "+$digits" : "+1$digits";
$quote = $settings->getFieldValue('quoteLink');
$quoteValue = $quote && $quote->url ? ['type' => $quote->type, 'value' => $quote->getElement()?->id ?? $quote->value, 'label' => 'Request a Quote', 'target' => $quote->target] : null;
$filled = 0;
foreach (Entry::find()->section('products')->status(null)->all() as $product) {
    if (trim((string)$product->getFieldValue('sectionHeading'))) continue;
    $category = $product->getFieldValue('productCategories')->status(null)->one();
    $isPrinter = $category && str_contains($category->slug, 'printer');
    $product->setFieldValues([
        'sectionHeading' => 'Not sure which ' . ($isPrinter ? 'printer' : 'product') . ' fits?',
        'sectionLead' => 'Tell us your application, materials and run lengths.',
        'ctaLink' => $phone ? ['type' => 'tel', 'value' => $tel, 'label' => 'Talk to a Specialist'] : null,
        'secondaryCta' => $quoteValue,
    ]);
    Craft::$app->getElements()->saveElement($product, false) || fail("product {$product->id}", $product);   // the band only; old imported products may not pass every rule
    $filled++;
}
say("  ~ $filled products: Help Band filled with the current band");

$total = (int)Entry::find()->section('products')->status(null)->count();
$withBand = (int)Entry::find()->section('products')->status(null)->sectionHeading(':notempty:')->count();
$withBand === $total || exit("$withBand of $total products have a Help Band heading\n");
say("  = all $total products have their Help Band");
