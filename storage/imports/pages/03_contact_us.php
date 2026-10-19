<?php
/**
 * SUPERSEDED IN PART by 04_generic_builder.php (2026-10-19): the Form block is general (no office panel / map), the
 * Distributors block became the general Card Grid, and the Contact Us page is built from general blocks. Run 04 after this.
 * Pages: the Contact Us page at /contact-us/ (as live, data/contact-us.json read from https://arrsys.com/contact-us/ on
 * 2026-10-19; decisions D3–D6: data, maps, schema and card order as live):
 *   Page Header   H1 "Contact [Us]", the live banner photo (businessman-touching-virtual-screen-1.jpg)
 *   Form          Formie "Contact Us" form under "Contact Us", beside the office panel "Arrow Systems, Inc. USA" (Site
 *                 Settings details) with the live Google map of the office
 *   Distributors  "Distributor Location", the live distributors' Google My Map, the 19 live cards in live order (live
 *                 text kept, spellings included; emails decoded from Cloudflare's protection)
 *   SEO           the live title and meta description
 * Then links to the live page → this page: the menu items (top bar, main menu, footer) and the blog's default CTA button.
 * Skips the page when it exists (edit it in the CP). Back up first. Run from the project root after 02_form_blocks.php:
 *   /opt/lampp/bin/php storage/imports/pages/03_contact_us.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\elements\Asset;
use craft\elements\Entry;
use craft\helpers\Assets as AssetsHelper;
use verbb\formie\elements\Form;
use verbb\navigation\elements\Node;

const LIVE_URL = 'https://arrsys.com/contact-us/';
$data = json_decode(file_get_contents(__DIR__ . '/data/contact-us.json'), true);
count($data['distributors']) === 19 || exit("data: expected 19 distributors\n");

/** An image in Images → pages/, downloaded once (by file name) */
function pageImage(string $url, string $alt): Asset {
    $volume = Craft::$app->getVolumes()->getVolumeByHandle('images');
    $folder = Craft::$app->getAssets()->ensureFolderByFullPathAndVolume('pages', $volume, false);
    $filename = AssetsHelper::prepareAssetName(basename(parse_url($url, PHP_URL_PATH)));
    if ($asset = Asset::find()->folderId($folder->id)->filename($filename)->one()) return $asset;
    $bytes = @file_get_contents($url, false, stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0 (Arrow Systems staging migration)\r\n", 'timeout' => 90]]))
        ?: exit("could not download $url\n");
    $tmp = Craft::$app->getPath()->getTempPath() . '/' . uniqid('page') . '-' . $filename;
    file_put_contents($tmp, $bytes);
    $asset = new Asset(['tempFilePath' => $tmp, 'filename' => $filename, 'volumeId' => $volume->id, 'newFolderId' => $folder->id, 'avoidFilenameConflicts' => true]);
    $asset->alt = $alt;
    $asset->setScenario(Asset::SCENARIO_CREATE);
    Craft::$app->getElements()->saveElement($asset) || fail("asset $filename", $asset);
    return $asset;
}

// ---- the page --------------------------------------------------------------------------------------------------------
$page = Entry::find()->section('pages')->slug('contact-us')->status(null)->one();
if (!$page) {
    $form = Form::find()->handle('contactUs')->one() ?? exit("no Formie form contactUs\n");
    $banner = pageImage($data['banner'], 'Person touching a virtual screen with contact icons');
    $cards = ['sortOrder' => [], 'entries' => []];
    foreach ($data['distributors'] as $i => $d) {
        $cards['sortOrder'][] = "new$i";
        $cards['entries']["new$i"] = ['type' => 'distributor', 'fields' => ['headingText' => $d['country'], 'subheading' => $d['company'], 'richText' => $d['details']]];
    }
    $section = Craft::$app->getEntries()->getSectionByHandle('pages');
    $page = new Entry(['sectionId' => $section->id, 'typeId' => Craft::$app->getEntries()->getEntryTypeByHandle('page')->id,
        'title' => 'Contact Us', 'slug' => 'contact-us']);
    $page->setFieldValues([
        'seoTitle' => $data['seoTitle'],
        'seoDescription' => $data['seoDescription'],
        'pageBuilder' => ['sortOrder' => ['new1', 'new2', 'new3'], 'entries' => [
            'new1' => ['type' => 'industryHeader', 'fields' => ['sectionHeading' => 'Contact [Us]', 'mediaImage' => [$banner->id]]],
            'new2' => ['type' => 'formBlock', 'fields' => ['formieForm' => [$form->id], 'sectionHeading' => $data['formHeading'],
                'showContactPanel' => true, 'headingText' => $data['officeHeading'], 'mapEmbedUrl' => $data['officeMap'], 'anchorId' => 'contact']],
            'new3' => ['type' => 'distributorsBlock', 'fields' => ['sectionHeading' => $data['distributorsHeading'],
                'mapEmbedUrl' => $data['distributorMap'], 'distributorItems' => $cards]],
        ]],
    ]);
    Craft::$app->getElements()->saveElement($page) || fail('page contact-us', $page);
    $page->uri === 'contact-us' || exit("the page got /{$page->uri}/: another entry uses /contact-us/\n");
    say("  + page /{$page->uri}/ (Page Header, Form, Distributors: " . count($data['distributors']) . ' cards)');
}

// ---- links to the live page → this page ----------------------------------------------------------------------------
foreach (Node::find()->status(null)->all() as $n) {
    if (rtrim((string)$n->url, '/') . '/' !== LIVE_URL) continue;
    $n->type = Entry::class;
    $n->elementId = $page->id;
    $n->url = null;
    Craft::$app->getElements()->saveElement($n) || fail("menu node {$n->id}", $n);
    say("  ~ menu {$n->menu->handle} “{$n->title}” → the page");
}
$blogPage = Entry::find()->section('blogPage')->status(null)->one();
$cta = $blogPage?->getFieldValue('ctaLink');
if ($cta && rtrim((string)$cta->url, '/') . '/' === LIVE_URL) {
    $blogPage->setFieldValue('ctaLink', ['type' => 'entry', 'value' => $page->id, 'label' => $cta->label]);
    Craft::$app->getElements()->saveElement($blogPage) || fail('blog page', $blogPage);
    say("  ~ Blog Page default CTA “{$cta->label}” → the page");
}
