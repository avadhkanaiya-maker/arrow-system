<?php
/**
 * Page builder: a Tabs block on Pages and industries (2026-10-21, for /gerber-foils/'s six foil series; general): the
 * products' Tabs block (tabsBlock: text, specifications, downloads and highlights tabs), added to the Pages and
 * Industries builders under Sections, after Card Grid. Rendered by industries/blocks/tabsBlock (the products' tab set in
 * its own section). Its Section Settings tab gets Space Above / Below, Background and CSS Class (products use them too).
 * Idempotent. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/pages/15_tabs_block.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

$fields = Craft::$app->getFields();
$tabs = Craft::$app->getEntries()->getEntryTypeByHandle('tabsBlock') ?? exit("no tabsBlock\n");

foreach (['pageBuilder', 'industryBuilder'] as $handle) {
    $field = $fields->getFieldByHandle($handle);
    $rows = array_map(fn($t) => ['uid' => $t->uid, 'group' => $t->group], $field->getEntryTypes());
    if (in_array($tabs->uid, array_column($rows, 'uid'), true)) continue;
    $after = array_search(Craft::$app->getEntries()->getEntryTypeByHandle('cardGrid')->uid, array_column($rows, 'uid'), true);
    array_splice($rows, $after === false ? count($rows) : $after + 1, 0, [['uid' => $tabs->uid, 'group' => 'Sections']]);
    $field->setEntryTypes($rows);
    $fields->saveField($field) || fail("field $handle", $field);
    say("  ~ $handle: + Tabs (Sections)");
}
addTab($tabs, 'Section Settings', array_map(fn($h) => $fields->getFieldByHandle($h), ['spacingTop', 'spacingBottom', 'sectionBackground', 'cssClass']));
saveProjectConfig();

foreach (['pageBuilder', 'industryBuilder'] as $handle) {
    in_array('tabsBlock', array_map(fn($t) => $t->handle, $fields->getFieldByHandle($handle)->getEntryTypes()), true) || exit("$handle: no Tabs\n");
}
say('  = Tabs on the Pages and Industries builders');
