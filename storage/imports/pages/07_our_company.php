<?php
/**
 * Pages: /our-company/, redesigned from the live page (https://arrsys.com/our-company/, read 2026-10-21). Live has only a
 * banner image, the H1 "Our Company" and 3 paragraphs; the text is kept word for word (its typos too, as live) and laid
 * out with the general page-builder blocks:
 *   Page Header   Full-width banner: H1 "Our [Company]", paragraph 1 as the intro, the live banner (Our-Company.webp)
 *                 shown whole under the text; buttons Request a Quote (Site Settings) and Contact Us (the page)
 *   Stats Strip   the company figures (Globals → Stats Strip)
 *   Card Grid     "Segments We Serve": the segments paragraph 1 names, each linked to its industry page (that page's
 *                 card photo and summary); materials → the live Label Materials page, with the design's material photo
 *   Content Block paragraph 2 (worldwide equipment, materials and support), link: Request a Free Quote
 *   CTA Band      paragraph 3 (40 years' experience), button Contact Us
 *   SEO           as live (title = the default format, the live meta description)
 * Then the Main Menu and Footer "Our Company" items link to the page. Skips the page when it exists. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/pages/07_our_company.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\elements\Asset;
use craft\elements\Entry;
use craft\helpers\Assets as AssetsHelper;
use verbb\navigation\elements\Node;

const LIVE_URL = 'https://arrsys.com/our-company/';
const DESIGN = '/home/avadh/server/theglobalwebdev/demo/public_html/Arrowsystem';

/** An image in Images → pages/ from a URL or a local file, added once (by file name) */
function pageImage(string $src, string $alt): Asset {
    $volume = Craft::$app->getVolumes()->getVolumeByHandle('images');
    $folder = Craft::$app->getAssets()->ensureFolderByFullPathAndVolume('pages', $volume, false);
    $filename = AssetsHelper::prepareAssetName(basename(parse_url($src, PHP_URL_PATH) ?: $src));
    if ($asset = Asset::find()->folderId($folder->id)->filename($filename)->one()) return $asset;
    $bytes = is_file($src) ? file_get_contents($src)
        : @file_get_contents($src, false, stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0 (Arrow Systems staging migration)\r\n", 'timeout' => 90]]));
    $bytes || exit("could not read $src\n");
    $tmp = Craft::$app->getPath()->getTempPath() . '/' . uniqid('page') . '-' . $filename;
    file_put_contents($tmp, $bytes);
    $asset = new Asset(['tempFilePath' => $tmp, 'filename' => $filename, 'volumeId' => $volume->id, 'newFolderId' => $folder->id, 'avoidFilenameConflicts' => true]);
    $asset->alt = $alt;
    $asset->setScenario(Asset::SCENARIO_CREATE);
    Craft::$app->getElements()->saveElement($asset) || fail("asset $filename", $asset);
    return $asset;
}

// the live text, word for word
$p1 = 'Arrow System Inc. specializes in the manufacturing and distribution of advanced printing systems and materials for a multitude of industries. Some of the segments we serve include color label printing and digital finishing, sign production, GHS certified label printing, as well as materials and process innovations. We also proudly serve the military, government, and airline industries. As an industry leader in these segments, Arrow Systems strives on providing exceptional quality and service with each interaction.';
$p2 = 'We manufacture and distribute digital printing equipment and materials worldwide and have support around the clock to help our customers at any time. If you are looking for a new color label printer, or die-cut materials on any type of substrate feel free to contact us for a free quote. We are always happy to help, both in the North American market, as well as in Europe, South America, Asia, and Africa. We have local support throughout the world and have the experience necessary to help you find the right printing equipment for your companies needs.';
$p3 = 'With over 40 years’ experience in the packaging, printing, and coating industries, we possess the knowledge and professionalism your business has been searching for! We invite you to contact us for more information or services today!';
$seoDescription = 'Arrow System Inc. specializes in the manufacturing and distribution of advanced printing systems and materials for a multitude of industries. Some of the';

$page = Entry::find()->section('pages')->slug('our-company')->status(null)->one();
if (!$page) {
    $industry = fn(string $slug) => Entry::find()->section('industries')->slug($slug)->one() ?? exit("no industry $slug\n");
    $contact = Entry::find()->section('pages')->slug('contact-us')->one();
    $quote = Craft::$app->getGlobals()->getSetByHandle('siteSettings')->getFieldValue('quoteLink');
    $quoteUrl = $quote?->url ?: 'https://arrsys.com/request-quote/';
    $contactLink = fn(string $label) => $contact ? ['type' => 'entry', 'value' => $contact->id, 'label' => $label] : ['type' => 'url', 'value' => 'https://arrsys.com/contact-us/', 'label' => $label];

    $banner = pageImage('https://arrsys.com/media/Our-Company.webp',
        'Empowering labels and packaging industry with precision, innovation, excellence: ArrowJet label printers and the Arrow EZCut 330R with printed labels and packaging');
    $material = pageImage(DESIGN . '/assets/materials/web/material-pp-high-gloss-label-stock-1.webp', 'Label printed on White Polyethylene Film');

    // segment card: [title, industry slug | null, link url for no industry]
    $segments = [
        ['Color Label Printing & Digital Finishing', 'label-production-and-in-house-labeling'],
        ['Sign Production', 'signs-and-graphics'],
        ['GHS Certified Label Printing', 'chemical-label-printing'],
        ['Materials & Process Innovations', null],
        ['Military & Government', 'government-and-military'],
        ['Airline Industry', 'aircraft-interior-placards'],
    ];
    $cards = ['sortOrder' => [], 'entries' => []];
    foreach ($segments as $i => [$title, $slug]) {
        if ($slug) {
            $ind = $industry($slug);
            $fields = ['headingText' => $title, 'subheading' => $ind->title, 'mediaImage' => $ind->getFieldValue('featuredImage')->ids(),
                'richText' => '<p>' . htmlspecialchars((string)$ind->cardTagline) . '</p>', 'ctaLink' => ['type' => 'entry', 'value' => $ind->id, 'label' => $ind->title]];
        } else {
            $fields = ['headingText' => $title, 'subheading' => 'Label Materials', 'mediaImage' => [$material->id],
                'richText' => '<p>Die-cut materials on any type of substrate.</p>',
                'ctaLink' => ['type' => 'url', 'value' => 'https://arrsys.com/arrow-materials/', 'label' => 'Label Materials']];
        }
        $cards['sortOrder'][] = "c$i";
        $cards['entries']["c$i"] = ['type' => 'card', 'fields' => $fields];
    }

    $section = Craft::$app->getEntries()->getSectionByHandle('pages');
    $page = new Entry(['sectionId' => $section->id, 'typeId' => Craft::$app->getEntries()->getEntryTypeByHandle('page')->id,
        'title' => 'Our Company', 'slug' => 'our-company']);
    $page->setFieldValues([
        'seoDescription' => $seoDescription,
        'featuredImage' => [$banner->id],
        'cardTagline' => 'Manufacturing and distribution of advanced printing systems and materials for a multitude of industries.',
        'pageBuilder' => ['sortOrder' => ['b1', 'b2', 'b3', 'b4', 'b5'], 'entries' => [
            'b1' => ['type' => 'industryHeader', 'fields' => ['eyebrow' => 'About Arrow Systems', 'sectionHeading' => 'Our [Company]',
                'sectionLead' => $p1, 'mediaImage' => [$banner->id], 'headerStyle' => 'banner',
                'primaryCta' => ['type' => 'url', 'value' => $quoteUrl, 'label' => 'Request a Quote'], 'secondaryCta' => $contactLink('Contact Us')]],
            'b2' => ['type' => 'statsStrip', 'fields' => []],
            'b3' => ['type' => 'cardGrid', 'fields' => ['sectionHeading' => 'Segments We [Serve]', 'cardColumns' => '3', 'cardItems' => $cards]],
            'b4' => ['type' => 'contentBlock', 'fields' => ['eyebrow' => 'Worldwide', 'headingText' => 'Equipment, Materials and Support Around the World',
                'richText' => '<p>' . htmlspecialchars($p2) . '</p>', 'ctaLink' => ['type' => 'url', 'value' => $quoteUrl, 'label' => 'Request a Free Quote']]],
            'b5' => ['type' => 'ctaBand', 'fields' => ['sectionHeading' => 'Over 40 Years of [Experience]', 'sectionLead' => $p3, 'ctaLink' => $contactLink('Contact Us')]],
        ]],
    ]);
    Craft::$app->getElements()->saveElement($page) || fail('page our-company', $page);
    $page->uri === 'our-company' || exit("the page got /{$page->uri}/: another entry uses /our-company/\n");
    $saved = (int)Entry::find()->ownerId($page->id)->fieldId(Craft::$app->getFields()->getFieldByHandle('pageBuilder')->id)->status(null)->count();
    $saved === 5 || exit("our-company: $saved of 5 blocks saved\n");   // a fresh query: the saved element's own value is cached
    say("  + page /{$page->uri}/ (Page Header banner, Stats Strip, Card Grid: 6 segments, Content Block, CTA Band)");
}

// menu items pointing at the live page → this page
foreach (Node::find()->status(null)->all() as $n) {
    if (rtrim((string)$n->url, '/') . '/' !== LIVE_URL) continue;
    $n->type = Entry::class;
    $n->elementId = $page->id;
    $n->url = null;
    Craft::$app->getElements()->saveElement($n) || fail("menu node {$n->id}", $n);
    say("  ~ menu {$n->menu->handle} “{$n->title}” → the page");
}
