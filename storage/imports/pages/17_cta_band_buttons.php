<?php
/**
 * CTA Band: both buttons set by the admin (review 2026-10-22: "why two buttons when there's a button field?"). The Pages /
 * industries CTA Band always added "Talk to a Specialist" (a call to Site Settings' toll-free number) before the block's
 * Button. Now the block has Button and Second Button (the Page Header's secondaryCta link field: page, URL, email or phone),
 * each shown only when filled in; nothing is added automatically (industries/blocks/ctaBand.twig, and the homepage's
 * CTA band shows a Second Button too).
 * Existing CTA Bands on Pages / industries keep their look: Button = "Talk to a Specialist" (phone), Second Button = their
 * old Button. The homepage's CTA Band never had the call button: unchanged.
 * Idempotent. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/pages/17_cta_band_buttons.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;

$entries = Craft::$app->getEntries();
$type = $entries->getEntryTypeByHandle('ctaBand') ?? exit("no ctaBand\n");
$layout = $type->getFieldLayout();
if (!$layout->getFieldByHandle('secondaryCta')) {
    $tabs = $layout->getTabs();
    foreach ($tabs as $tab) {
        $els = [];
        foreach ($tab->getElements() as $el) {
            $els[] = $el;
            if ($el instanceof CustomField && $el->attribute() === 'ctaLink') {
                $el->label = 'Button';
                $el->instructions = 'The first button (white). Empty = no button.';
                $els[] = new CustomField(Craft::$app->getFields()->getFieldByHandle('secondaryCta'), ['label' => 'Second Button',
                    'instructions' => 'Optional second button (outlined), e.g. “Talk to a Specialist” as a phone link.']);
            }
        }
        $tab->setElements($els);
    }
    $layout->setTabs($tabs);
    $type->setFieldLayout($layout);
    $entries->saveEntryType($type) || fail('entry type ctaBand', $type);
    saveProjectConfig();
    say('  ~ CTA Band: + Second Button');
}

// buttons (ctaLink, used by CTA Bands, cards, content blocks …) accept phone and email links, as the Page Header's do
$fields = Craft::$app->getFields();
$f = $fields->getFieldByHandle('ctaLink');
if (!in_array('tel', $f->types, true)) {
    $f->types = array_values(array_unique(array_merge($f->types, ['tel', 'email'])));
    $fields->saveField($f) || fail('field ctaLink', $f);
    saveProjectConfig();
    say('  ~ Button (ctaLink): + phone and email links');
}

// existing CTA Bands on Pages / industries: what the template used to add, now as their own buttons (same look)
$phone = (string)Craft::$app->getGlobals()->getSetByHandle('siteSettings')->getFieldValue('tollFreePhone');
$digits = preg_replace('/\D/', '', $phone);   // as the templates' links.tel macro
$tel = str_starts_with(trim($phone), '+') || (strlen($digits) === 11 && $digits[0] === '1') ? "+$digits" : "+1$digits";
foreach (Entry::find()->type('ctaBand')->status(null)->all() as $block) {
    $owner = $block->getOwner();
    if (!in_array($owner?->section?->handle, ['pages', 'industries'], true)) continue;
    $first = $block->getFieldValue('ctaLink');
    if ($first && str_starts_with((string)$first->url, 'tel:')) continue;   // done
    // a page link's value is its URL here: copy the element id
    $block->setFieldValue('secondaryCta', $first && $first->url ? ['type' => $first->type, 'value' => $first->getElement()?->id ?? $first->value, 'label' => $first->label, 'target' => $first->target] : null);
    $block->setFieldValue('ctaLink', ['type' => 'tel', 'value' => $tel, 'label' => 'Talk to a Specialist']);
    Craft::$app->getElements()->saveElement($block) || fail("cta band {$block->id}", $block);
    say("  ~ /{$owner->uri}/ CTA Band {$block->id}: Button = Talk to a Specialist ($tel), Second Button = " . ($first?->label ?: '—'));
}
