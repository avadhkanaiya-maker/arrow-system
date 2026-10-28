<?php
/**
 * Answers: the menu items named "Answers" (Main Menu › Resources, Footer › Resources: Custom items without a URL, or with
 * live's /answers/ address) → the Answers Page entry, so they follow its URL. Stops if a menu has unsaved builder
 * changes. Idempotent. Run from the project root after 02_content.php: /opt/lampp/bin/php storage/imports/answers/03_menus.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\db\Query;
use craft\elements\Entry;
use verbb\navigation\elements\Node;
use verbb\navigation\nodetypes\Custom;
use verbb\navigation\nodetypes\Entry as EntryNode;

$listing = Entry::find()->section('answersPage')->one() ?? exit("no Answers Page: run 01_structure.php\n");
foreach (Node::find()->status(null)->title('Answers')->all() as $n) {
    if ($n->type !== Custom::class || !in_array(rtrim((string)$n->url, '/'), ['', '#', 'https://arrsys.com/answers'], true)) continue;
    foreach ((new Query())->from('{{%navigation_build_sessions}}')->where(['menuId' => $n->menuId])->all() as $s) {
        !array_filter([$s['structureMoves'], $s['addedNodeIds'], $s['stagedDeletes'], $s['nodeDraftMap'] ?? null, $s['menuContentDraft'] ?? null])
            || exit("menu {$n->menu->handle} has unsaved builder changes: Save or Discard them, then run again\n");
    }
    $n->type = EntryNode::class;
    $n->elementId = $listing->id;
    $n->url = null;
    Craft::$app->getElements()->saveElement($n) || fail("menu node {$n->id}", $n);
    say("  ~ menu {$n->menu->handle} “{$n->title}” → /answers/");
}
foreach (Node::find()->status(null)->title('Answers')->all() as $n) say("  = {$n->menu->handle} “Answers” → " . ($n->getUrl() ?: '(no link)'));
