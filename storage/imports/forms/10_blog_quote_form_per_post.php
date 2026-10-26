<?php
/**
 * The blog's quote form only on the posts that have it on live (review 2026-10-22: "I do not want the form in every post,
 * e.g. paper-vs-film-labels-where-each-works-and-where-it-fails has none on live"). Phase 66 showed it on all 346 posts;
 * live has it in 244 of 354.
 *   - Blog posts get a "Quote Form" switch (Settings tab): on = the form (Blog Page → Article Form) at the end of the post.
 *     Off (the default for new posts) = as before the form: the red Call to Action box unless the post has its own.
 *   - On for every post whose live content holds a "Request Quote From Blog" form (blog/data/posts-*.json, the live posts
 *     as imported); off for the rest.
 * Posts with an open draft are not changed (listed: apply or delete the draft, then run again). Idempotent. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/forms/10_blog_quote_form_per_post.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\elements\Entry;
use craft\fields\Lightswitch;

$entries = Craft::$app->getEntries();
$switch = field(Lightswitch::class, ['name' => 'Quote Form', 'handle' => 'showQuoteForm', 'default' => false,
    'instructions' => 'On = the quote form (Blog Page → Article Form) at the end of this post, as on live. Off = the closing Call to Action box instead.']);
$type = $entries->getEntryTypeByHandle('blogPost') ?? exit("no blogPost entry type\n");
addTab($type, 'Settings', [$switch]);
saveProjectConfig();

// the live posts that have the form
$withForm = [];
foreach (glob(dirname(__DIR__) . '/blog/data/posts-*.json') as $f) {
    foreach (json_decode(file_get_contents($f), true) as $post) {
        if (preg_match('#name="Request Quote From Blog"#', $post['content']['rendered'] ?? '')) $withForm[$post['slug']] = true;
    }
}
count($withForm) > 200 || exit('only ' . count($withForm) . " live posts with the form found: check blog/data/posts-*.json\n");

$drafted = array_flip(array_map('intval', (new craft\db\Query())->select('canonicalId')->from('{{%drafts}}')->column()));
$on = $off = $changed = 0;
$skipped = [];
foreach (Entry::find()->section('blog')->status(null)->all() as $post) {
    $want = isset($withForm[$post->slug]);
    $want ? $on++ : $off++;
    if ((bool)$post->getFieldValue('showQuoteForm') === $want) continue;
    if (isset($drafted[$post->id])) { $skipped[] = $post->slug . ($want ? ' (should be on)' : ' (should be off)'); continue; }
    $post->setFieldValue('showQuoteForm', $want);
    Craft::$app->getElements()->saveElement($post, false) || fail("post {$post->slug}", $post);   // the switch only
    $changed++;
}
say("  ~ Quote Form: on for $on posts (live has the form), off for $off; $changed saved");
if ($skipped) say('  ! open draft, not changed: ' . implode(', ', $skipped));

// self-check: the switch matches live, open drafts aside
foreach (Entry::find()->section('blog')->status(null)->all() as $post) {
    if (isset($drafted[$post->id])) continue;
    (bool)$post->getFieldValue('showQuoteForm') === isset($withForm[$post->slug]) || exit("{$post->slug}: switch not as live\n");
}
say('  = every post (open drafts aside) as live');
