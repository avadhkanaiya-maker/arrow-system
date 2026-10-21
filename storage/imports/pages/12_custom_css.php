<?php
/**
 * Page builder: custom classes and CSS written by the admin (review 2026-10-21):
 *   CSS Class   on every block of the Pages and Industries page builders (Section Settings tab): class names put on the
 *               block (on its section; a text block gets a <div> around it). Also shows on those blocks in the blog
 *               and product builders, where it works the same.
 *   Custom CSS  a "Custom CSS" tab on pages, industries, products and blog posts (this page only) and on Globals →
 *               Site Settings (every page). Written into the page <head> after the site's CSS (_layouts/base).
 * Idempotent. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/pages/12_custom_css.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\fieldlayoutelements\CustomField;
use craft\fields\PlainText;
use craft\models\FieldLayoutTab;

$entries = Craft::$app->getEntries();
$fields = Craft::$app->getFields();

$class = field(PlainText::class, ['name' => 'CSS Class', 'handle' => 'cssClass',
    'instructions' => 'Your own class names for this block, separated by spaces (letters, numbers, - and _), e.g. “promo-box”. Style them in Custom CSS (the page’s Custom CSS tab, or Globals → Site Settings → Custom CSS for every page).']);
$css = field(PlainText::class, ['name' => 'Custom CSS', 'handle' => 'customCss', 'multiline' => true, 'initialRows' => 12, 'code' => true,
    'instructions' => 'CSS for your classes, e.g. `.promo-box { background: #f5f5f5; }` (no <style> tag). Applies after the site’s own CSS. Test on a draft first.']);

// CSS Class on every block type of the two builders (each type once)
$types = [];
foreach (['pageBuilder', 'industryBuilder'] as $handle) {
    foreach ($fields->getFieldByHandle($handle)->getEntryTypes() as $t) $types[$t->handle] = $t;
}
foreach ($types as $t) addTab($entries->getEntryTypeByHandle($t->handle), 'Section Settings', [$class]);

// Custom CSS: per page (pages, industries, products, blog posts)
foreach (['pages', 'industries', 'products', 'blog'] as $handle) {
    foreach ($entries->getSectionByHandle($handle)->getEntryTypes() as $t) addTab($t, 'Custom CSS', [$css]);
}

// Custom CSS: site-wide (Site Settings)
$set = Craft::$app->getGlobals()->getSetByHandle('siteSettings');
$layout = $set->getFieldLayout();
if (!$layout->getFieldByHandle('customCss')) {
    $tab = new FieldLayoutTab(['name' => 'Custom CSS', 'layout' => $layout]);
    $tab->setElements([new CustomField($css)]);
    $layout->setTabs(array_merge($layout->getTabs(), [$tab]));
    $set->setFieldLayout($layout);
    Craft::$app->getGlobals()->saveSet($set) || fail('global set siteSettings', $set);
    say('  ~ siteSettings: added customCss to \'Custom CSS\'');
}
saveProjectConfig();

// self-check: every builder block type has the class field, Site Settings has the CSS field
foreach ($types as $t) $entries->getEntryTypeByHandle($t->handle)->getFieldLayout()->getFieldByHandle('cssClass') || exit("{$t->handle}: no cssClass\n");
Craft::$app->getGlobals()->getSetByHandle('siteSettings')->getFieldLayout()->getFieldByHandle('customCss') || exit("siteSettings: no customCss\n");
say('  = CSS Class on ' . count($types) . ' block types; Custom CSS on pages, industries, products, blog, Site Settings');
