<?php
/**
 * Header Products mega menu: a featured product per category (2026-10-21).
 *   Main Menu items get a "Featured Product" field (Entries → Products, one). On a Products category item (Digital Label
 *   Printers, Label Finishers …) it sets the box on the right while that category is open: the product's Card Photo
 *   (else Main Image), name, Card Tagline and a link to it. (The menu-level Products box that served as the fallback was
 *   removed by 03_remove_products_box.php.) Ignored on other items. Front end:
 *   _partials/navigation/mega-products.twig + web/assets/js/craft.js.
 * Idempotent (keeps the existing item fields' layout elements). Back up first. Run from the project root:
 *   /opt/lampp/bin/php storage/imports/navigation/02_category_featured.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\fieldlayoutelements\CustomField;
use craft\fields\Entries;
use verbb\navigation\Navigation;

$fields = Craft::$app->getFields();
$products = Craft::$app->getEntries()->getSectionByHandle('products') ?? exit("no Products section\n");

say('Field');
$field = field(Entries::class, ['name' => 'Featured Product', 'handle' => 'featuredProduct', 'maxRelations' => 1,
    'sources' => ['section:' . $products->uid], 'showSearchInput' => true, 'selectionLabel' => 'Choose a product',
    'instructions' => 'Products menu → a category item (e.g. Label Finishers) only: the product in the box on the right while this category is open (its card photo, name and tagline). Empty = no product box for this category (only the extra links). Ignored on other items.']);

say('Main Menu items');
$menu = Navigation::$plugin->getMenus()->getMenuByHandle('mainMenu') ?? exit("no Main Menu\n");
$layout = $menu->getFieldLayout();
if (!$layout->getFieldByHandle('featuredProduct')) {
    $tabs = $layout->getTabs();
    $tabs[0]->setElements(array_merge($tabs[0]->getElements(), [new CustomField($field)]));
    $layout->setTabs($tabs);
    $menu->setFieldLayout($layout);
    Navigation::$plugin->getMenus()->saveMenu($menu) || fail('menu mainMenu', $menu);
    say("  ~ Main Menu items: + Featured Product (tab “{$tabs[0]->name}”)");
}
saveProjectConfig();
