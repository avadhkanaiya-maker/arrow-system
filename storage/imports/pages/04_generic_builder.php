<?php
/**
 * Pages: a general page builder, no block made for one page (review 2026-10-19). Replaces what 02_form_blocks.php built
 * for the Contact Us page:
 *   Form (formBlock)       any Formie form + optional heading and intro; nothing else (the office panel / map are gone:
 *                          build them with Columns). In the COMMON set too, so blog posts, products, industries and
 *                          columns can hold a form. The CP shows "Edit form fields" next to the chosen form.
 *   Columns (columnsBlock) NEW: two columns side by side (widths 50/50, 40/60, 60/40, 33/67, 67/33), each with its own
 *                          blocks (any common block: text, lists, image, video, HTML / embed, FAQ, form …) and a style
 *                          (plain, white card, maroon panel). Stacked on phones.
 *   Card Grid (cardGrid)   was "Distributors": heading, intro, cards (title, subtitle, text, image, link), 2 / 3 / 4 per
 *                          row. Handles renamed (distributorsBlock → cardGrid, distributor → card, distributorItems →
 *                          cardItems); content kept.
 *   Removed: the Contact Form block from the Pages builder (still on the industries, whose 12 pages use it); the fields
 *   Office Details (showContactPanel) and Map Embed URL (mapEmbedUrl).
 *   The Contact Us page is rebuilt from general blocks: Columns [maroon panel: office text + map (HTML) | Form], a Heading
 *   + the distributors map (HTML), Card Grid (the 19 distributors).
 * Idempotent. Back up first. Run from the project root, then storage/imports/builder/01_common_blocks.php:
 *   /opt/lampp/bin/php storage/imports/pages/04_generic_builder.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';
require dirname(__DIR__) . '/products/_helpers.php';

use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Dropdown;
use craft\fields\Matrix;

$fields = Craft::$app->getFields();
$entries = Craft::$app->getEntries();
$f = fn(string $handle, array $opts = []) => new CustomField($fields->getFieldByHandle($handle) ?? throw new Exception("No field $handle"), $opts);
$type = fn(string $h) => $entries->getEntryTypeByHandle($h);
// a new layout for a block type that keeps each existing field's layout element (its uid): Craft 5 stores the values under
// that uid, so a fresh element would orphan the content already entered
$relayout = function (string $handle, array $elements, array $props = []) use ($entries, $type) {
    $et = $type($handle);
    $old = [];
    foreach ($et->getFieldLayout()->getCustomFieldElements() as $el) $old[$el->getField()->handle] = $el->uid;
    foreach ($elements as $el) if (isset($old[$el->getField()->handle])) $el->uid = $old[$el->getField()->handle];
    foreach ($props as $k => $v) $et->$k = $v;
    $et->setFieldLayout(layout(['Content' => $elements]));
    $entries->saveEntryType($et) || fail("entry type $handle", $et);
};

// ---- Form: general -------------------------------------------------------------------------------------------------
say('Form');
if ($type('formBlock')->getFieldLayout()->getFieldByHandle('showContactPanel')) {
    $relayout('formBlock', [
        $f('formieForm', ['required' => true, 'instructions' => 'The form to show. Edit its fields, emails and thank-you message in Formie (the “Edit form fields” link under it).']),
        $f('sectionHeading', ['label' => 'Heading', 'instructions' => 'Over the form, e.g. “Contact Us”. Optional.']),
        $f('sectionLead', ['label' => 'Intro', 'instructions' => 'A line or two under the heading. Optional.']),
        $f('anchorId', ['instructions' => 'For links to this form, e.g. “contact” → /page/#contact. Optional.']),
    ], ['description' => 'Any Formie form: Formie checks, saves (Formie → Submissions) and emails each submission.']);
    say('  ~ formBlock: form, heading, intro, anchor (office details and map removed)');
}

// ---- Card Grid (was Distributors) ----------------------------------------------------------------------------------
say('Card Grid');
if ($old = $fields->getFieldByHandle('distributorItems')) {
    $old->name = 'Cards'; $old->handle = 'cardItems'; $old->createButtonLabel = 'Add card'; $old->instructions = 'One card each, in this order.';
    $fields->saveField($old) || fail('field cardItems', $old);
    say('  ~ field distributorItems → cardItems');
}
field(Dropdown::class, ['name' => 'Cards per Row', 'handle' => 'cardColumns', 'options' => options(['2' => '2', '3' => '3', '4' => '4'], '3'),
    'instructions' => 'On large screens (2 on tablets, 1 on phones).']);
if ($card = $type('distributor')) {
    $card->handle = 'card';
    $relayout('distributor', [
        $f('headingText', ['label' => 'Title', 'required' => true]),
        $f('subheading', ['label' => 'Subtitle', 'instructions' => 'Optional, shown under the title in red.']),
        $f('mediaImage', ['label' => 'Image', 'instructions' => 'Optional, above the title.']),
        $f('richText', ['label' => 'Text']),
        $f('ctaLink', ['label' => 'Link', 'instructions' => 'Optional: the card links here (the whole card is clickable).']),
    ], ['name' => 'Card', 'icon' => 'square', 'titleFormat' => '{headingText}']);
    say('  ~ entry type distributor → card (title, subtitle, image, text, link)');
}
if ($grid = $type('distributorsBlock')) {
    $grid->handle = 'cardGrid';
    $relayout('distributorsBlock', [
        $f('sectionHeading', ['label' => 'Heading', 'instructions' => '[brackets] = red. Optional.']),
        $f('sectionLead', ['label' => 'Intro', 'instructions' => 'Optional.']),
        $f('cardColumns'),
        $f('cardItems'),
    ], ['name' => 'Card Grid', 'icon' => 'grip', 'description' => 'Cards in a grid: people, offices, distributors, features, links …']);
    say('  ~ entry type distributorsBlock → cardGrid');
}

// ---- Columns -------------------------------------------------------------------------------------------------------
say('Columns');
field(Dropdown::class, ['name' => 'Column Widths', 'handle' => 'columnsLayout', 'options' => options([
    '1-1' => 'Equal (50 / 50)', '2-3' => 'Narrow + wide (40 / 60)', '3-2' => 'Wide + narrow (60 / 40)',
    '1-2' => 'One third + two thirds', '2-1' => 'Two thirds + one third'], '1-1')]);
field(Dropdown::class, ['name' => 'Column Style', 'handle' => 'columnStyle', 'options' => options([
    'plain' => 'Plain', 'card' => 'White card', 'panel' => 'Maroon panel (white text)'], 'plain')]);
// the blocks a column can hold: the common set (incl. Form), minus the full-width Stats Strip
$columnTypes = array_values(array_filter($fields->getFieldByHandle('articleBody')->getEntryTypes(), fn($t) => $t->handle !== 'statsStrip'));
foreach (['leftColumn' => 'Left Column', 'rightColumn' => 'Right Column'] as $handle => $name) {
    $m = $fields->getFieldByHandle($handle) ?? new Matrix(['name' => $name, 'handle' => $handle, 'viewMode' => Matrix::VIEW_MODE_BLOCKS,
        'createButtonLabel' => 'Add block', 'instructions' => 'The blocks in this column, top to bottom.']);
    $rows = array_map(fn($t) => ['uid' => $t->uid, 'group' => $t->group], $columnTypes);
    if (!$m->id || array_map(fn($t) => $t->uid, $m->getEntryTypes()) != array_column($rows, 'uid')) {
        $m->setEntryTypes($rows);
        $fields->saveField($m) || fail("field $handle", $m);
        say("  ~ field $handle: " . count($rows) . ' block types');
    }
}
blockType('Columns', 'columnsBlock', 'table-columns', [
    $f('columnsLayout'),
    $f('columnStyle', ['handle' => 'leftStyle', 'label' => 'Left Column Style']),
    $f('leftColumn'),
    $f('columnStyle', ['handle' => 'rightStyle', 'label' => 'Right Column Style']),
    $f('rightColumn'),
], '{columnsLayout}', 'Two columns side by side, each with its own blocks (text, image, embed, form …) and style.');

// ---- the common set: + Form (blog posts, products, industries, columns) -------------------------------------------
$body = $fields->getFieldByHandle('articleBody');
$rows = array_map(fn($t) => ['uid' => $t->uid, 'group' => $t->group], $body->getEntryTypes());
if (!in_array($type('formBlock')->uid, array_column($rows, 'uid'), true)) {
    $rows[] = ['uid' => $type('formBlock')->uid, 'group' => 'Products & CTA'];
    $body->setEntryTypes($rows);
    $fields->saveField($body) || fail('field articleBody', $body);
    say('  ~ articleBody: + Form (group Products & CTA)');
    foreach (['leftColumn', 'rightColumn'] as $handle) {   // columns hold the common set: now with Form
        $m = $fields->getFieldByHandle($handle);
        $m->setEntryTypes(array_merge(array_map(fn($t) => ['uid' => $t->uid, 'group' => $t->group], $m->getEntryTypes()), [['uid' => $type('formBlock')->uid, 'group' => 'Products & CTA']]));
        $fields->saveField($m) || fail("field $handle", $m);
    }
}
saveProjectConfig();

// ---- the Pages builder must allow Columns + Card Grid before the page uses them (Craft drops block types a Matrix field
// doesn't allow); builder/01_common_blocks.php sets the final order and groups afterwards
$pb = $fields->getFieldByHandle('pageBuilder');
$pbRows = array_map(fn($t) => ['uid' => $t->uid, 'group' => $t->group], $pb->getEntryTypes());
$add = array_filter([$type('columnsBlock'), $type('cardGrid')], fn($t) => !in_array($t->uid, array_column($pbRows, 'uid'), true));
if ($add) {
    $pb->setEntryTypes(array_merge($pbRows, array_map(fn($t) => ['uid' => $t->uid, 'group' => 'Sections'], $add)));
    $fields->saveField($pb) || fail('field pageBuilder', $pb);
    saveProjectConfig();
}

// ---- Contact Us: rebuilt from general blocks --------------------------------------------------------------------------
$page = Entry::find()->section('pages')->slug('contact-us')->status(null)->one();
$blocks = $page ? $page->getFieldValue('pageBuilder')->status(null)->all() : [];
$formBlock = current(array_filter($blocks, fn($b) => $b->type->handle === 'formBlock'));
if ($page && $formBlock && !array_filter($blocks, fn($b) => $b->type->handle === 'columnsBlock')) {
    $data = json_decode(file_get_contents(__DIR__ . '/data/contact-us.json'), true);
    $map = fn(string $src, string $title) => '<iframe src="' . htmlspecialchars($src) . '" title="' . htmlspecialchars($title) . '" width="100%" height="450" style="border:0" loading="lazy" allowfullscreen></iframe>';
    $s = Craft::$app->getGlobals()->getSetByHandle('siteSettings');
    $office = '<h2>' . htmlspecialchars($data['officeHeading']) . '</h2>'
        . '<p>2440 Jerauld Ave.,<br>Niagara Falls<br>New York 14305, USA</p>'
        . '<p><strong>Toll free</strong><br><a href="tel:+18886972749">+1-888-697-2749</a> (US / Canada)</p>'
        . '<p><strong>Contact Directly</strong><br><a href="tel:+17162852974">+1-716-285-2974</a></p>'
        . '<p><strong>Email</strong><br><a href="mailto:' . $s->getFieldValue('salesEmail') . '">' . $s->getFieldValue('salesEmail') . '</a></p>';
    $value = ['sortOrder' => [], 'entries' => []];
    foreach ($blocks as $b) {
        if ($b->type->handle === 'formBlock') {
            $value['sortOrder'][] = 'new1';
            $value['entries']['new1'] = ['type' => 'columnsBlock', 'fields' => ['columnsLayout' => '2-3', 'leftStyle' => 'panel', 'rightStyle' => 'card',
                'leftColumn' => ['sortOrder' => ['n1', 'n2'], 'entries' => [
                    'n1' => ['type' => 'richText', 'fields' => ['richText' => $office]],
                    'n2' => ['type' => 'html', 'fields' => ['htmlCode' => $map($data['officeMap'], 'Map: Arrow Systems, Inc., Niagara Falls')]],
                ]],
                'rightColumn' => ['sortOrder' => ['n1'], 'entries' => [
                    'n1' => ['type' => 'formBlock', 'fields' => ['formieForm' => $formBlock->getFieldValue('formieForm')->ids(), 'sectionHeading' => $data['formHeading'], 'anchorId' => 'contact']],
                ]],
            ]];
        } elseif ($b->type->handle === 'cardGrid') {
            $value['sortOrder'][] = 'new2';
            $value['entries']['new2'] = ['type' => 'heading', 'fields' => ['headingText' => $data['distributorsHeading']]];
            $value['sortOrder'][] = 'new3';
            $value['entries']['new3'] = ['type' => 'html', 'fields' => ['htmlCode' => $map($data['distributorMap'], 'Arrow Systems Distributor Locations')]];
            $value['sortOrder'][] = $b->id;
            $value['entries'][$b->id] = ['type' => 'cardGrid', 'fields' => ['sectionHeading' => null, 'cardColumns' => '3']];
        } else $value['sortOrder'][] = $b->id;
    }
    $page->setFieldValue('pageBuilder', $value);
    Craft::$app->getElements()->saveElement($page) || fail('page contact-us', $page);
    $page->getFieldValue('pageBuilder')->type('columnsBlock')->exists() || exit("contact-us: the Columns block was not saved\n");
    say('  ~ /contact-us/: Columns [office panel + map | Form], Heading + map, Card Grid (19)');
}

// ---- unused now ----------------------------------------------------------------------------------------------------
foreach (['showContactPanel', 'mapEmbedUrl'] as $handle) {
    if (!$field = $fields->getFieldByHandle($handle)) continue;
    $fields->deleteField($field) || fail("delete $handle", $field);
    $fields->applyFieldDelete($field->uid);   // a standalone script doesn't run Craft's config handlers
    say("  - field $handle");
}
saveProjectConfig();
