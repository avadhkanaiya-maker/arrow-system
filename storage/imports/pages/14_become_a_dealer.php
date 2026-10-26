<?php
/**
 * Pages: /become-a-dealer/, from the live page (https://arrsys.com/become-a-dealer/, read 2026-10-21; no design file).
 * Live: the H1 "Join Us To Dominate The Fast-Moving Label and Packaging Industries", the heading "Fill Up The Form, We
 * Will Get Back To You" and the form; the page's photo is the one uploaded with it (media library: a handshake, alt
 * "Illustration-of-become-a-dealer"). Live has no menu link to it (only search finds it); here About → Become a Dealer
 * and Footer → Company → Become a Dealer. Live text word for word; general blocks only:
 *   Page Header  Image beside text: the H1 ([Industries] in red), the photo; button → the form
 *   Form         "Fill Up The Form, We Will Get Back To You": the Formie form Become a Dealer (forms/05_become_a_dealer.php)
 *   SEO          the live title and description
 * Then the menu items linking to the live page link here. Skips the page when it exists. Back up first.
 * Run from the project root, after forms/05_become_a_dealer.php: /opt/lampp/bin/php storage/imports/pages/14_become_a_dealer.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\elements\Asset;
use craft\elements\Entry;
use verbb\formie\elements\Form;
use verbb\navigation\elements\Node;

const LIVE_URL = 'https://arrsys.com/become-a-dealer/';
const PHOTO = 'two-confident-business-man-shaking-hands-during-meeting-office-success-dealing-greeting-partner-concept.jpg';

$page = Entry::find()->section('pages')->slug('become-a-dealer')->status(null)->one();
if (!$page) {
    $form = Form::find()->handle('becomeADealer')->one() ?? exit("no Formie form becomeADealer: run forms/05_become_a_dealer.php\n");
    $volume = Craft::$app->getVolumes()->getVolumeByHandle('images');
    $folder = Craft::$app->getAssets()->ensureFolderByFullPathAndVolume('pages', $volume, false);
    $photo = Asset::find()->folderId($folder->id)->filename(PHOTO)->one();
    if (!$photo) {
        $bytes = @file_get_contents('https://arrsys.com/media/' . PHOTO, false, stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0 (Arrow Systems staging migration)\r\n", 'timeout' => 90]]))
            ?: exit("could not download the photo\n");
        $tmp = Craft::$app->getPath()->getTempPath() . '/' . uniqid('page') . '-' . PHOTO;
        file_put_contents($tmp, $bytes);
        $photo = new Asset(['tempFilePath' => $tmp, 'filename' => PHOTO, 'volumeId' => $volume->id, 'newFolderId' => $folder->id]);
        $photo->alt = 'Two business partners shaking hands over a meeting table';
        $photo->setScenario(Asset::SCENARIO_CREATE);
        Craft::$app->getElements()->saveElement($photo) || fail('photo', $photo);
    }

    $page = new Entry(['sectionId' => Craft::$app->getEntries()->getSectionByHandle('pages')->id,
        'typeId' => Craft::$app->getEntries()->getEntryTypeByHandle('page')->id, 'title' => 'Become a Dealer', 'slug' => 'become-a-dealer']);
    $page->setFieldValues([
        'seoTitle' => 'Become a dealer - Arrow Systems, Inc.',
        'seoDescription' => 'Join Us To Dominate The Fast-Moving Label and Packaging Industries Fill Up The Form, We Will Get Back To You',
        'featuredImage' => [$photo->id],
        'cardTagline' => 'Join us to dominate the fast-moving label and packaging industries.',
        'pageBuilder' => ['sortOrder' => ['b1', 'b2'], 'entries' => [
            'b1' => ['type' => 'industryHeader', 'fields' => ['eyebrow' => 'Become a Dealer', 'sectionHeading' => 'Join Us To Dominate The Fast-Moving Label and Packaging [Industries]',
                'mediaImage' => [$photo->id], 'headerStyle' => 'split',
                'primaryCta' => ['type' => 'url', 'value' => '#dealer-form', 'label' => 'Fill Up the Form']]],
            'b2' => ['type' => 'formBlock', 'fields' => ['formieForm' => [$form->id], 'sectionHeading' => 'Fill Up The Form, We Will Get Back To You',
                'anchorId' => 'dealer-form', 'sectionBackground' => 'surface']],
        ]],
    ]);
    Craft::$app->getElements()->saveElement($page) || fail('page become-a-dealer', $page);
    $page->uri === 'become-a-dealer' || exit("the page got /{$page->uri}/: another entry uses /become-a-dealer/\n");
    $saved = (int)Entry::find()->ownerId($page->id)->fieldId(Craft::$app->getFields()->getFieldByHandle('pageBuilder')->id)->status(null)->count();
    $saved === 2 || exit("become-a-dealer: $saved of 2 blocks saved\n");
    say("  + page /{$page->uri}/ (Page Header with the live photo, Form: Become a Dealer)");
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
