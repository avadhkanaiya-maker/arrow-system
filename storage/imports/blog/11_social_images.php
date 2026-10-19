<?php
/**
 * Blog: the live share image (og:image) where it differs from the post's featured image (3 posts on 2026-10-15:
 * labelexpo-brussels-2023, eliminating-wasted-inventory-…, screen-printing-vs-digital-inkjet-…). The import gave every
 * post the featured image as its cover and left Social Image empty, so those posts shared the cover instead of the live
 * picture. This sets their Social Image to the live og:image (downloaded into the Blog folder, live alt text).
 * The image goes to the entry that serves the same URL on staging (the live og:image was read from that URL): for
 * /labelexpo-brussels-2023/ that is the exhibition (live shows the page there; the post moved to -article, Phase 16).
 * Compares file names without WordPress's "-300x250" size suffix. Entries whose Social Image is already set are skipped.
 * Idempotent. Run from the project root after 05_import.php: /opt/lampp/bin/php storage/imports/blog/11_social_images.php
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_helpers.php';

use craft\elements\Entry;

$load = function (string $prefix): array {
    $out = [];
    foreach (glob(__DIR__ . "/data/$prefix-*.json") as $file) foreach (json_decode(file_get_contents($file), true) as $row) $out[$row['id']] = $row;
    return $out;
};
$media = $load('media');
$seo = json_decode(file_get_contents(__DIR__ . '/data/seo.json'), true);
$name = fn(?string $url) => strtolower(pathinfo(preg_replace('/-\d+x\d+(?=\.\w+$)/', '', parse_url((string)$url, PHP_URL_PATH) ?? ''), PATHINFO_FILENAME));

$set = 0;
foreach ($load('posts') as $post) {
    $og = $seo[$post['slug']]['ogImage'] ?? null;
    $featured = $media[$post['featured_media']]['source_url'] ?? null;
    if (!$og || !$featured || $name($og) === $name($featured)) continue; // same picture, or no featured image (then the og:image is the cover)
    $entry = Entry::find()->uri($post['slug'])->status(null)->one();
    if (!$entry || !$entry->getFieldLayout()->getFieldByHandle('socialImage')) { say("  ? /{$post['slug']}/: no entry with a Social Image field at this URL"); continue; }
    if ($entry->getFieldValue('socialImage')->exists()) continue;
    $image = importImage($og, $seo[$post['slug']]['ogImageAlt'] ?? $entry->title) ?? exit("could not download $og\n");
    $entry->setFieldValue('socialImage', [$image->id]);
    Craft::$app->getElements()->saveElement($entry) || fail("entry {$entry->slug}", $entry);
    say("  ~ {$entry->section->handle} /{$entry->uri}/: Social Image = {$image->filename}");
    $set++;
}
say("Social Image set on $set entries");
