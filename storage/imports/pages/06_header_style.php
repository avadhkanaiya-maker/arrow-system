<?php
/**
 * Page Header block (industryHeader; Pages and industries): a Header Style option (2026-10-21).
 *   Image beside text (default, the design's industry header) · Full-width banner: the text first, then the image on
 *   its own across the full content width, shown whole with nothing on it (as the blog posts' cover).
 * Keeps the block's existing layout elements (their uids hold the content). The Contact Us page header becomes a banner
 * (the live page has a full-width banner). Idempotent. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/pages/06_header_style.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Dropdown;

$fields = Craft::$app->getFields();
$entries = Craft::$app->getEntries();
$field = field(Dropdown::class, ['name' => 'Header Style', 'handle' => 'headerStyle',
    'options' => options(['split' => 'Image beside text', 'banner' => 'Full-width banner'], 'split'),
    'instructions' => 'Full-width banner: the image on its own under the text, across the full width and shown whole (like a blog post cover). Use a wide photo, 1600 px wide or more.']);
if (str_contains((string)$field->instructions, 'behind the text')) {   // the first version put the text on the image
    $field->instructions = 'Full-width banner: the image on its own under the text, across the full width and shown whole (like a blog post cover). Use a wide photo, 1600 px wide or more.';
    $fields->saveField($field) || fail('field headerStyle', $field);
    say('  ~ Header Style: instructions (image under the text)');
}

$type = $entries->getEntryTypeByHandle('industryHeader') ?? exit("no industryHeader\n");
$layout = $type->getFieldLayout();
if (!$layout->getFieldByHandle('headerStyle')) {
    $tabs = $layout->getTabs();
    $els = [];
    foreach ($tabs[0]->getElements() as $el) {
        $els[] = $el;
        if ($el instanceof CustomField && $el->attribute() === 'mediaImage') {
            $el->instructions = 'Image beside text: a 16:9 photo (e.g. 1280 × 720). Full-width banner: a wide photo, 1600 px or more, shown whole under the text. Set its alt text. Empty = the page’s Card Photo.';
            $els[] = new CustomField($field);
        }
    }
    $tabs[0]->setElements($els);
    $layout->setTabs($tabs);
    $type->setFieldLayout($layout);
    $entries->saveEntryType($type) || fail('entry type industryHeader', $type);
    say('  ~ Page Header: + Header Style (Image beside text / Full-width banner)');
}
// Banner Height (review 2026-10-21): the whole image (default) or a short strip, e.g. for a plain texture; shown only for
// Full-width banner (field layout condition; needs the saved layout and elementType, else Craft drops it)
$height = field(Dropdown::class, ['name' => 'Banner Height', 'handle' => 'bannerHeight',
    'options' => options(['whole' => 'Whole image', 'strip' => 'Short strip (cropped)'], 'whole'),
    'instructions' => 'Whole image = the full picture. Short strip = a band about 320 px tall, cropped from the middle (good for textures and wide scenes).']);
$type = $entries->getEntryTypeByHandle('industryHeader');
$layout = $type->getFieldLayout();
if (!$layout->getFieldByHandle('bannerHeight')) {
    $tabs = $layout->getTabs();
    $els = [];
    foreach ($tabs[0]->getElements() as $el) {
        $els[] = $el;
        if ($el instanceof CustomField && $el->attribute() === 'headerStyle') $els[] = new CustomField($height);
    }
    $tabs[0]->setElements($els);
    $layout->setTabs($tabs);
    $type->setFieldLayout($layout);
    $entries->saveEntryType($type) || fail('entry type industryHeader', $type);
    say('  ~ Page Header: + Banner Height');
    $type = $entries->getEntryTypeByHandle('industryHeader');
    $layout = $type->getFieldLayout();
}
$styleEl = current(array_filter($layout->getCustomFieldElements(), fn($el) => $el->attribute() === 'headerStyle'));
$heightEl = current(array_filter($layout->getCustomFieldElements(), fn($el) => $el->attribute() === 'bannerHeight'));
if ($heightEl && !$heightEl->getElementCondition()?->getConditionRules()) {
    $heightEl->setElementCondition(['class' => craft\elements\conditions\entries\EntryCondition::class, 'elementType' => Entry::class, 'conditionRules' => [[
        'class' => craft\fields\conditions\OptionsFieldConditionRule::class, 'fieldUid' => $styleEl->getField()->uid, 'layoutElementUid' => $styleEl->uid,
        'operator' => 'in', 'values' => ['banner'],
    ]]]);
    $type->setFieldLayout($layout);
    $entries->saveEntryType($type) || fail('entry type industryHeader', $type);
    say('  ~ Banner Height: shown only for Full-width banner');
}
saveProjectConfig();

// Contact Us: the banner, as the live page
$page = Entry::find()->section('pages')->slug('contact-us')->status(null)->one();
$header = $page ? $page->getFieldValue('pageBuilder')->type('industryHeader')->status(null)->one() : null;
if ($header && ($header->getFieldValue('headerStyle')->value ?? '') !== 'banner') {
    $header->setFieldValue('headerStyle', 'banner');
    Craft::$app->getElements()->saveElement($header) || fail('contact-us header', $header);
    say('  ~ /contact-us/: Page Header = Full-width banner');
}
