<?php
/**
 * Blog QA (report 2026-10-27): the fixes a re-import (05_import.php --update) does not make.
 *   B07  test posts "QA Workflow Test" and "Builder Test Post" disabled (kept for the pending CMS tests; delete before launch)
 *   B19  /labelexpo-brussels-2023-article/ disabled: on live the post can't be reached (the exhibition owns its URL)
 *   B14  "10 Step Guide For the best Frozen Food Pouches…": Quote Form on, as live (its open draft kept it off)
 *   B13  Cover Image = live's top banner where it is another picture than the featured image (3 posts, checked by eye;
 *        the others show the same picture or a banner strip). The Social Image keeps the featured image, as live's og:image.
 *   B08 / B09 on the hand-edited posts (not re-imported): the repeated title heading removed, the Headline set.
 * Posts with an open draft get no block changes. Idempotent. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/blog/15_qa_fixes.php
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_helpers.php';
require __DIR__ . '/_converter.php';

use craft\elements\Entry;

$elements = Craft::$app->getElements();
$post = fn(string $slug) => Entry::find()->section('blog')->slug($slug)->status(null)->one() ?? exit("no post $slug\n");
$save = function (Entry $e, string $what) use ($elements) {
    $elements->saveElement($e) || fail($e->slug, $e);
    say("  ~ {$e->slug}: $what");
};
$live = liveData();
$localSlugs = array_fill_keys(array_keys($live['posts']), true);
$convert = function (string $slug) use ($live, $localSlugs): ElementorConverter {
    $p = $live['posts'][$slug];
    $media = $live['media'][$p['featured_media']] ?? null;
    $c = new ElementorConverter(html_entity_decode($p['title']['rendered'], ENT_QUOTES | ENT_HTML5), $media['source_url'] ?? null, $localSlugs);
    $c->convert($p['content']['rendered']);
    return $c;
};

// B07, B19
foreach (['qa-workflow-test', 'builder-test-post', 'labelexpo-brussels-2023-article'] as $slug) {
    $e = $post($slug);
    if ($e->enabled) { $e->enabled = false; $save($e, 'disabled'); }
}

// B14
$e = $post('10-step-guide-for-the-best-frozen-food-pouches-with-water-based-inks');
if (!$e->showQuoteForm) { $e->setFieldValue('showQuoteForm', true); $save($e, 'Quote Form on'); }

// B13: live's top banner as the cover (live slug → its banner is the first image widget, ElementorConverter::$bannerUrl)
foreach (['custom-dog-treat-bags-in-house-printing-for-pet-brands', 'lansen-label-wins-national-quality-award-in-guyana-powered-by-the-arrowjet-aqua-330r', 'what-are-mylar-bags-made-of'] as $slug) {
    $banner = $convert($slug)->bannerUrl ?? exit("no live banner for $slug\n");
    $asset = importImage($banner, html_entity_decode($live['posts'][$slug]['title']['rendered'], ENT_QUOTES | ENT_HTML5)) ?? exit("could not import $banner\n");
    $e = $post($slug);
    $cover = $e->featuredImage->one();
    if ($cover?->id === $asset->id) continue;
    if ($cover && !$e->socialImage->one()) $e->setFieldValue('socialImage', [$cover->id]);
    $e->setFieldValue('featuredImage', [$asset->id]);
    $save($e, "cover {$asset->filename} (was {$cover?->filename}; that one stays the share image)");
}

// B09 on a hand-edited post: the Headline only
$e = $post('2025-packaging-ink-market-dynamics');
$headline = $convert('2025-packaging-ink-market-dynamics')->headline;
if ($headline && $e->headline !== $headline) { $e->setFieldValue('headline', $headline); $save($e, "headline \"$headline\""); }

// B08 on a hand-edited post: its first block repeats the title
$e = $post('all-about-label-materials');
$hasDraft = Entry::find()->draftOf($e)->status(null)->exists();
$first = $e->articleBody->status(null)->one();
$norm = fn(string $s) => strtolower(preg_replace('/[^a-z0-9]+/i', '', $s));
if (!$hasDraft && $first && $first->type->handle === 'heading' && $norm($first->headingText) === $norm($e->title)) {
    $elements->deleteElement($first, true) || fail('block', $first);
    say("  ~ {$e->slug}: removed the first heading \"{$first->headingText}\" (the title again)");
}
