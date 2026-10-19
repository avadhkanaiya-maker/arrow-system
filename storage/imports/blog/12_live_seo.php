<?php
/**
 * Blog: SEO data still different from the live posts after the import (checked against every live <head>, 2026-10-19):
 *   - Publish date: WordPress's `date` is the site's local time (America/New_York); the import used `date_gmt`, which
 *     WordPress left equal to `date` on older posts, so those were 4–5 hours early (a post after 7 pm could show the next
 *     day's date on live but this day on staging … or the reverse). Now `date` in the site's time zone, as live.
 *   - Modified date (article:modified_time, og:updated_time, schema dateModified): the live `modified` date instead of
 *     the import date. Set in the database (a save in the CP would reset it to "now"; that is right: the post changed).
 *   - Share image alt text (og:image:alt; the cover's is also its <img alt>): the live og:image:alt, on the Social Image
 *     (11_social_images.php) or else the cover when its file is the live og:image, if its alt is empty (data/seo.json).
 * Idempotent. Run from the project root after 05_import.php: /opt/lampp/bin/php storage/imports/blog/12_live_seo.php
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_helpers.php';

use craft\elements\Entry;
use craft\helpers\Db;

$tz = new DateTimeZone(Craft::$app->getTimeZone());
$seo = json_decode(file_get_contents(__DIR__ . '/data/seo.json'), true);
$name = fn(?string $url) => rtrim(strtolower(pathinfo(preg_replace('/-\d+x\d+(?=\.\w+$)/', '', rawurldecode(parse_url((string)$url, PHP_URL_PATH) ?? '')), PATHINFO_FILENAME)), '-'); // Craft drops a trailing "-" from file names
$posts = [];
foreach (glob(__DIR__ . '/data/posts-*.json') as $file) foreach (json_decode(file_get_contents($file), true) as $p) $posts[$p['slug']] = $p;

$dates = $modified = $alts = $dayChanged = 0;
foreach (Entry::find()->section('blog')->status(null)->all() as $entry) {
    // the import keeps the live slug, except the post that lost its URL to the LabelExpo exhibition (Phase 16)
    $p = $posts[$entry->slug] ?? $posts[preg_replace('/-article$/', '', $entry->slug)] ?? null;
    if (!$p) continue;

    $published = postDate($p);
    if ($entry->postDate->getTimestamp() !== $published->getTimestamp()) {
        if ($entry->postDate->setTimezone($tz)->format('Y-m-d') !== $published->format('Y-m-d')) $dayChanged++;
        $entry->postDate = $published;
        Craft::$app->getElements()->saveElement($entry) || fail("entry {$entry->slug}", $entry);
        $dates++;
    }

    $share = $entry->getFieldValue('socialImage')->one() ?? $entry->getFieldValue('featuredImage')->one();
    $live = $seo[$p['slug']] ?? [];
    if ($share && !trim((string)$share->alt) && !empty($live['ogImageAlt']) && $name($live['ogImage'] ?? '') === $name($share->filename)) {
        $share->alt = $live['ogImageAlt'];
        Craft::$app->getElements()->saveElement($share) || fail("asset {$share->filename}", $share);
        $alts++;
    }

    // last: any save above sets dateUpdated to now
    $liveModified = Db::prepareDateForDb(new DateTime($p['modified'], $tz));
    if (Db::prepareDateForDb($entry->dateUpdated) !== $liveModified) {
        foreach (['{{%elements}}' => ['id' => $entry->id], '{{%elements_sites}}' => ['elementId' => $entry->id]] as $table => $where)
            Craft::$app->getDb()->createCommand()->update($table, ['dateUpdated' => $liveModified], $where, [], false)->execute();
        $modified++;
    }
}
Craft::$app->getElements()->invalidateCachesForElementType(Entry::class);
say("publish date fixed on $dates posts ($dayChanged now show a different day), modified date = live on $modified, share image alt from live on $alts");
