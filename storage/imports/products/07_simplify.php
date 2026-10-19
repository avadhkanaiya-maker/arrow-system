<?php
/**
 * Products, one field per thing on the page (after review of the new design, 2026-10-16):
 *   - Short Description removed: the Intro is the header description (a value in it moves into the Intro first)
 *   - The old bullet "Highlights" (listItems, the previous design's header list) moved into a Highlights tab (one card per
 *     bullet, tick icon) as the first tab of the product's first Tabs block (a Tabs block is added after the Stats Strip
 *     when there is none); skipped when the product already has a Highlights tab. Then the field leaves the product form
 *     (it stays in use by the blog / industry blocks).
 * Idempotent. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/products/07_simplify.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;

$fields = Craft::$app->getFields();
$entries = Craft::$app->getEntries();
$product = $entries->getEntryTypeByHandle('product');
$layout = $product->getFieldLayout();
$save = function (Entry $e) { Craft::$app->getElements()->saveElement($e) || fail("entry {$e->slug}", $e); };

// ---- content (while the fields are still on the form) -------------------------------------------------------------
$moved = $converted = 0;
foreach (Entry::find()->section('products')->status(null)->all() as $p) {
    $changed = false;
    if ($layout->getFieldByHandle('shortDescription') && trim((string)$p->getFieldValue('shortDescription'))) {
        $p->setFieldValue('richText', '<p>' . nl2br(htmlspecialchars(trim($p->getFieldValue('shortDescription')))) . '</p>');
        $p->setFieldValue('shortDescription', null);
        $changed = true; $moved++;
    }

    $bullets = $layout->getFieldByHandle('listItems') ? array_values(array_filter(array_map('trim', array_column($p->getFieldValue('listItems') ?? [], 'text')))) : [];
    $blocks = $p->getFieldValue('productBuilder')->status(null)->all();
    $hasHighlights = false;
    foreach ($blocks as $b) if ($b->type->handle === 'tabsBlock')
        foreach ($b->getFieldValue('productTabs')->status(null)->all() as $tab) $hasHighlights = $hasHighlights || $tab->type->handle === 'highlightsTab';
    if ($bullets && !$hasHighlights) {
        $tab = ['type' => 'highlightsTab', 'fields' => ['headingText' => 'Highlights', 'sectionHeading' => 'Highlights',
            'highlightItems' => array_map(fn($text) => ['icon' => 'check', 'title' => $text, 'text' => ''], $bullets)]];
        $value = ['sortOrder' => [], 'entries' => []];
        $done = false;
        foreach ($blocks as $b) {
            $value['sortOrder'][] = $b->id;
            if (!$done && $b->type->handle === 'tabsBlock') {
                $ids = array_map(fn($t) => $t->id, $b->getFieldValue('productTabs')->status(null)->all());
                $value['entries'][$b->id] = ['type' => 'tabsBlock', 'fields' => ['productTabs' => ['sortOrder' => array_merge(['new1'], $ids), 'entries' => ['new1' => $tab]]]];
                $done = true;
            }
        }
        if (!$done) { // no Tabs block: one after the Stats Strip (else at the end)
            $at = array_search('statsStrip', array_map(fn($b) => $b->type->handle, $blocks), true);
            $at = $at === false ? count($blocks) : $at + 1;
            array_splice($value['sortOrder'], $at, 0, ['new1']);
            $value['entries']['new1'] = ['type' => 'tabsBlock', 'fields' => ['productTabs' => ['sortOrder' => ['new1'], 'entries' => ['new1' => $tab]]]];
        }
        $p->setFieldValue('productBuilder', $value);
        $changed = true; $converted++;
    }
    if ($changed) {
        $save($p);
        $after = $p->getFieldValue('productBuilder')->status(null)->count();
        $after >= count($blocks) || exit("{$p->slug}: blocks lost\n");
    }
}
say("  ~ Short Description → Intro on $moved; bullet Highlights → Highlights tab on $converted");

// ---- the product form: - Short Description, - Highlights (bullets); Intro instructions -----------------------------
$tabs = $layout->getTabs();
$removed = false;
foreach ($tabs as $tab) {
    $els = [];
    foreach ($tab->getElements() as $el) {
        $handle = $el instanceof CustomField ? $el->attribute() : null;
        if (in_array($handle, ['shortDescription', 'listItems'], true)) { $removed = true; continue; }
        if ($handle === 'richText' && $tab->name === 'Product') $el->instructions = 'The description under the subtitle: 2–3 lines (about 40–60 words).';
        $els[] = $el;
    }
    $tab->setElements($els);
}
if ($removed) {
    $layout->setTabs($tabs);
    $product->setFieldLayout($layout);
    $entries->saveEntryType($product) || fail('entry type product', $product);
    say('  ~ product form: - Short Description, - Highlights (bullets)');
}
if ($field = $fields->getFieldByHandle('shortDescription')) {
    if (Craft::$app->getProjectConfig()->get('fields.' . $field->uid)) $fields->deleteField($field) || exit("could not delete shortDescription\n");
    $fields->applyFieldDelete($field->uid); // a standalone script doesn't run Craft's config handlers: apply the delete itself
    say('  - field shortDescription');
}
saveProjectConfig();
