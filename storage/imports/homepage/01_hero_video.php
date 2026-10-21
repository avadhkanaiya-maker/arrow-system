<?php
/**
 * Homepage hero slider: video slides (the design's 2026-10-19 hero video: Arrowsystem/index.html, js/home.js, css/home.css).
 * Each Hero Slide gets a Slide Type:
 *   Image  (default) the ready-made banner, as before
 *   Video  an uploaded video file (MP4 …: plays instantly, no third-party player) OR a YouTube link (any YouTube URL or
 *          the ID); a file wins when both are set. The Banner Image is the poster: shown until the video plays, and for
 *          visitors who ask for reduced motion. Muted, autoplaying, no controls, replays from the start.
 * + Display Time (seconds, optional) for any slide; empty = 7 s for images, 20 s for videos (the design).
 * The video fields show only when Slide Type = Video. Existing slides keep their content (the layout's existing elements
 * are kept). The design's video slide ("ArrowJet MTS-48 in Action") is added as the first slide when no video slide exists.
 * Idempotent. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/homepage/01_hero_video.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\elements\Asset;
use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Assets;
use craft\fields\conditions\OptionsFieldConditionRule;
use craft\fields\Dropdown;
use craft\fields\Number;

const DESIGN = '/home/avadh/server/theglobalwebdev/demo/public_html/Arrowsystem';
$fields = Craft::$app->getFields();
$entries = Craft::$app->getEntries();
$images = Craft::$app->getVolumes()->getVolumeByHandle('images');

// ---- fields -----------------------------------------------------------------------------------------------------------
say('Fields');
field(Dropdown::class, ['name' => 'Slide Type', 'handle' => 'slideType', 'options' => options(['image' => 'Image', 'video' => 'Video'], 'image'),
    'instructions' => 'Image = a ready-made banner. Video = an uploaded video file or a YouTube link, with the banner image as its poster.']);
field(Assets::class, ['name' => 'Video File', 'handle' => 'videoFile', 'maxRelations' => 1, 'restrictFiles' => true, 'allowedKinds' => ['video'],
    'sources' => ['volume:' . $images->uid], 'defaultUploadLocationSource' => 'volume:' . $images->uid, 'defaultUploadLocationSubpath' => 'hero',
    'instructions' => 'Upload an MP4 (H.264, ideally under 10 MB, 16:9). It starts instantly, without YouTube. Leave empty to use the YouTube link.']);
field(Number::class, ['name' => 'Display Time', 'handle' => 'slideDuration', 'decimals' => 0, 'min' => 3, 'max' => 120, 'suffix' => 'seconds',
    'instructions' => 'How long the slide shows before the next one. Empty = 7 seconds (images) or 20 seconds (videos).']);

// ---- Hero Slide layout: keep the existing elements (their uids hold the content), add the new ones -----------------------
$slide = $entries->getEntryTypeByHandle('heroSlide') ?? exit("no heroSlide entry type\n");
$layout = $slide->getFieldLayout();
if (!$layout->getFieldByHandle('slideType')) {
    $typeEl = new CustomField($fields->getFieldByHandle('slideType'));
    $fileEl = new CustomField($fields->getFieldByHandle('videoFile'));
    $linkEl = new CustomField($fields->getFieldByHandle('videoUrl'), ['label' => 'YouTube Link',
        'instructions' => 'Or a YouTube link (watch, youtu.be, shorts …) or the video ID. Used when no Video File is uploaded.']);
    $durationEl = new CustomField($fields->getFieldByHandle('slideDuration'));

    $tabs = $layout->getTabs();
    $els = [$typeEl];
    foreach ($tabs[0]->getElements() as $el) {
        if ($el instanceof CustomField && $el->attribute() === 'bannerImage') {
            $el->label = 'Banner Image / Video Poster';
            $el->instructions = 'Image slide: the ready-made banner, 1920 × 600 px (16:5). Video slide: the poster shown until the video plays (16:9, e.g. 1280 × 720). Fill in its Alt Text.';
            $els[] = $el; $els[] = $fileEl; $els[] = $linkEl;
            continue;
        }
        $els[] = $el;
    }
    $els[] = $durationEl;
    $tabs[0]->setElements($els);
    $layout->setTabs($tabs);
    $slide->setFieldLayout($layout);
    $entries->saveEntryType($slide) || fail('entry type heroSlide', $slide);
    say('  ~ Hero Slide: + Slide Type, Video File, YouTube Link (video only), Display Time; Banner Image = poster for videos');
}

// Video File and YouTube Link show only when Slide Type = Video (set once the elements belong to the saved layout: the rule
// needs the layout to find the Slide Type element)
$slide = $entries->getEntryTypeByHandle('heroSlide');
$layout = $slide->getFieldLayout();
$typeEl = current(array_filter($layout->getCustomFieldElements(), fn($el) => $el->attribute() === 'slideType'));
$changed = false;
foreach ($layout->getCustomFieldElements() as $el) {
    if (!in_array($el->attribute(), ['videoFile', 'videoUrl'], true) || $el->getElementCondition()?->getConditionRules()) continue;
    $el->setElementCondition(['class' => craft\elements\conditions\entries\EntryCondition::class, 'elementType' => Entry::class, 'conditionRules' => [[
        'class' => OptionsFieldConditionRule::class, 'fieldUid' => $typeEl->getField()->uid, 'layoutElementUid' => $typeEl->uid,
        'operator' => 'in', 'values' => ['video'],
    ]]]);
    $changed = true;
}
if ($changed) {
    $slide->setFieldLayout($layout);
    $entries->saveEntryType($slide) || fail('entry type heroSlide', $slide);
    say('  ~ Video File / YouTube Link: shown only for Slide Type = Video');
}
saveProjectConfig();

// ---- the design's video slide, first ---------------------------------------------------------------------------------
$home = Entry::find()->section('homepage')->one();
$slider = $home ? current(array_filter($home->getFieldValue('homepageSections')->all(), fn($b) => $b->type->handle === 'heroSlider')) : null;
if ($slider && !array_filter($slider->getFieldValue('slides')->all(), fn($s) => ($s->getFieldValue('slideType')->value ?? '') === 'video')) {
    $folder = Craft::$app->getAssets()->ensureFolderByFullPathAndVolume('hero', $images, false);
    $poster = Asset::find()->folderId($folder->id)->filename('mts-48-video-poster.webp')->one();
    if (!$poster) {
        $tmp = Craft::$app->getPath()->getTempPath() . '/' . uniqid('hero') . '-mts-48-video-poster.webp';
        copy(DESIGN . '/assets/hero/web/mts-48-video-poster.webp', $tmp) || exit("poster not found in the design\n");
        $poster = new Asset(['tempFilePath' => $tmp, 'filename' => 'mts-48-video-poster.webp', 'volumeId' => $images->id, 'newFolderId' => $folder->id]);
        $poster->alt = 'ArrowJet MTS-48 industrial UV LED flatbed printer for membrane switch and overlay graphics';
        $poster->setScenario(Asset::SCENARIO_CREATE);
        Craft::$app->getElements()->saveElement($poster) || fail('poster', $poster);
    }
    $mts = Entry::find()->section('products')->slug('arrowjet-mts-48')->one();
    $ids = $slider->getFieldValue('slides')->ids();
    $slider->setFieldValue('slides', ['sortOrder' => array_merge(['new1'], $ids), 'entries' => ['new1' => ['type' => 'heroSlide', 'fields' => [
        'slideType' => 'video',
        'videoUrl' => 'https://www.youtube.com/watch?v=HjWQU9GJ4bI',
        'bannerImage' => [$poster->id],
        'categoryLabel' => 'Watch it run',
        'productName' => 'ArrowJet MTS-48 in Action',
        'primaryCta' => $mts ? ['type' => 'entry', 'value' => $mts->id, 'label' => 'Explore Product'] : ['type' => 'url', 'value' => 'https://arrsys.com/product/arrowjet-mts-48/', 'label' => 'Explore Product'],
        'slideDuration' => 20,
    ]]]]);
    Craft::$app->getElements()->saveElement($slider) || fail('hero slider', $slider);
    say('  + hero slide 1: ArrowJet MTS-48 in Action (YouTube video, 20 s), as the design');
}
