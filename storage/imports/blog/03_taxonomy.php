<?php
/**
 * Blog migration: the live blog categories (45, nested up to 3 levels) and tags (245), from the WordPress REST API
 * export in data/ (categories.json, tags-*.json).
 *   Blog Categories  same names and slugs, same parents → same URLs: /category/{parent}/{slug}/. A–Z on each level.
 *                    Both "Food Packaging" categories are kept (D7). Empty ones (e.g. Financing) are kept too.
 *   Blog Tags        same names and slugs → /tag/{slug}/ (noindex, as live) (D3).
 * Existing entries (matched by URI) are left as they are, so CP edits survive a re-run.
 * Run from the project root: /opt/lampp/bin/php storage/imports/blog/03_taxonomy.php
 */
require __DIR__ . '/_bootstrap.php';

use craft\elements\Entry;

$entries = Craft::$app->getEntries();
$categoriesSection = $entries->getSectionByHandle('blogCategories');
$tagsSection = $entries->getSectionByHandle('blogTags');
$categoryType = $entries->getEntryTypeByHandle('blogCategory');
$tagType = $entries->getEntryTypeByHandle('blogTag');

$wpCats = json_decode(file_get_contents(__DIR__ . '/data/categories.json'), true);
$byId = array_column($wpCats, null, 'id');

/** "category/digital-printing/food-packaging" for a WordPress category */
function wpCategoryUri(array $cat, array $byId): string {
    $slugs = [$cat['slug']];
    while ($cat['parent'] && isset($byId[$cat['parent']])) {
        $cat = $byId[$cat['parent']];
        array_unshift($slugs, $cat['slug']);
    }
    return 'category/' . implode('/', $slugs);
}

say('Blog Categories');
$created = 0;
$add = function (int $parentWpId, ?Entry $parent) use (&$add, &$created, $wpCats, $byId, $categoriesSection, $categoryType) {
    $children = array_filter($wpCats, fn($c) => (int)$c['parent'] === $parentWpId);
    usort($children, fn($a, $b) => strcasecmp(html_entity_decode($a['name']), html_entity_decode($b['name'])));
    foreach ($children as $cat) {
        $uri = wpCategoryUri($cat, $byId);
        $entry = Entry::find()->section('blogCategories')->uri($uri)->status(null)->one();
        $isNew = !$entry;
        if ($isNew) {
            $entry = new Entry([
                'sectionId' => $categoriesSection->id,
                'typeId' => $categoryType->id,
                'title' => html_entity_decode($cat['name'], ENT_QUOTES | ENT_HTML5),
                'slug' => $cat['slug'],
            ]);
            if ($parent) $entry->setParentId($parent->id);
            Craft::$app->getElements()->saveElement($entry) || fail("category $uri", $entry);
            $created++;
        }
        if ($entry->uri !== $uri) say("  ! $uri saved as {$entry->uri}");
        say(sprintf('  %s %-58s %3d posts on live', $isNew ? '+' : '=', $entry->uri, $cat['count']));
        $add((int)$cat['id'], $entry);
    }
};
$add(0, null);
say("  $created created, " . Entry::find()->section('blogCategories')->status(null)->count() . ' in total');

say('Blog Tags');
$tags = [];
foreach (glob(__DIR__ . '/data/tags-*.json') as $f) $tags = array_merge($tags, json_decode(file_get_contents($f), true));
usort($tags, fn($a, $b) => strcasecmp($a['name'], $b['name']));
$created = 0;
foreach ($tags as $tag) {
    if (Entry::find()->section('blogTags')->slug($tag['slug'])->status(null)->exists()) continue;
    $entry = new Entry([
        'sectionId' => $tagsSection->id,
        'typeId' => $tagType->id,
        'title' => html_entity_decode($tag['name'], ENT_QUOTES | ENT_HTML5),
        'slug' => $tag['slug'],
    ]);
    Craft::$app->getElements()->saveElement($entry) || fail("tag {$tag['slug']}", $entry);
    if ($entry->slug !== $tag['slug']) say("  ! tag {$tag['slug']} saved as {$entry->slug}");
    $created++;
}
say("  $created created, " . Entry::find()->section('blogTags')->status(null)->count() . ' in total (live: ' . count($tags) . ')');
