<?php
/**
 * Blog migration, Phases 5–6: import the live posts (data/posts-*.json, the WordPress REST export) as Blog entries.
 *
 *   Per post: title, slug (= the live URL), post date (live), author admin, Lead + Meta Description (the live Rank Math
 *   description), SEO Title (only when it differs from "{title} - Arrow Systems, Inc."), Cover Image (the featured image,
 *   with its alt text), Categories (the live breadcrumb category first), Tags, and the Article Body converted from
 *   Elementor by _converter.php. Images are downloaded once into Assets → Images → blog/.
 *   Posts that redirect on the live site (data/live-redirects.tsv) are not imported; config/redirects.php sends their
 *   URLs on, as live.
 *
 *   Existing posts are skipped (so hand-polished posts, like the pilot, are never overwritten); --update rebuilds them,
 *   except the pilot. A report is written to storage/imports/blog/report.md.
 *
 * Options:  --limit=10            only the first N posts (newest first)
 *           --slugs=a,b           only these posts
 *           --sample              a 10-post trial: mixed years, with video, FAQ, gallery, tables, HTML
 *           --update              rebuild posts that already exist (not the pilot)
 * Back up first:             /opt/lampp/bin/php craft db/backup
 * Run from the project root: /opt/lampp/bin/php storage/imports/blog/05_import.php --sample
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_helpers.php';
require __DIR__ . '/_converter.php';

use craft\elements\Entry;

const PILOT = 'roll-to-roll-vs-sheet-fed-label-printing-throughput-cost';

$opts = [];
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--([a-z]+)(?:=(.*))?$/', $arg, $m)) $opts[$m[1]] = $m[2] ?? true;
}

$live = liveData();
$posts = array_values(array_filter($live['posts'], fn($p) => !isset($live['redirects'][$p['slug']])));
usort($posts, fn($a, $b) => strcmp($b['date_gmt'], $a['date_gmt']));
$localSlugs = array_fill_keys(array_column($posts, 'slug'), true);

if (isset($opts['slugs'])) {
    $want = explode(',', $opts['slugs']);
    $posts = array_values(array_filter($posts, fn($p) => in_array($p['slug'], $want, true)));
} elseif (isset($opts['sample'])) {
    // a mixed trial: newest, oldest, one per few years, and posts with each special widget
    $pick = [];
    $has = fn(string $w) => array_values(array_filter($posts, fn($p) => str_contains($p['content']['rendered'], "data-widget_type=\"$w")));
    foreach (['video.default', 'accordion.default', 'gallery.default', 'media-carousel.default', 'image-gallery.default', 'html.default', 'divider.default'] as $w) {
        $list = $has($w);
        if ($list) $pick[] = $list[intdiv(count($list), 2)]['slug'];
    }
    $pick[] = $posts[1]['slug'];
    $pick[] = $posts[count($posts) - 1]['slug'];
    $withTable = array_values(array_filter($posts, fn($p) => str_contains($p['content']['rendered'], '<table')));
    if ($withTable) $pick[] = $withTable[0]['slug'];
    $pick = array_unique($pick);
    $posts = array_values(array_filter($posts, fn($p) => in_array($p['slug'], $pick, true)));
}
if (isset($opts['limit'])) $posts = array_slice($posts, 0, (int)$opts['limit']);

$section = Craft::$app->getEntries()->getSectionByHandle('blog');
$type = Craft::$app->getEntries()->getEntryTypeByHandle('blogPost');
$elements = Craft::$app->getElements();

$rows = ["# Blog import report", '', 'Run: ' . date('Y-m-d H:i') . ' · ' . count($posts) . ' posts selected', '',
    '| Post | Result | Blocks | H2 | Headings (Craft / live) | Images (Craft / live) | Notes |', '|---|---|---|---|---|---|---|'];
$done = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
$t0 = microtime(true);

foreach ($posts as $n => $post) {
    $slug = $post['slug'];
    $existing = Entry::find()->section('blog')->slug($slug)->status(null)->one();
    if ($existing && (!isset($opts['update']) || $slug === PILOT)) {
        $done['skipped']++;
        $rows[] = "| [$slug](/$slug/) | skipped (exists) | | | | | |";
        continue;
    }
    // any other Craft entry already at this root URL?
    $clash = Entry::find()->uri($slug)->status(null)->andWhere(['not', ['elements.id' => $existing?->id ?? 0]])->one();
    if ($clash) {
        $done['failed']++;
        $rows[] = "| $slug | **failed**: URL taken by {$clash->section->name} entry #{$clash->id} | | | | | |";
        continue;
    }

    $GLOBALS['report'] = [];
    $title = html_entity_decode($post['title']['rendered'], ENT_QUOTES | ENT_HTML5);
    $media = $live['media'][$post['featured_media']] ?? null;
    $cover = $media ? importImage($media['source_url'], $media['alt_text'] ?: html_entity_decode($media['title']['rendered'] ?? '', ENT_QUOTES | ENT_HTML5)) : null;

    $conv = new ElementorConverter($title, $media['source_url'] ?? null, $localSlugs);
    try {
        $blocks = $conv->convert($post['content']['rendered']);
    } catch (Throwable $e) {
        $done['failed']++;
        $rows[] = "| $slug | **failed**: " . str_replace('|', '/', $e->getMessage()) . ' | | | | | |';
        continue;
    }

    if (!$cover && $conv->bannerUrl) {
        // no featured image on live: the post's top banner becomes the cover
        $cover = importImage($conv->bannerUrl, $title);
        if ($cover) $conv->notes[] = 'no featured image on live: the top banner is the cover';
    }
    $og = $live['seo'][$slug] ?? [];
    if (!$cover && !empty($og['ogImage'])) {
        // featured image not in the media export (private on WordPress): the live og:image is the same picture
        $cover = importImage($og['ogImage'], $og['ogImageAlt'] ?? $title);
        if ($cover) $conv->notes[] = 'cover from the live og:image';
    }
    $seo = postSeo($post, $title);
    $lead = $seo['seoDescription'] ?? null;
    if (!$lead) {
        $firstText = '';
        foreach ($blocks as $b) if ($b['type'] === 'richText') { $firstText = trim(html_entity_decode(strip_tags($b['fields']['richText']), ENT_QUOTES | ENT_HTML5)); break; }
        $lead = preg_match('/^(.{20,240}?[.!?])(\s|$)/u', $firstText, $m) ? $m[1] : mb_substr($firstText, 0, 200);
    }

    $entry = $existing ?? new Entry(['sectionId' => $section->id, 'typeId' => $type->id]);
    $entry->title = $title;
    $entry->slug = $slug;
    $entry->postDate = postDate($post);
    $entry->setAuthorIds([1]);
    $entry->setFieldValues(array_merge([
        'sectionLead' => $lead,
        'featuredImage' => $cover ? [$cover->id] : [],
        'articleBody' => builderValue($blocks),
        'blogCategories' => postCategories($post),
        'blogTags' => postTags($post),
        'seoTitle' => null,
    ], $seo));
    if (!$elements->saveElement($entry)) {
        $done['failed']++;
        $rows[] = "| $slug | **failed**: " . str_replace('|', '/', json_encode($entry->getErrors())) . ' | | | | | |';
        continue;
    }
    $done[$existing ? 'updated' : 'created']++;

    $h2 = count(array_filter($blocks, fn($b) => $b['type'] === 'heading' && $b['fields']['headingLevel'] === 'h2'));
    $notes = array_merge($conv->notes, $GLOBALS['report']);
    if (!$cover) $notes[] = 'no cover image';
    if (!$h2) $notes[] = 'no H2: "On this page" is empty';
    $s = $conv->stats;
    $rows[] = sprintf('| [%s](/%s/) | %s | %d | %d | %d / %d | %d / %d | %s |', $slug, $slug, $existing ? 'updated' : 'created',
        count($blocks), $h2, $s['headings'], $s['liveHeadings'], $s['images'], $s['liveImages'],
        str_replace('|', '/', implode('; ', array_unique($notes))));
    printf("%3d/%d %-8s %s (%d blocks, %d notes) %.0fs\n", $n + 1, count($posts), $existing ? 'updated' : 'created', $slug, count($blocks), count($notes), microtime(true) - $t0);
}

$summary = sprintf('**%d created, %d updated, %d skipped, %d failed** in %.0f s', $done['created'], $done['updated'], $done['skipped'], $done['failed'], microtime(true) - $t0);
array_splice($rows, 3, 0, [$summary, '']);
file_put_contents(__DIR__ . '/report.md', implode("\n", $rows) . "\n");
say(strip_tags(str_replace('**', '', $summary)) . ' → storage/imports/blog/report.md');
