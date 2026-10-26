<?php
/**
 * Menu item fields with clear names (review 2026-10-23: "change Second Line to Description; View All Text and its URL
 * together, the URL named View All Text URL; meaningful names instead of vague ones").
 *   Main Menu items, in this order (level / item type = when the field shows in the Edit panel):
 *     Label · the item's link · Open in New Tab · Dropdown Style (level 1) · Category Panel Heading, View All Text,
 *     View All Text URL, Featured Product (level 3, Passive = Products categories) · Industry Icon (level 3, Entry or
 *     Custom URL = industries) · Description (level 4, products)
 *   - new field View All Text URL (viewAllUrl, Link: a page or an address, optional new tab): the "View all" link's
 *     address. Before, the category item's own link served as that address; the 4 Products categories that had one get
 *     it as View All Text URL and become Passive items (a category name opens its panel, it never linked anywhere), so
 *     the "View all" links stay the same. mega-products.twig reads viewAllUrl.
 *   Every menu: New Window → Open in New Tab, shown only on items with a link. The plugin's own help texts:
 *   translations/en-US/navigation.php.
 * Stops on a staged (unsaved) Main Menu builder change. Idempotent. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/navigation/05_menu_item_fields.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\db\Query;
use craft\elements\conditions\LevelConditionRule;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Link;
use verbb\navigation\elements\conditions\NodeCondition;
use verbb\navigation\elements\conditions\TypeConditionRule;
use verbb\navigation\elements\Node;
use verbb\navigation\fieldlayoutelements\NewWindowField;
use verbb\navigation\Navigation;
use verbb\navigation\nodetypes\Custom;
use verbb\navigation\nodetypes\Entry as EntryNode;
use verbb\navigation\nodetypes\Passive;

$menus = Navigation::$plugin->getMenus();
$main = $menus->getMenuByHandle('mainMenu') ?? exit("no Main Menu\n");
foreach ((new Query())->from('{{%navigation_build_sessions}}')->where(['menuId' => $main->id])->all() as $s) {
    !array_filter([$s['structureMoves'], $s['addedNodeIds'], $s['stagedDeletes'], $s['nodeDraftMap'], $s['menuContentDraft']])
        || exit("Main Menu has unsaved builder changes (session {$s['id']}): Save or Discard them, then run again\n");
}

$viewAllUrl = field(Link::class, ['name' => 'View All Text URL', 'handle' => 'viewAllUrl',
    'instructions' => 'Where the View All link goes: a page of this website or a full address.',
    'types' => ['entry', 'url'], 'advancedFields' => ['target']]);

$cond = fn(int $level, array $types = []) => ['class' => NodeCondition::class, 'elementType' => Node::class, 'fieldContext' => 'global',
    'conditionRules' => array_values(array_filter([
        ['class' => LevelConditionRule::class, 'operator' => '=', 'value' => (string)$level],
        $types ? ['class' => TypeConditionRule::class, 'operator' => 'in', 'values' => $types] : null,
    ]))];
$category = fn() => $cond(3, [Passive::class]);
// handle => [label (null = the field's name), help text, condition]
$spec = [
    'menuStyle' => ['Dropdown Style', 'What opens under this item: a simple dropdown list, the Products mega menu or the Industries mega menu.', $cond(1)],
    'panelTitle' => ['Category Panel Heading', 'Heading at the top of this category’s panel, e.g. “ArrowJet digital label printers”. Optional.', $category()],
    'viewAllLabel' => ['View All Text', 'The link at the bottom of this category’s panel, e.g. “View all digital label printers”. Leave empty to hide it.', $category()],
    'viewAllUrl' => [null, null, $category()],
    'featuredProduct' => ['Featured Product', 'The product in the box on the right while this category is open. Leave empty for no product box.', $category()],
    'menuIcon' => ['Industry Icon', 'Line icon before the industry name. Leave empty to use the industry’s own icon.', $cond(3, [EntryNode::class, Custom::class])],
    'itemDescription' => ['Description', 'Grey line under the product name. Leave empty to use the product’s Menu Description.', $cond(4)],
];

// Main Menu item layout: same layout elements (they hold the saved values), new names, order and conditions
$layout = $main->getFieldLayout();
$tab = $layout->getTabs()[0];
$fixed = $custom = [];
foreach ($tab->getElements() as $el) {
    if ($el instanceof CustomField) $custom[$el->getField()->handle] = $el; else $fixed[] = $el;
}
$custom['viewAllUrl'] ??= new CustomField($viewAllUrl);
!array_diff(array_keys($custom), array_keys($spec)) && !array_diff(array_keys($spec), array_keys($custom)) || exit('unexpected Main Menu fields: ' . implode(', ', array_keys($custom)) . "\n");
foreach ($spec as $handle => [$label, $help, $condition]) {
    $custom[$handle]->label = $label;
    $custom[$handle]->instructions = $help;
    $custom[$handle]->setElementCondition($condition);
}
$tab->setElements(array_merge($fixed, array_map(fn($h) => $custom[$h], array_keys($spec))));
$main->instructions = 'Top menu in the header (desktop and mobile). Level 1: menu items. Under a mega menu, level 2: group headings (Group / Column), level 3: categories (Passive items) or industries, level 4: products.';

// every menu: New Window → Open in New Tab, only on items that link somewhere (not Passive, Group / Column)
$linking = ['class' => NodeCondition::class, 'elementType' => Node::class, 'fieldContext' => 'global', 'conditionRules' => [
    ['class' => TypeConditionRule::class, 'operator' => 'in', 'values' => [EntryNode::class, Custom::class, verbb\navigation\nodetypes\Asset::class]]]];
foreach ($menus->getAllMenus() as $menu) {
    $l = $menu->handle === 'mainMenu' ? $layout : $menu->getFieldLayout();
    foreach ($l->getTabs() as $t) foreach ($t->getElements() as $el) if ($el instanceof NewWindowField) { $el->label = 'Open in New Tab'; $el->setElementCondition($linking); }
    $menu = $menu->handle === 'mainMenu' ? $main : $menu;
    $menu->setFieldLayout($l);
    $menus->saveMenu($menu) || fail("menu {$menu->handle}", $menu);
}
saveProjectConfig();
say('  ~ menu item fields: names, order, conditions; Open in New Tab in every menu');

// Products categories: their link → View All Text URL, then Passive (nothing else uses a category's own link)
$elements = Craft::$app->getElements();
foreach (Node::find()->menu('mainMenu')->level(1)->status(null)->all() as $top) {
    if (($top->getFieldValue('menuStyle')?->value ?? null) !== 'productsMega') continue;
    foreach (Node::find()->descendantOf($top)->level(3)->status(null)->all() as $cat) {
        if ($cat->type !== Custom::class) continue;
        $url = $cat->getUrl();   // with any URL suffix, as the View all link used it
        $cat->setFieldValue('viewAllUrl', $url ? ['type' => 'url', 'value' => $url, 'target' => $cat->newWindow ? '_blank' : null] : null);
        $cat->type = Passive::class;
        $cat->url = null;
        $cat->urlSuffix = null;
        $cat->newWindow = false;
        $elements->saveElement($cat) || fail("category {$cat->title}", $cat);
        say("  ~ {$cat->title}: link → View All Text URL, now Passive");
    }
}

// self-check: the fields in order, every category Passive with its View All link
$labels = array_map(fn($e) => $e instanceof CustomField ? ($e->label ?? $e->getField()->name) : get_class($e), $menus->getMenuByHandle('mainMenu')->getFieldLayout()->getTabs()[0]->getElements());
array_slice($labels, -7) === ['Dropdown Style', 'Category Panel Heading', 'View All Text', 'View All Text URL', 'Featured Product', 'Industry Icon', 'Description'] || exit('fields: ' . implode(', ', $labels) . "\n");
foreach (Node::find()->menu('mainMenu')->level(3)->status(null)->all() as $n) {
    if ($n->type === Passive::class && $n->getFieldValue('viewAllLabel')) $n->getFieldValue('viewAllUrl')?->url || exit("{$n->title}: View All Text without its URL\n");
    $n->type !== Custom::class || ($n->getParent()?->getParent()?->getFieldValue('menuStyle')?->value ?? '') !== 'productsMega' || exit("{$n->title}: still a Custom URL category\n");
}
say('  = Main Menu fields in order; every Products category Passive, its View All link in View All Text URL');
