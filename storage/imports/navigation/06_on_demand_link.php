<?php
/**
 * "On-Demand Label Production" (Main Menu) linked to the custom URL "/#on-demand": a root-relative address that works
 * at a domain root, but on a copy in a subfolder (projects.theglobalwebdev.com/arrow-systems/web/) it left the site.
 * It becomes an Entry item → Homepage with the URL suffix "#on-demand", so the link follows the site URL on every copy.
 * Stops on a staged (unsaved) Main Menu builder change. Idempotent. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/navigation/06_on_demand_link.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\db\Query;
use craft\elements\Entry;
use verbb\navigation\elements\Node;
use verbb\navigation\Navigation;
use verbb\navigation\nodetypes\Entry as EntryNode;

$main = Navigation::$plugin->getMenus()->getMenuByHandle('mainMenu') ?? exit("no Main Menu\n");
foreach ((new Query())->from('{{%navigation_build_sessions}}')->where(['menuId' => $main->id])->all() as $s) {
    !array_filter([$s['structureMoves'], $s['addedNodeIds'], $s['stagedDeletes'], $s['nodeDraftMap'], $s['menuContentDraft']])
        || exit("Main Menu has unsaved builder changes (session {$s['id']}): Save or Discard them, then run again\n");
}

$nodes = Node::find()->menu('mainMenu')->status(null)->title('On-Demand Label Production')->all();
count($nodes) === 1 || exit(count($nodes) . " \"On-Demand Label Production\" items in the Main Menu, expected 1\n");
$node = $nodes[0];
$home = Entry::find()->section('homepage')->status(null)->one() ?? exit("no Homepage\n");

if ($node->type === EntryNode::class && $node->elementId === $home->id && $node->urlSuffix === '#on-demand') {
    say("already linked: {$node->getUrl()}");
    exit;
}

$node->type = EntryNode::class;
$node->setLinkedElementId($home->id);
$node->url = null;
$node->urlSuffix = '#on-demand';
Craft::$app->getElements()->saveElement($node) || fail('On-Demand Label Production', $node);
say("On-Demand Label Production → Homepage + #on-demand: {$node->getUrl()}");
