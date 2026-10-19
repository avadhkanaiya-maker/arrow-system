<?php
/**
 * ONE-OFF, ALREADY DONE (2026-10-13): it needs the Neo plugin, which has since been removed. Kept as the record of how
 * the content was moved (DEVELOPMENT_PHASES.md → Phase 14).
 *
 * Move the blog's page builder from Neo to Matrix (2026-10-13), keeping every block and its content.
 *
 *   1. copy:   for every Blog post (and its unsaved provisional drafts), read the Neo blocks of articleBody and write the
 *              same blocks to the Matrix field articleBlocks (made by 01_structure.php): same block types (entry types
 *              with the same handles), same field values, same order, same enabled / collapsed state; child blocks go
 *              into the parent's nested field (Steps → stepItems, Comparison → compareColumns, Product List →
 *              productItems, FAQ → faqItems)
 *   2. check:  the number of blocks per post must match
 *   3. switch (with --switch): remove the Neo field from the Blog Post layout, delete it (and its blocks), and rename
 *              the Matrix field articleBlocks → articleBody, so the templates keep using entry.articleBody
 *
 * After --switch: php craft plugin/uninstall neo, composer remove spicyweb/craft-neo.
 * Back up first. Run from the project root:
 *   /opt/lampp/bin/php storage/imports/blog/08_neo_to_matrix.php            (copy + check; --ids=1,2 for a trial)
 *   /opt/lampp/bin/php storage/imports/blog/08_neo_to_matrix.php --switch   (after checking the front end)
 */
require __DIR__ . '/_bootstrap.php';

use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Matrix;

const CHILD_FIELDS = ['steps' => 'stepItems', 'compare' => 'compareColumns', 'productList' => 'productItems', 'faq' => 'faqItems'];

$fields = Craft::$app->getFields();
$elements = Craft::$app->getElements();
$neo = $fields->getFieldByHandle('articleBody');
$matrix = $fields->getFieldByHandle('articleBlocks');

if (in_array('--switch', $argv, true)) {
    if (!$matrix || !$neo || $neo instanceof Matrix) {
        say('Nothing to switch (articleBody is already the Matrix field).');
        exit;
    }
    $entriesService = Craft::$app->getEntries();
    $postType = $entriesService->getEntryTypeByHandle('blogPost');
    $layout = $postType->getFieldLayout();
    foreach ($layout->getTabs() as $tab) {
        $tab->setElements(array_values(array_filter($tab->getElements(),
            fn($el) => !($el instanceof CustomField && $el->getField()->id === $neo->id))));
    }
    $postType->setFieldLayout($layout);
    $entriesService->saveEntryType($postType) || fail('entry type blogPost', $postType);
    say('  ~ blogPost: Neo field removed from the layout');
    $fields->deleteField($neo) || fail('delete Neo field', $neo);
    say('  - Neo field articleBody deleted');
    $matrix->handle = 'articleBody';
    $fields->saveField($matrix) || fail('rename articleBlocks', $matrix);
    say('  ~ articleBlocks renamed to articleBody');
    saveProjectConfig();
    exit;
}

if (!$matrix) {
    fwrite(STDERR, "Run 01_structure.php first (it creates the Matrix field articleBlocks).\n");
    exit(1);
}

/** Matrix value for a list of Neo blocks (in order, levels 1 and 2) */
function matrixValue(array $neoBlocks): array {
    // tree: level-2 blocks belong to the last level-1 block before them
    $tree = [];
    foreach ($neoBlocks as $b) {
        if ((int)$b->level === 1) $tree[] = ['block' => $b, 'children' => []];
        elseif ($tree) $tree[array_key_last($tree)]['children'][] = $b;
        else throw new Exception("child block {$b->id} without a parent");
        if ((int)$b->level > 2) throw new Exception("block {$b->id} is nested deeper than 2 levels");
    }
    $toEntries = function (array $items) use (&$toEntries) {
        $out = ['entries' => [], 'sortOrder' => []];
        foreach ($items as $i => $item) {
            $b = $item['block'] ?? $item;
            $fields = $b->getSerializedFieldValues();
            $type = $b->getType()->handle;
            if (isset(CHILD_FIELDS[$type])) {
                $fields[CHILD_FIELDS[$type]] = $toEntries(array_map(fn($c) => ['block' => $c], $item['children'] ?? []));
            } elseif (!empty($item['children'])) {
                throw new Exception("block {$b->id} ($type) has child blocks but no nested field");
            }
            $key = 'new' . ($i + 1);
            $out['entries'][$key] = ['type' => $type, 'enabled' => (bool)$b->enabled, 'collapsed' => (bool)$b->getCollapsed(), 'fields' => $fields];
            $out['sortOrder'][] = $key;
        }
        return $out;
    };
    return $toEntries($tree);
}

/** Number of blocks (incl. child blocks) in a Matrix value / on an element */
function countMatrix(Entry $entry): int {
    $n = 0;
    foreach ($entry->getFieldValue('articleBlocks')->status(null)->all() as $e) {
        $n++;
        $type = $e->getType()->handle;
        if (isset(CHILD_FIELDS[$type])) $n += $e->getFieldValue(CHILD_FIELDS[$type])->status(null)->count();
    }
    return $n;
}

$targets = array_merge(
    Entry::find()->section('blog')->status(null)->orderBy('elements.id')->all(),
    Entry::find()->section('blog')->drafts()->provisionalDrafts()->status(null)->all(),
);
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--ids=')) {
        $ids = array_map('intval', explode(',', substr($arg, 6)));
        $targets = array_values(array_filter($targets, fn($e) => in_array($e->id, $ids, true)));
    }
}
say(count($targets) . ' elements to copy (posts + provisional drafts)');
$t0 = microtime(true);
$bad = 0;
foreach ($targets as $i => $entry) {
    $label = ($entry->getIsDraft() ? 'draft of ' : '') . $entry->slug;
    $neoBlocks = $entry->getFieldValue('articleBody')->status(null)->all();
    if (countMatrix($entry) === count($neoBlocks) && count($neoBlocks) > 0) {
        continue; // already copied
    }
    $entry->setFieldValue('articleBlocks', matrixValue($neoBlocks));
    if (!$elements->saveElement($entry)) {
        $bad++;
        say("  ! $label: " . json_encode($entry->getErrors()));
        continue;
    }
    $fresh = $entry->getIsDraft()
        ? Entry::find()->id($entry->id)->drafts()->provisionalDrafts()->status(null)->one()
        : Entry::find()->id($entry->id)->status(null)->one();
    $n = countMatrix($fresh);
    if ($n !== count($neoBlocks)) {
        $bad++;
        say("  ! $label: " . count($neoBlocks) . " Neo blocks, $n Matrix blocks");
    }
    if (($i + 1) % 25 === 0) printf("  %d/%d  %.0fs\n", $i + 1, count($targets), microtime(true) - $t0);
}
say(sprintf('Done in %.0fs: %d with problems.', microtime(true) - $t0, $bad));

// check every element
$mismatch = 0;
foreach ($targets as $entry) {
    $fresh = $entry->getIsDraft()
        ? Entry::find()->id($entry->id)->drafts()->provisionalDrafts()->status(null)->one()
        : Entry::find()->id($entry->id)->status(null)->one();
    if (countMatrix($fresh) !== count($fresh->getFieldValue('articleBody')->status(null)->all())) $mismatch++;
}
say("Check: $mismatch of " . count($targets) . ' elements have a different number of blocks.');
