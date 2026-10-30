<?php
/**
 * Hero slider on phones (2026-10-28, QA): cropping the wide 16:5 banners to a square cut their headlines and products.
 * Live (arrsys.com) shows phones a separate set of square banners (1000 × 1000, title on top, machine and products
 * below). Hero slides now have an optional Mobile Banner Image (after Banner Image, image slides only); phones show it
 * whole, and a slide without one shows the whole Banner Image.
 * Live's four mobile banners (data/hero-mobile/, renamed after their slides; live's names were ArrowJet-Aqua-Hybrid-M,
 * -M-1, -M-2, -M-1-1) go into Images › pages/ and onto their slides. The MTS-48 slides have none on live.
 * Also clears the focal points set for the earlier square crop (2026-10-28), no longer used.
 * Template: _blocks/home/hero-slider.twig; styles in craft.css. Idempotent. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/pages/38_hero_mobile_banners.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';
require __DIR__ . '/_helpers.php';

use craft\elements\Asset;
use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Assets;

$fields = Craft::$app->getFields();
$banner = $fields->getFieldByHandle('bannerImage') ?? exit("no field bannerImage\n");
$mobile = field(Assets::class, array_merge($banner->getSettings(), [
    'name' => 'Mobile Banner Image', 'handle' => 'mobileBannerImage', 'maxRelations' => 1, 'searchable' => false,
    'instructions' => 'Optional: a square version of the banner for phones, 1000 × 1000 px (title on top, machine and products below). Empty = phones show the whole Banner Image, small. Fill in its Alt Text.',
]));
saveProjectConfig();

$type = Craft::$app->getEntries()->getEntryTypeByHandle('heroSlide') ?? exit("no entry type heroSlide\n");
$layout = $type->getFieldLayout();
if (!$layout->getFieldByHandle('mobileBannerImage')) {
    foreach ($layout->getTabs() as $tab) {
        $els = $tab->getElements();
        foreach ($els as $i => $el) {
            if (!$el instanceof CustomField || $el->getField()->handle !== 'bannerImage') continue;
            // the Banner Image's own condition (Slide Type = Image), with new rule uids
            $condition = $el->getElementCondition()?->getConfig();
            foreach ($condition['conditionRules'] ?? [] as $k => $rule) unset($condition['conditionRules'][$k]['uid']);
            array_splice($els, $i + 1, 0, [new CustomField($mobile, ['elementCondition' => $condition, 'width' => $el->width])]);
            $tab->setElements($els);
            break 2;
        }
    }
    $type->setFieldLayout($layout);
    Craft::$app->getEntries()->saveEntryType($type) || fail('entry type heroSlide', $type);
    say('  ~ heroSlide: + Mobile Banner Image after Banner Image');
    saveProjectConfig();
}

// ---- live's mobile banners onto their slides (by the slide's Banner Image) --------------------------------------------
$map = [
    'arrowjet-aqua-800m.webp' => 'arrowjet-aqua-800m-mobile.webp',
    'arrowjet-aqua-330r-hybrid-pro-m.webp' => 'arrowjet-aqua-330r-hybrid-pro-m-mobile.webp',
    'arrowjet-aqua-330r-lite-2.webp' => 'arrowjet-aqua-330r-lite-2-mobile.webp',
    'arrowjet-nova-250r.webp' => 'arrowjet-nova-250r-mobile.webp',
];
$hasDraft = fn(int $id) => Entry::find()->draftOf($id)->status(null)->exists() || Entry::find()->draftOf($id)->provisionalDrafts()->status(null)->exists();
foreach (Entry::find()->type('heroSlide')->status(null)->all() as $slide) {
    $desktop = $slide->bannerImage->one();
    if (!$desktop || !isset($map[$desktop->filename]) || $slide->mobileBannerImage->exists()) continue;
    $top = $slide; while ($o = $top->getPrimaryOwner()) $top = $o;
    if ($hasDraft($top->getCanonicalId())) { say("  ! slide {$slide->id}: {$top->title} has an open draft, skipped"); continue; }
    $id = pageImage(__DIR__ . '/data/hero-mobile/' . $map[$desktop->filename], (string)$desktop->alt);
    $slide->setFieldValue('mobileBannerImage', [$id]);
    Craft::$app->getElements()->saveElement($slide) || fail("slide {$slide->id}", $slide);
    say("  ~ slide {$slide->id} ({$slide->productName}): Mobile Banner Image = {$map[$desktop->filename]}");
}

foreach (Asset::find()->filename(array_merge(array_keys($map), ['Screenshot-from-2026-10-12-17-01-36.png']))->all() as $a) {
    if (!$a->getHasFocalPoint()) continue;
    $a->setFocalPoint(null);
    Craft::$app->getElements()->saveElement($a) || fail("asset {$a->id}", $a);
    say("  ~ {$a->filename}: focal point cleared");
}
