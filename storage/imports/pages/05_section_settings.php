<?php
/**
 * Pages: layout settings the admin controls on the general section blocks (review 2026-10-19: "make the page builder
 * flexible"), on a "Section Settings" tab of Columns, Card Grid and Form:
 *   Space Above / Space Below  none, small, normal (default), large
 *   Background                 white (default), light grey
 *   Vertical Alignment         (Columns) top (default), centre, equal height (both columns as tall as the taller one)
 * A Columns block with only one column filled is one full-width column (e.g. a heading + a wide map or video).
 * Contact Us: the office / form columns aligned to the top (no stretched empty form card); "Distributor Location" + the
 * map moved into a one-column Columns block (full width, as live), with the Card Grid right under it.
 * Idempotent. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/pages/05_section_settings.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\elements\Entry;
use craft\fields\Dropdown;

$fields = Craft::$app->getFields();
$entries = Craft::$app->getEntries();
$space = options(['normal' => 'Normal', 'small' => 'Small', 'none' => 'None', 'large' => 'Large'], 'normal');
field(Dropdown::class, ['name' => 'Space Above', 'handle' => 'spacingTop', 'options' => $space]);
field(Dropdown::class, ['name' => 'Space Below', 'handle' => 'spacingBottom', 'options' => $space]);
field(Dropdown::class, ['name' => 'Background', 'handle' => 'sectionBackground', 'options' => options(['white' => 'White', 'surface' => 'Light grey'], 'white')]);
field(Dropdown::class, ['name' => 'Vertical Alignment', 'handle' => 'columnsAlign', 'options' => options(['top' => 'Top', 'center' => 'Centre', 'stretch' => 'Equal height'], 'top'),
    'instructions' => 'How the two columns line up. Equal height = the shorter column (card / panel) grows to the taller one.']);

$settings = array_map(fn($h) => $fields->getFieldByHandle($h), ['spacingTop', 'spacingBottom', 'sectionBackground']);
addTab($entries->getEntryTypeByHandle('columnsBlock'), 'Section Settings', array_merge([$fields->getFieldByHandle('columnsAlign')], $settings));
addTab($entries->getEntryTypeByHandle('cardGrid'), 'Section Settings', $settings);
addTab($entries->getEntryTypeByHandle('formBlock'), 'Section Settings', $settings);   // used when the form is a full-width section
saveProjectConfig();

// ---- Contact Us -----------------------------------------------------------------------------------------------------
$page = Entry::find()->section('pages')->slug('contact-us')->status(null)->one();
$blocks = $page ? $page->getFieldValue('pageBuilder')->status(null)->all() : [];
$handles = array_map(fn($b) => $b->type->handle, $blocks);
if ($page && in_array('html', $handles, true)) {   // the distributors' Heading + HTML are still loose common blocks
    $value = ['sortOrder' => [], 'entries' => []];
    $moved = [];
    foreach ($blocks as $b) {
        $h = $b->type->handle;
        if ($h === 'columnsBlock') {
            $value['sortOrder'][] = $b->id;
            $value['entries'][$b->id] = ['type' => 'columnsBlock', 'fields' => ['columnsAlign' => 'top', 'spacingBottom' => 'small']];
        } elseif (in_array($h, ['heading', 'html'], true)) {
            $moved[] = $b;
            if (count($moved) === 1) $value['sortOrder'][] = 'new1';
        } elseif ($h === 'cardGrid') {
            $value['sortOrder'][] = $b->id;
            $value['entries'][$b->id] = ['type' => 'cardGrid', 'fields' => ['spacingTop' => 'none']];
        } else $value['sortOrder'][] = $b->id;
    }
    $left = ['sortOrder' => [], 'entries' => []];
    foreach ($moved as $i => $b) {
        $left['sortOrder'][] = "n$i";
        $left['entries']["n$i"] = ['type' => $b->type->handle, 'fields' => $b->type->handle === 'html'
            ? ['htmlCode' => $b->getFieldValue('htmlCode')]
            : ['headingText' => $b->getFieldValue('headingText')]];
    }
    $value['entries']['new1'] = ['type' => 'columnsBlock', 'fields' => ['leftColumn' => $left, 'spacingTop' => 'small', 'spacingBottom' => 'small']];
    $page->setFieldValue('pageBuilder', $value);
    Craft::$app->getElements()->saveElement($page) || fail('page contact-us', $page);
    Entry::find()->id($page->id)->one()->getFieldValue('pageBuilder')->type('columnsBlock')->count() === 2 || exit("contact-us: expected 2 Columns blocks\n");
    say('  ~ /contact-us/: columns aligned top; Distributor Location + map in a full-width one-column block; cards right under it');
}
