<?php
/**
 * Pages: /support/, from the live page (https://arrsys.com/support/, read 2026-10-21; no design file). Live: a dark
 * abstract background behind the H1, 2 intro paragraphs, "Contact Support" (a table: toll-free, direct, email,
 * headquarters) + the local support partners line, and "Planning to Add Capacity?". Its text says "send a support
 * request using the form below", but live has no form: here the Form block (the Contact Us Formie form) fills that in.
 * Text word for word; general blocks only:
 *   Page Header   Full-width banner, the Support banner image (inner-banner-support-new.jpg, see below), whole:
 *                 "Support for Your Arrow Systems [Equipment]", the 2 paragraphs; buttons Call 1-888-697-2749 (tel:) and
 *                 Send a Support Request (#support-form)
 *   Columns       maroon panel: Contact Support (toll-free, direct, email, headquarters) + the support partners line |
 *                 white card: Form "Send a Support Request" (anchor support-form)
 *   CTA Band      "Planning to Add [Capacity]?" + the live text, Request a Quote
 *   SEO           the live title and meta description
 * Also: the Page Header buttons (primaryCta / secondaryCta) accept phone and email links, for any page.
 * Then the menu items linking to the live page (top bar Support, Main Menu and Footer "Request Support") link here.
 * Skips the page when it exists. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/pages/08_support.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\elements\Entry;
use verbb\formie\elements\Form;
use verbb\navigation\elements\Node;

const LIVE_URL = 'https://arrsys.com/support/';
$fields = Craft::$app->getFields();

// ---- Page Header buttons: + phone / email links ------------------------------------------------------------------------
foreach (['primaryCta', 'secondaryCta'] as $handle) {
    $f = $fields->getFieldByHandle($handle);
    if (in_array('tel', $f->types, true)) continue;
    $f->types = array_values(array_unique(array_merge($f->types, ['tel', 'email'])));
    $fields->saveField($f) || fail("field $handle", $f);
    say("  ~ $handle: + phone and email links");
}
saveProjectConfig();

// ---- the page --------------------------------------------------------------------------------------------------------
$intro = "Arrow Systems supports the digital label printers, label finishers and flexible packaging printers we manufacture and sell. Our support team helps with troubleshooting, site readiness before delivery, and keeping your press running at production quality. To reach us, call toll-free at 1-888-697-2749, or send a support request using the form below.\n\n"
    . 'With over 40 years in the packaging, printing and coating industries, we know that a down press means missed ship dates. Our job is to get you back to printing quickly.';
$contact = '<h2>Contact Support</h2>'
    . '<p><strong>Toll-free</strong><br><a href="tel:+18886972749">1-888-697-2749</a></p>'
    . '<p><strong>Direct</strong><br><a href="tel:+17162852974">+1-716-285-2974</a></p>'
    . '<p><strong>Email</strong><br><a href="mailto:sales@arrsys.com">sales@arrsys.com</a></p>'
    . '<p><strong>Headquarters</strong><br>2440 Jerauld Ave., Niagara Falls, NY 14305, USA</p>'
    . '<p>Arrow supports customers in North America, Europe, South America, Asia and Africa through local support partners.</p>';
$capacity = 'If you’re adding a second press, moving finishing in-house, or expanding into flexible packaging, our team can size the right configuration for your volumes. Request a quote or contact our team.';

$page = Entry::find()->section('pages')->slug('support')->status(null)->one();
if (!$page) {
    $form = Form::find()->handle('contactUs')->one() ?? exit("no Formie form contactUs\n");
    $quote = Craft::$app->getGlobals()->getSetByHandle('siteSettings')->getFieldValue('quoteLink');
    $section = Craft::$app->getEntries()->getSectionByHandle('pages');
    $page = new Entry(['sectionId' => $section->id, 'typeId' => Craft::$app->getEntries()->getEntryTypeByHandle('page')->id,
        'title' => 'Support', 'slug' => 'support']);
    $page->setFieldValues([
        'seoTitle' => 'Label Press & Finisher Support | Arrow Systems, Inc.',
        'seoDescription' => 'Support for your Arrow Systems label press, finisher or flexible packaging printer: technical help, site readiness and label materials. Call 1-888-697-2749.',
        'cardTagline' => 'Troubleshooting, site readiness and keeping your press running at production quality.',
        'pageBuilder' => ['sortOrder' => ['b1', 'b2', 'b3'], 'entries' => [
            'b1' => ['type' => 'industryHeader', 'fields' => ['eyebrow' => 'Support', 'sectionHeading' => 'Support for Your Arrow Systems [Equipment]',
                'sectionLead' => $intro,
                'primaryCta' => ['type' => 'tel', 'value' => '+18886972749', 'label' => 'Call 1-888-697-2749'],
                'secondaryCta' => ['type' => 'url', 'value' => '#support-form', 'label' => 'Send a Support Request']]],
            'b2' => ['type' => 'columnsBlock', 'fields' => ['columnsLayout' => '2-3', 'leftStyle' => 'panel', 'rightStyle' => 'card', 'columnsAlign' => 'top',
                'leftColumn' => ['sortOrder' => ['n1'], 'entries' => ['n1' => ['type' => 'richText', 'fields' => ['richText' => $contact]]]],
                'rightColumn' => ['sortOrder' => ['n1'], 'entries' => ['n1' => ['type' => 'formBlock', 'fields' => [
                    'formieForm' => [$form->id], 'sectionHeading' => 'Send a Support Request', 'anchorId' => 'support-form']]]],
            ]],
            'b3' => ['type' => 'ctaBand', 'fields' => ['sectionHeading' => 'Planning to Add [Capacity]?', 'sectionLead' => $capacity,
                'ctaLink' => ['type' => 'url', 'value' => $quote?->url ?: 'https://arrsys.com/request-quote/', 'label' => 'Request a Quote']]],
        ]],
    ]);
    Craft::$app->getElements()->saveElement($page) || fail('page support', $page);
    $page->uri === 'support' || exit("the page got /{$page->uri}/: another entry uses /support/\n");
    $saved = (int)Entry::find()->ownerId($page->id)->fieldId($fields->getFieldByHandle('pageBuilder')->id)->status(null)->count();
    $saved === 3 || exit("support: $saved of 3 blocks saved\n");
    say("  + page /{$page->uri}/ (Page Header, Columns: contact panel + support form, CTA Band)");
}

// the Support banner (inner-banner-support-new.jpg: "24/7 Service, working with you around the clock" + the ASI logo; in
// the live media library, id 82; the current live page no longer shows it, its own background is a plain dark texture) on
// the Page Header, as a Full-width banner, the whole image. Replaces the texture (v1016-b-08-scaled.jpg) used at first.
$header = $page->getFieldValue('pageBuilder')->type('industryHeader')->status(null)->one();
$current = $header ? $header->getFieldValue('mediaImage')->one() : null;
if ($header && (!$current || $current->filename === 'v1016-b-08-scaled.jpg')) {
    $volume = Craft::$app->getVolumes()->getVolumeByHandle('images');
    $folder = Craft::$app->getAssets()->ensureFolderByFullPathAndVolume('pages', $volume, false);
    $image = craft\elements\Asset::find()->folderId($folder->id)->filename('inner-banner-support-new.jpg')->one();
    if (!$image) {
        $bytes = @file_get_contents('https://arrsys.com/media/inner-banner-support-new.jpg', false, stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0 (Arrow Systems staging migration)\r\n", 'timeout' => 90]]))
            ?: exit("could not download the support banner\n");
        $tmp = Craft::$app->getPath()->getTempPath() . '/' . uniqid('page') . '-inner-banner-support-new.jpg';
        file_put_contents($tmp, $bytes);
        $image = new craft\elements\Asset(['tempFilePath' => $tmp, 'filename' => 'inner-banner-support-new.jpg', 'volumeId' => $volume->id, 'newFolderId' => $folder->id]);
        $image->alt = '24/7 service, working with you around the clock: Arrow Systems, Inc.';
        $image->setScenario(craft\elements\Asset::SCENARIO_CREATE);
        Craft::$app->getElements()->saveElement($image) || fail('support banner', $image);
    }
    $header->setFieldValue('mediaImage', [$image->id]);
    $header->setFieldValue('headerStyle', 'banner');
    $header->setFieldValue('bannerHeight', 'whole');
    Craft::$app->getElements()->saveElement($header) || fail('support header', $header);
    say('  ~ /support/: Page Header image = inner-banner-support-new.jpg (Full-width banner, whole image)');
    if ($current && $current->filename === 'v1016-b-08-scaled.jpg' && !craft\elements\Entry::find()->relatedTo($current)->status(null)->exists()) {
        Craft::$app->getElements()->deleteElement($current, true);   // the texture, imported by mistake and no longer used
        say('  - asset v1016-b-08-scaled.jpg (unused)');
    }
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
