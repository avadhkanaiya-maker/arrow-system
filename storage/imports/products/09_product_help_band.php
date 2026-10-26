<?php
/**
 * SUPERSEDED by 10_product_help_band_per_product.php (2026-10-22: per product, not in Site Settings).
 * Product pages' closing band "Not sure which product fits?" managed in the CP (review 2026-10-22: "should be managed by
 * admin, not fixed; keep the front end as it is"). It's on every product, so its content lives in Globals → Site
 * Settings, a new "Product Help Band" tab: Heading ({type} = "printer" on printer products, else "product"; [red] words
 * work), Text, Button, Second Button (page, URL, email or phone). Filled with what the template showed (no visible change):
 *   Not sure which {type} fits? · Tell us your application, materials and run lengths. ·
 *   Talk to a Specialist (call the toll-free number) · Request a Quote (the Request a Quote Link)
 * products/_entry.twig reads them; empty Heading = no band, an empty button = left out. Idempotent. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/products/09_product_help_band.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\fieldlayoutelements\CustomField;
use craft\models\FieldLayoutTab;

$fields = Craft::$app->getFields();
$globals = Craft::$app->getGlobals();
$set = $globals->getSetByHandle('siteSettings');
$layout = $set->getFieldLayout();
if (!$layout->getFieldByHandle('sectionHeading')) {
    $tab = new FieldLayoutTab(['name' => 'Product Help Band', 'layout' => $layout]);
    $tab->setElements([
        new CustomField($fields->getFieldByHandle('sectionHeading'), ['label' => 'Heading', 'instructions' =>
            'The maroon band at the bottom of every product page. {type} becomes “printer” on printer products and “product” on the others; [words] in brackets are underlined. Empty = no band.']),
        new CustomField($fields->getFieldByHandle('sectionLead'), ['label' => 'Text']),
        new CustomField($fields->getFieldByHandle('ctaLink'), ['label' => 'Button', 'instructions' => 'White button, e.g. “Talk to a Specialist” as a phone link. Empty = no button.']),
        new CustomField($fields->getFieldByHandle('secondaryCta'), ['label' => 'Second Button', 'instructions' => 'Outlined button, e.g. “Request a Quote”. Empty = no button.']),
    ]);
    $tabs = $layout->getTabs();
    array_splice($tabs, 3, 0, [$tab]);   // after "Calls to action"
    $layout->setTabs($tabs);
    $set->setFieldLayout($layout);
    $globals->saveSet($set) || fail('global set siteSettings', $set);
    saveProjectConfig();
    say('  + Site Settings: Product Help Band tab (Heading, Text, Button, Second Button)');
}

// today's band, as values
$set = $globals->getSetByHandle('siteSettings');
if (!trim((string)$set->getFieldValue('sectionHeading'))) {
    $phone = (string)$set->getFieldValue('tollFreePhone');
    $digits = preg_replace('/\D/', '', $phone);   // as the templates' links.tel macro
    $tel = str_starts_with(trim($phone), '+') || (strlen($digits) === 11 && $digits[0] === '1') ? "+$digits" : "+1$digits";
    $quote = $set->getFieldValue('quoteLink');
    $set->setFieldValues([
        'sectionHeading' => 'Not sure which {type} fits?',
        'sectionLead' => 'Tell us your application, materials and run lengths.',
        'ctaLink' => ['type' => 'tel', 'value' => $tel, 'label' => 'Talk to a Specialist'],
        'secondaryCta' => $quote && $quote->url
            ? ['type' => $quote->type, 'value' => $quote->getElement()?->id ?? $quote->value, 'label' => 'Request a Quote', 'target' => $quote->target]
            : null,
    ]);
    Craft::$app->getElements()->saveElement($set) || fail('siteSettings values', $set);
    say("  ~ Product Help Band: filled with the current band (Talk to a Specialist $tel · Request a Quote)");
}

$set = $globals->getSetByHandle('siteSettings');
$set->getFieldValue('secondaryCta')?->url || exit("Second Button has no link\n");
say('  = Product Help Band: “' . $set->getFieldValue('sectionHeading') . '” · ' . $set->getFieldValue('ctaLink')->label . ' · ' . $set->getFieldValue('secondaryCta')->label . ' → ' . $set->getFieldValue('secondaryCta')->url);
