<?php
/**
 * Take /arrow-material/ (the materials listing page, 35_arrow_material.php) off the site's navigation (client request
 * 2026-10-29): the page itself stays live at its URL, but nothing on the site links to it any more. The menu items and
 * the cards linking to it are switched off (the "Arrow Material" item under Main Menu → Materials, the "Arrow Material"
 * card on /arrow-materials/); switch them on again to bring the links back. Replaces the first version, which disabled
 * the page: it is switched on again here. Stops on an open draft of the page or of a card's page, or unsaved
 * menu-builder changes. Idempotent. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/pages/40_unlink_arrow_material.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\db\Query;
use craft\elements\Entry;
use verbb\navigation\elements\Node;

$page = Entry::find()->section('pages')->uri('arrow-material')->status(null)->one() ?? exit("no /arrow-material/ page\n");
if (Entry::find()->draftOf($page)->provisionalDrafts(null)->status(null)->exists()) exit("/arrow-material/ has an open draft: save or discard it, then run again\n");

// first: Navigation switches an entry's menu items on / off with the entry
if (!$page->enabled) {
    $page->enabled = true;
    Craft::$app->getElements()->saveElement($page) || fail('page arrow-material', $page);
    say('  ~ /arrow-material/: live again');
}

foreach (array_filter(Node::find()->status(null)->all(), fn($n) => $n->elementId == $page->id) as $n) {
    if (!Node::find()->id($n->id)->status(null)->one()->enabled) continue;
    foreach ((new Query())->from('{{%navigation_build_sessions}}')->where(['menuId' => $n->menuId])->all() as $s) {
        !array_filter([$s['structureMoves'], $s['addedNodeIds'], $s['stagedDeletes'], $s['nodeDraftMap'], $s['menuContentDraft']])
            || exit("menu {$n->menuId} has unsaved builder changes: Save or Discard them, then run again\n");
    }
    $n = Node::find()->id($n->id)->status(null)->one();
    $n->enabled = false;
    Craft::$app->getElements()->saveElement($n) || fail("menu node {$n->id}", $n);
    say("  ~ menu {$n->menu->handle} “{$n->title}”: switched off");
}

foreach (Entry::find()->type('card')->status('enabled')->all() as $card) {
    if (!str_contains(json_encode($card->ctaLink?->serialize()), "{entry:{$page->id}@")) continue;
    $top = $card; while ($top->getPrimaryOwner()) $top = $top->getPrimaryOwner();
    if ($top->getIsDraft() || $top->getIsRevision()) continue;
    if (Entry::find()->draftOf($top)->provisionalDrafts(null)->status(null)->exists()) exit("/{$top->uri}/ has an open draft: save or discard it, then run again\n");
    $card->enabled = false;
    Craft::$app->getElements()->saveElement($card) || fail("card {$card->id}", $card);
    say("  ~ /{$top->uri}/: card “{$card->title}” switched off");
}
