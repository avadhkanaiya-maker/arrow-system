<?php
/**
 * Header Products mega menu: the menu-level "Products box" is removed (2026-10-21): each category picks its own
 * Featured Product (02_category_featured.php), so the hand-typed box (Navigation → Main Menu → Products box: Featured
 * Image / Title / Text / Link) is no longer needed. A category without a Featured Product shows only the extra links
 * (Compare Models, Used Equipment) in that place.
 *   - the "Products box" tab leaves the Main Menu's own fields (the "Industries box" tab stays)
 *   - the fields Featured Title, Featured Text, Featured Link are deleted (used only there); Featured Image is the shared
 *     Card Photo field of blog posts, exhibitions, pages … and stays
 * Idempotent. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/navigation/03_remove_products_box.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use verbb\navigation\Navigation;

$fields = Craft::$app->getFields();
$menu = Navigation::$plugin->getMenus()->getMenuByHandle('mainMenu') ?? exit("no Main Menu\n");
$layout = $menu->getMenuFieldLayout();
$tabs = $layout->getTabs();
$kept = array_values(array_filter($tabs, fn($t) => $t->name !== 'Products box'));
if (count($kept) !== count($tabs)) {
    $layout->setTabs($kept);
    $menu->setMenuFieldLayout($layout);
    Navigation::$plugin->getMenus()->saveMenu($menu) || fail('menu mainMenu', $menu);
    say('  - Main Menu content: "Products box" tab removed (kept: ' . implode(', ', array_map(fn($t) => $t->name, $kept)) . ')');
}
saveProjectConfig();

$picker = $fields->getFieldByHandle('featuredProduct');
if ($picker && str_contains((string)$picker->instructions, 'Products box')) {
    $picker->instructions = 'Products menu → a category item (e.g. Label Finishers) only: the product in the box on the right while this category is open (its card photo, name and tagline). Empty = no product box for this category (only the extra links). Ignored on other items.';
    $fields->saveField($picker) || fail('field featuredProduct', $picker);
    say('  ~ Featured Product: instructions (no menu box fallback any more)');
}
foreach (['featuredTitle', 'featuredText', 'featuredLink'] as $handle) {
    if (!$field = $fields->getFieldByHandle($handle)) continue;
    if (Craft::$app->getProjectConfig()->get('fields.' . $field->uid)) $fields->deleteField($field) || fail("delete $handle", $field);
    $fields->applyFieldDelete($field->uid);   // a standalone script doesn't run Craft's config handlers
    say("  - field $handle");
}
saveProjectConfig();
