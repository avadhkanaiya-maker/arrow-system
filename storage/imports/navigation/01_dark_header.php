<?php
/**
 * Header + footer, the design's dark version (Arrowsystem/ on the server, CHANGELOG 2026-10-19: "Dark header and footer"
 * → "Lighter red on menu hover … label-sheet pattern in the footer"). The markup is unchanged (the templates already
 * render the design's); this script does the admin side:
 *   - Globals → Site Settings → Brand: + "Logo on Dark Background" (logoLight), filled with the design's
 *     assets/brand/ASI-Logo-4-light.webp (white lettering, red arrow). The header and footer use it; the normal Logo stays
 *     for light backgrounds (and is the fallback).
 *   - Main Menu (Navigation), as the design's menus now:
 *       Products → "Arrow Systems equipment": Gerber Plotters moves to the end of Label Finishers ("Gerber cutting
 *       plotters"); the "Partner Product Lines" category becomes "Specialty Printers" (panel "Specialty printers":
 *       Matan SprinG3, Gerber Edge FX, Corrugated & End-of-Line Printers) as the group's last category; the then empty
 *       "Partner Brands" group is deleted.
 *       Materials: + Gerber Foils, Gerber Premium Cast Vinyl Film, 3M Aviation Films (moved from Partner Product Lines),
 *       before Order Supplies.
 *   Items are found by their titles (works on any copy of the database). Idempotent. Back up first. Run from the project
 *   root: /opt/lampp/bin/php storage/imports/navigation/01_dark_header.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\elements\Asset;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Assets;
use verbb\navigation\elements\Node;

const LIGHT_LOGO = '/home/avadh/server/theglobalwebdev/demo/public_html/Arrowsystem/assets/brand/ASI-Logo-4-light.webp';
$fields = Craft::$app->getFields();
$elements = Craft::$app->getElements();

// ---- Site Settings: Logo on Dark Background -------------------------------------------------------------------------
say('Logo');
$images = Craft::$app->getVolumes()->getVolumeByHandle('images');
field(Assets::class, ['name' => 'Logo on Dark Background', 'handle' => 'logoLight', 'maxRelations' => 1, 'restrictFiles' => true,
    'allowedKinds' => ['image'], 'sources' => ['volume:' . $images->uid],
    'instructions' => 'The logo with light lettering, for the dark header and footer. Empty = the Logo above.']);
$logoField = $fields->getFieldByHandle('logo');
$logoField->instructions = 'The logo with dark lettering, for light backgrounds. (The dark header and footer use Logo on Dark Background.)';
$fields->saveField($logoField) || fail('field logo', $logoField);

$settings = Craft::$app->getGlobals()->getSetByHandle('siteSettings');
$layout = $settings->getFieldLayout();
if (!$layout->getFieldByHandle('logoLight')) {
    foreach ($layout->getTabs() as $tab) {
        $els = [];
        foreach ($tab->getElements() as $el) {
            $els[] = $el;
            if ($el instanceof CustomField && $el->attribute() === 'logo') $els[] = new CustomField($fields->getFieldByHandle('logoLight'));
        }
        $tab->setElements($els);
    }
    $settings->setFieldLayout($layout);
    Craft::$app->getGlobals()->saveSet($settings) || fail('global set siteSettings', $settings);
    say('  ~ Site Settings → Brand: + Logo on Dark Background');
}
$settings = Craft::$app->getGlobals()->getSetByHandle('siteSettings');
if (!$settings->getFieldValue('logoLight')->exists()) {
    $asset = Asset::find()->volumeId($images->id)->folderPath('')->filename('ASI-Logo-4-light.webp')->one();
    if (!$asset) {
        is_file(LIGHT_LOGO) || exit("light logo not found: " . LIGHT_LOGO . "\n");
        $tmp = Craft::$app->getPath()->getTempPath() . '/' . uniqid('logo') . '-ASI-Logo-4-light.webp';
        copy(LIGHT_LOGO, $tmp);
        $asset = new Asset(['tempFilePath' => $tmp, 'filename' => 'ASI-Logo-4-light.webp', 'volumeId' => $images->id,
            'newFolderId' => Craft::$app->getAssets()->getRootFolderByVolumeId($images->id)->id, 'avoidFilenameConflicts' => true]);
        $asset->alt = $settings->getFieldValue('logo')->one()?->alt ?: 'Arrow Systems, Inc.';
        $asset->setScenario(Asset::SCENARIO_CREATE);
        $elements->saveElement($asset) || fail('asset light logo', $asset);
    }
    $settings->setFieldValue('logoLight', [$asset->id]);
    $elements->saveElement($settings) || fail('site settings', $settings);
    say("  ~ Logo on Dark Background = {$asset->filename}");
}
saveProjectConfig();

// ---- Main Menu ------------------------------------------------------------------------------------------------------
say('Main Menu');
$nav = verbb\navigation\Navigation::$plugin->getMenus()->getMenuByHandle('mainMenu') ?? exit("no Main Menu\n");
$structureId = $nav->structureId;
$structures = Craft::$app->getStructures();
/** A Main Menu item by title, optionally inside $parent */
$find = function (string $title, ?Node $parent = null): ?Node {
    $q = Node::find()->handle('mainMenu')->status(null)->title($title);
    if ($parent) $q->descendantOf($parent);
    return $q->one();
};
$save = fn(Node $n) => $elements->saveElement($n) || fail("menu item {$n->title}", $n);

$products = $find('Products') ?? exit("no Products item\n");
$equipment = $find('Arrow Systems equipment', $products) ?? exit("no 'Arrow Systems equipment'\n");
$finishers = $find('Label Finishers', $equipment) ?? exit("no 'Label Finishers'\n");
$materials = Node::find()->handle('mainMenu')->status(null)->level(1)->title('Materials')->one() ?? exit("no Materials item\n");

// Gerber Plotters → last item of Label Finishers
$plotters = $find('Gerber Plotters', $products);
if ($plotters && $plotters->getParent()?->id !== $finishers->id) {
    $plotters->itemDescription = 'Gerber cutting plotters';
    $save($plotters);
    $structures->append($structureId, $plotters, $finishers);
    say('  ~ Gerber Plotters → Label Finishers');
}

// Partner Product Lines → Specialty Printers (last category of Arrow Systems equipment)
$specialty = $find('Specialty Printers', $products) ?? $find('Partner Product Lines', $products);
if ($specialty && ($specialty->title !== 'Specialty Printers' || $specialty->getParent()?->id !== $equipment->id)) {
    $specialty->title = 'Specialty Printers';
    $specialty->panelTitle = 'Specialty printers';
    $save($specialty);
    $structures->append($structureId, $specialty, $equipment);
    say('  ~ Partner Product Lines → Specialty Printers, under Arrow Systems equipment');
}

// Gerber Foils, Gerber Premium Cast Vinyl Film, 3M Aviation Films → Materials, before Order Supplies
$orderSupplies = $find('Order Supplies', $materials);
foreach (['Gerber Foils', 'Gerber Premium Cast Vinyl Film', '3M Aviation Films'] as $title) {
    $node = $find($title, $products);
    if (!$node) continue;
    $orderSupplies ? $structures->moveBefore($structureId, $node, $orderSupplies) : $structures->append($structureId, $node, $materials);
    say("  ~ $title → Materials");
}

// Specialty Printers: the design's order
if ($specialty) {
    $order = array_filter(array_map(fn($t) => $find($t, $specialty), ['Matan SprinG3', 'Gerber Edge FX', 'Corrugated & End-of-Line Printers']));
    $now = array_map(fn($n) => $n->title, Node::find()->handle('mainMenu')->status(null)->descendantOf($specialty)->all());
    if ($now !== array_values(array_map(fn($n) => $n->title, $order))) {
        foreach ($order as $n) $structures->append($structureId, $n, $specialty);
        say('  ~ Specialty Printers: Matan SprinG3, Gerber Edge FX, Corrugated & End-of-Line Printers');
    }
}

// the Partner Brands group, now empty
$partner = $find('Partner Brands', $products);
if ($partner && !Node::find()->handle('mainMenu')->status(null)->descendantOf($partner)->exists()) {
    $elements->deleteElement($partner, true);
    say('  - Partner Brands (empty group)');
}
Craft::$app->getElements()->invalidateCachesForElementType(Node::class);
