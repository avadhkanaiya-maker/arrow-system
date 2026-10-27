<?php
/**
 * Newsletter block (2026-10-26): a page with a page builder shows the newsletter band (above the footer) only while its
 * builder has a Newsletter block (templates/_layouts/base.twig). This gives every existing page one, at the end of its
 * builder; new pages get one from modules/builderui.
 * Only the new blocks are saved, not the pages, so their dateUpdated (the blog's schema dateModified) stays as it is.
 * Pages with an open draft (or unsaved edits) are skipped and listed: a block added under a draft is lost when the draft
 * is applied. Add it in the draft by hand, or re-run once the drafts are applied or deleted.
 * Idempotent. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/builder/03_newsletter_block.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\db\Query;
use craft\elements\Entry;

$type = Craft::$app->getEntries()->getEntryTypeByHandle('newsletter') ?? exit("Apply the project config first (entry type newsletter)\n");
$builders = ['pages' => 'pageBuilder', 'products' => 'productBuilder', 'industries' => 'industryBuilder', 'blog' => 'articleBody', 'homepage' => 'homepageSections'];
$drafted = (new Query())->select('canonicalId')->from('{{%drafts}}')->where(['not', ['canonicalId' => null]])->column();

$added = $had = 0;
$skipped = [];
foreach ($builders as $section => $handle) {
    $field = Craft::$app->getFields()->getFieldByHandle($handle);
    foreach (Entry::find()->section($section)->status(null)->all() as $owner) {
        if (Entry::find()->ownerId($owner->id)->fieldId($field->id)->type('newsletter')->status(null)->exists()) { $had++; continue; }
        if (in_array($owner->id, $drafted)) { $skipped[] = "$section: {$owner->title} (#{$owner->id})"; continue; }
        $count = $owner->getFieldValue($handle)->status(null)->count();
        $block = new Entry(['fieldId' => $field->id, 'typeId' => $type->id, 'siteId' => $owner->siteId]);
        $block->setOwner($owner);
        $block->setPrimaryOwner($owner);
        Craft::$app->getElements()->saveElement($block) || fail("Newsletter block on {$owner->slug}", $block);
        $last = Entry::find()->ownerId($owner->id)->fieldId($field->id)->status(null)->all();
        (count($last) === $count + 1 && end($last)->id === $block->id) || exit("{$owner->slug}: the block isn't last\n");
        $added++;
    }
}
say("  + Newsletter block on $added pages ($had had one already)");
if ($skipped) say("  ! skipped, open draft or unsaved edits (add the block there by hand):\n    " . implode("\n    ", $skipped));
