<?php
/**
 * Products, menus: menu items that link to a live product page (https://arrsys.com/product/<slug>/) are linked to the
 * Craft product entry instead (like Blog and Exhibitions), so they follow the entry's URL. Old slugs that 301 on live
 * are followed to their product (same list as config/redirects.php). Items without a product here are left as they are.
 * Idempotent. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/products/03_menus.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\elements\Entry;
use verbb\navigation\elements\Node;

$moved = ['arrowjet-aqua-330r' => 'arrowjet-aqua-330r-lite-2', 'arrowjet-bolt' => 'arrow-bolt-330r-hybrid-pro-m',
    'arrowjet-hybrid-m-pro-plus' => 'arrowjet-aqua-330r-hybrid-pro-plus-m', 'arrowjet-nova-250' => 'arrowjet-nova-250-plus',
    'arrow-systems-anycut-iii-13-roll-to-roll-laser-cutter' => 'arrowcut-nova-330r-roll-to-roll-laser-cutter'];
foreach (Node::find()->status(null)->all() as $n) {
    if (!preg_match('~^https://arrsys\.com/product/([^/?#]+)/?$~',(string)$n->url, $m)) continue;
    $product = Entry::find()->section('products')->slug($moved[$m[1]] ?? $m[1])->status(null)->one();
    if (!$product) { say("  ! {$n->menu->handle} “{$n->title}”: no product for {$m[1]}, left as is"); continue; }
    $n->type = Entry::class;
    $n->elementId = $product->id;
    $n->url = null;
    Craft::$app->getElements()->saveElement($n) || fail("menu node {$n->id}", $n);
    say("  ~ {$n->menu->handle} “{$n->title}” → {$product->url}");
}
