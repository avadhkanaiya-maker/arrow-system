<?php
/**
 * Blog QA (report 2026-10-27): the field changes the fixes need. Generic page-builder options, not blog-only blocks.
 *   - Blog Post: "Headline" (B09). Live old posts show an in-content heading as their H1, often not the post title;
 *     the H1 shows the Headline when set. The Title stays the name in listings, breadcrumbs and the SEO title.
 *   - Video block: a "Video File" (MP4) as well as the YouTube link (B05: live's self-hosted WordPress videos)
 *   - Heading block: an optional "Link" on the heading text (B15) and H5 / H6 levels (B16)
 * Idempotent. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/blog/14_qa_structure.php
 */
require __DIR__ . '/_bootstrap.php';

use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\fields\Dropdown;
use craft\fields\Link;
use craft\fields\PlainText;
use craft\models\EntryType;

$fields = Craft::$app->getFields();
$entries = Craft::$app->getEntries();

/** Put $new into $et's layout right after the first element that $after() accepts (skipped if the field is there) */
function insertAfter(EntryType $et, callable $after, CustomField $new): void {
    $layout = $et->getFieldLayout();
    if ($layout->getFieldByHandle($new->getField()->handle)) return;
    foreach ($layout->getTabs() as $tab) {
        $els = $tab->getElements();
        foreach ($els as $i => $el) {
            if (!$after($el)) continue;
            array_splice($els, $i + 1, 0, [$new]);
            $tab->setElements($els);
            $et->setFieldLayout($layout);
            $GLOBALS['entries']->saveEntryType($et) || fail("entry type {$et->handle}", $et);
            say("  ~ {$et->handle}: added {$new->getField()->handle}");
            return;
        }
    }
    fail("entry type {$et->handle}: no place for {$new->getField()->handle}", $et);
}
$isField = fn(string $handle) => fn($el) => $el instanceof CustomField && $el->getField()->handle === $handle;

// ---- Heading block: H5 / H6, Link ----
$level = $fields->getFieldByHandle('headingLevel');
$values = array_column($level->options, 'value');
if (!in_array('h5', $values, true)) {
    $level->options = array_merge($level->options, [
        ['label' => 'H5: minor heading', 'value' => 'h5', 'default' => false],
        ['label' => 'H6: minor heading', 'value' => 'h6', 'default' => false],
    ]);
    $level->instructions = 'H2 = a main section (listed in “On this page”). H3 / H4 = a sub-heading inside a section. H5 / H6 = minor headings (older posts).';
    $fields->saveField($level) || fail('field headingLevel', $level);
    say('  ~ headingLevel: + H5, H6');
}
$headingLink = field(Link::class, [
    'name' => 'Link', 'handle' => 'headingLink',
    'instructions' => 'Optional: the heading text links here (e.g. a product heading to its product page).',
    'types' => ['url', 'entry'], 'showLabelField' => false,
    'typeSettings' => ['url' => ['allowRootRelativeUrls' => true, 'allowAnchors' => true]],
]);
insertAfter($entries->getEntryTypeByHandle('heading'), $isField('headingText'), new CustomField($headingLink));

// ---- Video block: an MP4 file as well as the YouTube link ----
$video = $entries->getEntryTypeByHandle('video');
$layout = $video->getFieldLayout();
foreach ($layout->getCustomFieldElements() as $el) {
    if ($el->getField()->handle === 'videoUrl' && $el->required) {
        $el->required = false;
        $el->instructions = 'A YouTube link, e.g. https://youtu.be/PbjRZJS6dbQ. Or leave it empty and choose a Video File.';
        $video->description = 'A YouTube video or an MP4 file';
        $video->setFieldLayout($layout);
        $entries->saveEntryType($video) || fail('entry type video', $video);
        say('  ~ video: YouTube link optional');
    }
}
insertAfter($video, $isField('videoUrl'), new CustomField($fields->getFieldByHandle('videoFile'), [
    'instructions' => 'An MP4 file, played in a player with controls (used when there is no YouTube link).',
]));

// ---- Blog Post: Headline ----
$headline = field(PlainText::class, [
    'name' => 'Headline', 'handle' => 'headline',
    'instructions' => 'The H1 on the post page, when it should differ from the Title. Listings, breadcrumbs and the SEO title keep using the Title.',
]);
insertAfter($entries->getEntryTypeByHandle('blogPost'), fn($el) => $el instanceof EntryTitleField, new CustomField($headline));

saveProjectConfig();
