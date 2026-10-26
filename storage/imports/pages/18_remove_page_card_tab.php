<?php
/**
 * Pages: the "Card" tab removed from the page entry type (review 2026-10-22: "no need of it"). Its two fields, Card
 * Photo (featuredImage) and Card Summary (cardTagline), only described a page as a card, and nothing lists pages as
 * cards; Card Photo was a fallback for the header / social image, but every page that had one uses the same image in
 * its Page Header. The fields stay on industries, products, blog posts and exhibitions (their cards use them).
 * Templates read a page's Card Photo only through `??` (absent field = no image). Idempotent. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/pages/18_remove_page_card_tab.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

$entries = Craft::$app->getEntries();
$type = $entries->getEntryTypeByHandle('page') ?? exit("no page entry type\n");
$layout = $type->getFieldLayout();
$tabs = array_values(array_filter($layout->getTabs(), fn($t) => $t->name !== 'Card'));
if (count($tabs) !== count($layout->getTabs())) {
    $layout->setTabs($tabs);
    $type->setFieldLayout($layout);
    $entries->saveEntryType($type) || fail('entry type page', $type);
    saveProjectConfig();
    say('  - page: Card tab (Card Photo, Card Summary)');
}

$layout = $entries->getEntryTypeByHandle('page')->getFieldLayout();
!$layout->getFieldByHandle('featuredImage') && !$layout->getFieldByHandle('cardTagline') || exit("page still has the card fields\n");
say('  = page tabs: ' . implode(' · ', array_map(fn($t) => $t->name, $layout->getTabs())));
