<?php
/**
 * One common page builder: every module's builder offers the same content blocks (the blog's Article Body set, in its
 * groups: Text, Lists, Media, Products & CTA (incl. Form: any Formie form), Q&A, Other) plus that module's own blocks in
 * a group of their own:
 *   Article Body (blog)           the common set
 *   Page Builder (products)       "Product":  Tabs, Specifications, Downloads, Product Video, What You Can Print + common
 *   Page Builder (industries)     "Industry": Page Header, Recommended Products, Content Block, Columns, Card Grid,
 *                                             Contact Form, Other Industries, CTA Band, Photo Gallery           + common
 *   Page Builder (pages)          "Sections": Page Header, Columns, Card Grid, Recommended Products, Content Block,
 *                                             CTA Band, Photo Gallery                                          + common
 * Entry types are shared (Craft 5), so a block added to the common set later is added here once and re-run.
 * Existing content is untouched (Rich Text, FAQ, Image, Video were already shared entry types).
 * Idempotent. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/builder/01_common_blocks.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

$fields = Craft::$app->getFields();
$common = array_map(fn($t) => ['uid' => $t->uid, 'group' => $t->group], $fields->getFieldByHandle('articleBody')->getEntryTypes());
$modules = [
    'productBuilder' => ['Product', ['tabsBlock', 'specsBlock', 'downloadsBlock', 'productVideo', 'productApplications']],
    'industryBuilder' => ['Industry', ['industryHeader', 'productShowcase', 'contentBlock', 'columnsBlock', 'cardGrid', 'contactBlock', 'otherIndustries', 'ctaBand', 'photoGallery']],
    'pageBuilder' => ['Sections', ['industryHeader', 'columnsBlock', 'cardGrid', 'productShowcase', 'contentBlock', 'ctaBand', 'photoGallery']],
];
foreach ($modules as $handle => [$group, $own]) {
    $field = $fields->getFieldByHandle($handle);
    $byHandle = [];
    foreach ($field->getEntryTypes() as $t) $byHandle[$t->handle] = $t;
    $rows = array_map(fn($h) => ['uid' => ($byHandle[$h] ?? Craft::$app->getEntries()->getEntryTypeByHandle($h))->uid, 'group' => $group], $own);
    $taken = array_column($rows, 'uid');
    foreach ($common as $c) if (!in_array($c['uid'], $taken, true)) $rows[] = $c;
    $now = array_map(fn($t) => ['uid' => $t->uid, 'group' => $t->group], $field->getEntryTypes());
    if ($now == $rows) { say("  = $handle"); continue; }
    $field->setEntryTypes($rows);
    $fields->saveField($field) || fail("field $handle", $field);
    say("  ~ $handle: $group (" . count($own) . ') + common (' . count($common) . ')');
}
saveProjectConfig();
