<?php
/**
 * Page Header (industryHeader) options for the design's category listing (Arrowsystem/digital-label-printers, 2026-10-26):
 *   - Subtitle: the existing Subheading field, under the heading (an H2 line, e.g. "Commercial Inkjet Label Printers for
 *     Production-Scale Printing"); empty = none
 *   - Header Style "Cut-out image beside text" (cutout): a product cut-out (transparent background) shown whole and large
 *     beside the text, no photo frame or crop marks; hidden on phones and tablets (the design's .cat-hero__art)
 * Template: industries/blocks/industryHeader.twig, styles: craft.css. Idempotent. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/pages/27_header_subtitle_cutout.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\fieldlayoutelements\CustomField;

$fields = Craft::$app->getFields();
$type = Craft::$app->getEntries()->getEntryTypeByHandle('industryHeader');
$sub = $fields->getFieldByHandle('subheading');
if (!$type->getFieldLayout()->getFieldByHandle('subheading')) {
    $layout = $type->getFieldLayout();
    foreach ($layout->getTabs() as $tab) {
        $els = [];
        foreach ($tab->getElements() as $el) {
            $els[] = $el;
            if ($el instanceof CustomField && $el->getField()->handle === 'sectionHeading') {
                $new = new CustomField($sub);
                $new->label = 'Subtitle';
                $new->instructions = 'Optional line under the heading, e.g. “Commercial Inkjet Label Printers for Production-Scale Printing”.';
                $els[] = $new;
            }
        }
        $tab->setElements($els);
    }
    $type->setFieldLayout($layout);
    Craft::$app->getEntries()->saveEntryType($type) || fail('entry type industryHeader', $type);
    say('  + Page Header: Subtitle');
}

$style = $fields->getFieldByHandle('headerStyle');
if (!in_array('cutout', array_column($style->options, 'value'), true)) {
    $style->options[] = ['label' => 'Cut-out image beside text', 'value' => 'cutout', 'default' => false];
    $style->instructions .= ' Cut-out image beside text: a product cut-out (transparent background) shown whole and large beside the text, no frame; hidden on phones and tablets.';
    $fields->saveField($style) || fail('field headerStyle', $style);
    say('  + Header Style: Cut-out image beside text');
}
saveProjectConfig();

$type = Craft::$app->getEntries()->getEntryTypeByHandle('industryHeader');
$type->getFieldLayout()->getFieldByHandle('subheading') && in_array('cutout', array_column($fields->getFieldByHandle('headerStyle')->options, 'value'), true)
    || exit("Page Header options not saved\n");
say('  = Page Header: Subtitle and the cut-out style');
