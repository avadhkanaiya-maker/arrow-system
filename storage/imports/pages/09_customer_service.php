<?php
/**
 * Pages: /customer-service/, from the live page (https://arrsys.com/customer-service/, read 2026-10-21; no design file):
 * the H1 "We Provide 24 X 7 Customer Service", a second heading "Fill Up Our Customer Service form", one sentence, the
 * form (Name, Email, Phone No., Message) over the page's photo (people-working-call-center-1.jpg). Live text word for
 * word; general blocks only:
 *   Page Header  Image beside text: the H1, the sentence as the intro, the live photo; button → the form
 *   Form         "Fill Up Our Customer Service form": the Formie form Customer Service (forms/02_customer_service.php)
 *   SEO          as live (title = the default format, the live description)
 * Then the menu items linking to the live page link here. Skips the page when it exists. Back up first.
 * Run from the project root, after forms/02_customer_service.php: /opt/lampp/bin/php storage/imports/pages/09_customer_service.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\elements\Asset;
use craft\elements\Entry;
use verbb\formie\elements\Form;
use verbb\navigation\elements\Node;

const LIVE_URL = 'https://arrsys.com/customer-service/';
$intro = 'We Support you 24 X 7. Please fill up our customer service form and wait for our response. We usually get back to you within 24hrs of working days.';

$page = Entry::find()->section('pages')->slug('customer-service')->status(null)->one();
if (!$page) {
    $form = Form::find()->handle('customerService')->one() ?? exit("no Formie form customerService: run forms/02_customer_service.php\n");
    $volume = Craft::$app->getVolumes()->getVolumeByHandle('images');
    $folder = Craft::$app->getAssets()->ensureFolderByFullPathAndVolume('pages', $volume, false);
    $photo = Asset::find()->folderId($folder->id)->filename('people-working-call-center-1.jpg')->one();
    if (!$photo) {
        $bytes = @file_get_contents('https://arrsys.com/media/people-working-call-center-1.jpg', false, stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0 (Arrow Systems staging migration)\r\n", 'timeout' => 90]]))
            ?: exit("could not download the photo\n");
        $tmp = Craft::$app->getPath()->getTempPath() . '/' . uniqid('page') . '-people-working-call-center-1.jpg';
        file_put_contents($tmp, $bytes);
        $photo = new Asset(['tempFilePath' => $tmp, 'filename' => 'people-working-call-center-1.jpg', 'volumeId' => $volume->id, 'newFolderId' => $folder->id]);
        $photo->alt = 'Customer service team with headsets working at laptops';
        $photo->setScenario(Asset::SCENARIO_CREATE);
        Craft::$app->getElements()->saveElement($photo) || fail('photo', $photo);
    }

    $page = new Entry(['sectionId' => Craft::$app->getEntries()->getSectionByHandle('pages')->id,
        'typeId' => Craft::$app->getEntries()->getEntryTypeByHandle('page')->id, 'title' => 'Customer Service', 'slug' => 'customer-service']);
    $page->setFieldValues([
        'seoDescription' => $intro,
        'featuredImage' => [$photo->id],
        'cardTagline' => 'We support you 24 X 7: fill up the customer service form and we get back to you within 24 hours of working days.',
        'pageBuilder' => ['sortOrder' => ['b1', 'b2'], 'entries' => [
            'b1' => ['type' => 'industryHeader', 'fields' => ['eyebrow' => 'Customer Service', 'sectionHeading' => 'We Provide 24 X 7 Customer [Service]',
                'sectionLead' => $intro, 'mediaImage' => [$photo->id], 'headerStyle' => 'split',
                'primaryCta' => ['type' => 'url', 'value' => '#customer-service-form', 'label' => 'Fill Up the Form']]],
            'b2' => ['type' => 'formBlock', 'fields' => ['formieForm' => [$form->id], 'sectionHeading' => 'Fill Up Our Customer Service form',
                'anchorId' => 'customer-service-form', 'sectionBackground' => 'surface']],
        ]],
    ]);
    Craft::$app->getElements()->saveElement($page) || fail('page customer-service', $page);
    $page->uri === 'customer-service' || exit("the page got /{$page->uri}/: another entry uses /customer-service/\n");
    $saved = (int)Entry::find()->ownerId($page->id)->fieldId(Craft::$app->getFields()->getFieldByHandle('pageBuilder')->id)->status(null)->count();
    $saved === 2 || exit("customer-service: $saved of 2 blocks saved\n");
    say("  + page /{$page->uri}/ (Page Header with the live photo, Form: Customer Service)");
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
