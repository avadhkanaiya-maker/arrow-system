<?php
/**
 * Main Menu items: the "Partner group" switch is removed (review 2026-10-23: not in use; no item had it on). It only added
 * a top border above a Products mega menu group heading (.mega__label--partner, gone from mega-products.twig / site.css).
 *   - the field leaves the Main Menu's item fields, then the field isPartnerGroup is deleted (used nowhere else)
 * Idempotent. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/navigation/04_remove_partner_group.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\fieldlayoutelements\CustomField;
use verbb\navigation\Navigation;

$fields = Craft::$app->getFields();
$field = $fields->getFieldByHandle('isPartnerGroup');
$menu = Navigation::$plugin->getMenus()->getMenuByHandle('mainMenu') ?? exit("no Main Menu\n");

if ($field) {
    foreach (verbb\navigation\elements\Node::find()->status(null)->all() as $n) {   // nothing to lose: stop if any item uses it
        !($n->getFieldLayout()?->getFieldByHandle('isPartnerGroup') && $n->getFieldValue('isPartnerGroup')) || exit("“{$n->title}” has Partner group on: stop\n");
    }
    $layout = $menu->getFieldLayout();
    $removed = 0;
    foreach ($layout->getTabs() as $tab) {
        $kept = array_values(array_filter($tab->getElements(), fn($el) => !($el instanceof CustomField && $el->getFieldUid() === $field->uid)));
        $removed += count($tab->getElements()) - count($kept);
        $tab->setElements($kept);
    }
    if ($removed) {
        $menu->setFieldLayout($layout);
        Navigation::$plugin->getMenus()->saveMenu($menu) || fail('menu mainMenu', $menu);
        say('  - Main Menu items: Partner group switch removed');
    }
    if (Craft::$app->getProjectConfig()->get('fields.' . $field->uid)) $fields->deleteField($field) || fail('delete isPartnerGroup', $field);
    $fields->applyFieldDelete($field->uid);   // a standalone script doesn't run Craft's config handlers
    say('  - field isPartnerGroup');
    saveProjectConfig();
}

// self-check
!$fields->getFieldByHandle('isPartnerGroup') || exit("isPartnerGroup still there\n");
!str_contains(json_encode(Craft::$app->getProjectConfig()->get('navigation')), 'b2ac1849-b531-44c4-bc8d-c2a286528fec') || exit("a menu still lists the field\n");
say('  = no Partner group field, no menu lists it');
