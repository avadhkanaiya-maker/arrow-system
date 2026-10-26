<?php
/**
 * Page builder: a Media Carousel block (review 2026-10-22: "image carousel option, images as well as videos"), general:
 * Pages and Industries builders, group Sections (after Tabs).
 *   Media Carousel  Heading, Intro (optional) · Slides · Section Settings (spacing, background, CSS Class)
 *   Slide           Image · Video File (an uploaded MP4: plays with controls, the Image is its poster) · YouTube Link
 *                   (click to play, the Content Block's player) · Caption. A slide shows its video when it has one, else
 *                   the image.
 * One slide at a time, arrows + dots + swipe (industries/blocks/mediaCarousel.twig, craft.css, craft.js).
 * Idempotent. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/pages/20_media_carousel.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';
require dirname(__DIR__) . '/products/_helpers.php';

use craft\fieldlayoutelements\CustomField;
use craft\fields\Matrix;

$fields = Craft::$app->getFields();
$entries = Craft::$app->getEntries();
$f = fn(string $handle, array $opts = []) => new CustomField($fields->getFieldByHandle($handle) ?? throw new Exception("No field $handle"), $opts);

$slide = blockType('Carousel Slide', 'carouselSlide', 'image', [
    $f('mediaImage', ['label' => 'Image', 'instructions' => 'The slide’s picture; for a video slide, the picture shown before it plays. 16:9 works best (e.g. 1600 × 900). Set its alt text.']),
    $f('videoFile', ['label' => 'Video File', 'instructions' => 'Optional: an uploaded MP4. The slide shows the video (with play controls) instead of the image.']),
    $f('videoUrl', ['label' => 'YouTube Link', 'instructions' => 'Optional: a YouTube address, used when there’s no Video File. Plays when clicked.']),
    $f('headingText', ['label' => 'Caption', 'required' => false, 'instructions' => 'Optional text under the slide.']),
], '{headingText}');
$slides = field(Matrix::class, ['name' => 'Slides', 'handle' => 'carouselSlides', 'viewMode' => Matrix::VIEW_MODE_CARDS,
    'createButtonLabel' => 'Add slide', 'minEntries' => 1, 'instructions' => 'One slide each (image, uploaded video or YouTube video), in this order.'], [$slide]);
$carousel = blockType('Media Carousel', 'mediaCarousel', 'images', [
    $f('sectionHeading', ['label' => 'Heading', 'instructions' => 'Optional, e.g. “See It in [Action]” ([words] in red).']),
    $f('sectionLead', ['label' => 'Intro']),
    $f('carouselSlides'),
], '{sectionHeading}', 'Images and videos, one at a time, with arrows and dots.');
addTab($entries->getEntryTypeByHandle('mediaCarousel'), 'Section Settings',
    array_map(fn($h) => $fields->getFieldByHandle($h), ['spacingTop', 'spacingBottom', 'sectionBackground', 'cssClass']));

foreach (['pageBuilder', 'industryBuilder'] as $handle) {
    $field = $fields->getFieldByHandle($handle);
    $rows = array_map(fn($t) => ['uid' => $t->uid, 'group' => $t->group], $field->getEntryTypes());
    if (in_array($carousel->uid, array_column($rows, 'uid'), true)) continue;
    $after = array_search($entries->getEntryTypeByHandle('tabsBlock')->uid, array_column($rows, 'uid'), true);
    array_splice($rows, $after === false ? count($rows) : $after + 1, 0, [['uid' => $carousel->uid, 'group' => 'Sections']]);
    $field->setEntryTypes($rows);
    $fields->saveField($field) || fail("field $handle", $field);
    say("  ~ $handle: + Media Carousel (Sections)");
}
saveProjectConfig();

foreach (['pageBuilder', 'industryBuilder'] as $handle) {
    in_array('mediaCarousel', array_map(fn($t) => $t->handle, $fields->getFieldByHandle($handle)->getEntryTypes()), true) || exit("$handle: no Media Carousel\n");
}
say('  = Media Carousel on the Pages and Industries builders');
