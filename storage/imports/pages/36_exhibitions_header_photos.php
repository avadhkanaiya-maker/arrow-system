<?php
/**
 * Exhibitions page (2026-10-28, QA): Header Photos took any number of photos, but the header collage shows two (large +
 * small). It was the exhibitions' gallery field (Photos, unlimited, shared with every exhibition), so the page gets its
 * own Header Photos field: Photos' settings, at most 2. The page's first two Photos are copied into it.
 * Template: exhibitions/_index.twig. Idempotent. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/pages/36_exhibitions_header_photos.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Assets;

$fields = Craft::$app->getFields();
$photos = $fields->getFieldByHandle('exhibitionPhotos') ?? exit("no field exhibitionPhotos\n");
$header = field(Assets::class, array_merge($photos->getSettings(), [
    'name' => 'Header Photos', 'handle' => 'headerPhotos', 'maxRelations' => 2, 'searchable' => false,
    'instructions' => 'At most two: first = the large photo, second = the small one. Set each photo’s alt text (click it → Alternative Text).',
]));
saveProjectConfig();

$type = Craft::$app->getEntries()->getEntryTypeByHandle('exhibitionsPage') ?? exit("no entry type exhibitionsPage\n");
$page = Entry::find()->type('exhibitionsPage')->status(null)->one() ?? exit("no Exhibitions page\n");
$layout = $type->getFieldLayout();
if (!$layout->getFieldByHandle('headerPhotos')) {
    $ids = array_slice($page->exhibitionPhotos->ids(), 0, 2); // before the layout loses Photos
    foreach ($layout->getTabs() as $tab) {
        $els = $tab->getElements();
        foreach ($els as $i => $el) {
            if ($el instanceof CustomField && $el->getField()->handle === 'exhibitionPhotos') {
                $els[$i] = new CustomField($header, ['width' => $el->width]);
            }
        }
        $tab->setElements($els);
    }
    $type->setFieldLayout($layout);
    Craft::$app->getEntries()->saveEntryType($type) || fail('entry type exhibitionsPage', $type);
    say('  ~ exhibitionsPage: Photos → Header Photos');
    saveProjectConfig();

    $page = Entry::find()->id($page->id)->status(null)->one();
    $page->setFieldValue('headerPhotos', $ids);
    Craft::$app->getElements()->saveElement($page) || fail('Exhibitions page', $page);
    say('  ~ Exhibitions page: Header Photos = ' . implode(', ', $ids));
}
