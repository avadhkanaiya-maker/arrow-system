<?php
/**
 * Blog migration, Phase 7: homepage section K "More Insights On Labels and Packaging Industry" (the latest 3 posts),
 * design: Arrowsystem_local/index.html "K. INDUSTRY INSIGHTS / BLOG".
 *   Entry type "K · Latest blog posts" (latestPosts) in the Homepage Sections matrix: Small Heading, Heading,
 *   Posts to Show (optional, up to 3 hand-picked posts; empty = the newest 3, fewer than 3 = topped up with the newest),
 *   "View all" button (→ the Blog Page), Anchor ID.
 *   The section is added once, after "J · Sustainable printing CTA" (before L), as in the design.
 * Run from the project root: /opt/lampp/bin/php storage/imports/blog/06_homepage.php
 */
require __DIR__ . '/_bootstrap.php';

use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;

$fields = Craft::$app->getFields();
$f = fn(string $handle, array $opts = []) => new CustomField($fields->getFieldByHandle($handle), $opts);

$type = entryType('Latest Blog Posts', 'latestPosts', ['Content' => [
    $f('eyebrow'),
    $f('sectionHeading', ['required' => true]),
    $f('ctaLink', ['label' => 'View All Link', 'instructions' => 'The link at the top right, e.g. “View all articles” → Blog Page.']),
    $f('anchorId'),
]], ['icon' => 'newspaper', 'titleLabel' => 'Name in the list',
    'description' => 'Blog post cards: up to 3 hand-picked posts, or the newest 3 automatically. Posts: Entries → Blog.']);

// Posts to Show (added 2026-10-13): reuses the Entries field relatedPosts (Blog posts, max 3) with its own label
$postsPicker = fn() => $f('relatedPosts', ['label' => 'Posts to Show',
    'instructions' => 'Optional: pick up to 3 blog posts and drag them into order. Leave empty to show the newest 3 automatically; with 1 or 2 picked, the newest posts fill the rest.']);
$layout = $type->getFieldLayout();
if (!$layout->getFieldByHandle('relatedPosts')) {
    $tab = $layout->getTabs()[0];
    $els = [];
    foreach ($tab->getElements() as $el) {
        $els[] = $el;
        if ($el instanceof CustomField && $el->getField()->handle === 'sectionHeading') $els[] = $postsPicker();
    }
    $tab->setElements($els);
    $type->setFieldLayout($layout);
    $type->description = 'Blog post cards: up to 3 hand-picked posts, or the newest 3 automatically. Posts: Entries → Blog.';
    Craft::$app->getEntries()->saveEntryType($type) || fail('entry type latestPosts', $type);
    say('  ~ latestPosts: + Posts to Show');
}

$matrix = $fields->getFieldByHandle('homepageSections');
$types = $matrix->getEntryTypes();
if (!in_array('latestPosts', array_map(fn($t) => $t->handle, $types), true)) {
    $types[] = $type;
    $matrix->setEntryTypes($types);
    $fields->saveField($matrix) || fail('homepageSections', $matrix);
    say('  ~ homepageSections: + Latest Blog Posts');
}
saveProjectConfig();
// the Matrix field was cached without the new entry type: reload it before saving the homepage
$fields->refreshFields();

$home = Entry::find()->section('homepage')->one();
$sections = $home->homepageSections->status(null)->all();
if (array_filter($sections, fn($s) => $s->type->handle === 'latestPosts')) {
    say('Homepage already has a Latest Blog Posts section.');
    exit;
}
$blogPage = Entry::find()->section('blogPage')->one();
$sortOrder = [];
foreach ($sections as $s) {
    $sortOrder[] = $s->id;
    if ($s->type->handle === 'ctaBand') $sortOrder[] = 'new1';
}
if (!in_array('new1', $sortOrder, true)) $sortOrder[] = 'new1';
$home->setFieldValue('homepageSections', [
    'entries' => ['new1' => ['type' => 'latestPosts', 'title' => 'K · Latest blog posts', 'fields' => [
        'eyebrow' => 'Blog',
        'sectionHeading' => 'More Insights On [Labels and Packaging] Industry',
        'ctaLink' => ['type' => 'entry', 'value' => $blogPage->id, 'label' => 'View all articles'],
        'anchorId' => 'insights',
    ]]],
    'sortOrder' => $sortOrder,
]);
Craft::$app->getElements()->saveElement($home) || fail('homepage', $home);
say('Homepage: + K · Latest blog posts (after J)');
