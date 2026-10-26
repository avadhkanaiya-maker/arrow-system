<?php
/**
 * Pages: /try-a-free-sample/ and /careers/ with their Formie forms (forms/12_free_sample_and_careers.php). General
 * blocks only; skips a page that exists. Back up first.
 *   /try-a-free-sample/ (design: Arrowsystem/try-a-free-sample; text as live, read 2026-10-22)
 *     (its blocks are now the design's: 22_free_sample_design.php, run after this script)
 *     Page Header  "Request a Free [Sample]" + live's line
 *     Columns 3-2  card: Free Sample Request form (anchor sample-form) | panel: Prefer to talk? (Site Settings contact
 *                  details) + Looking for something else? (Request a Quote, Order Supplies, Request Support)
 *     Then every link field pointing at the live page (Site Settings, Button blocks) links here, label kept; links typed
 *     inside rich text are left as they are (as 13_request_quote.php).
 *   /careers/ (live https://arrsys.com/careers/, no design file; no menu links to it on live, none added)
 *     Page Header  "Looking For A Career Change? Join Arrow [System]!"
 *     Columns 1-1  card: Careers form (anchor careers-form) | live's illustration
 *     Rich Text    Position Open For: R&D Engineer (Mechanical Engineer), word for word; resume to shaan@arrsys.com
 * Run from the project root: /opt/lampp/bin/php storage/imports/pages/21_free_sample_and_careers.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\db\Query;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\fields\Link;
use verbb\formie\elements\Form;

$sections = Craft::$app->getEntries();
$newPage = function (string $title, string $slug, array $fields) use ($sections): Entry {
    $page = new Entry(['sectionId' => $sections->getSectionByHandle('pages')->id, 'typeId' => $sections->getEntryTypeByHandle('page')->id, 'title' => $title, 'slug' => $slug]);
    $page->setFieldValues($fields);
    Craft::$app->getElements()->saveElement($page) || fail("page $slug", $page);
    $page->uri === $slug || exit("the page got /{$page->uri}/: another entry uses /$slug/\n");
    $want = count($fields['pageBuilder']['sortOrder']);
    $saved = (int)Entry::find()->ownerId($page->id)->fieldId(Craft::$app->getFields()->getFieldByHandle('pageBuilder')->id)->status(null)->count();
    $saved === $want || exit("$slug: $saved of $want blocks saved\n");
    return $page;
};

// ---- /try-a-free-sample/
$sample = Entry::find()->section('pages')->slug('try-a-free-sample')->status(null)->one();
if (!$sample) {
    $form = Form::find()->handle('freeSampleRequest')->one() ?? exit("no Formie form freeSampleRequest: run forms/12_free_sample_and_careers.php\n");
    $s = Craft::$app->getGlobals()->getSetByHandle('siteSettings');
    $tel = fn(string $p) => '+' . ltrim(preg_replace('/\D/', '', str_starts_with($p, '+') ? $p : '1' . $p), '+');
    $uri = fn(string $slug) => Entry::find()->slug($slug)->status(null)->one()?->url ?? "https://arrsys.com/$slug/";
    $aside = '<p>Prefer to talk?</p><h2>Talk to a Specialist</h2><p>Tell us your application, materials and run lengths.</p>'
        . "<p><strong>Toll-free</strong><br><a href=\"tel:{$tel($s->tollFreePhone)}\">{$s->tollFreePhone}</a></p>"
        . "<p><strong>Direct</strong><br><a href=\"tel:{$tel($s->directPhone)}\">{$s->directPhone}</a></p>"
        . "<p><strong>Email</strong><br><a href=\"mailto:{$s->salesEmail}\">{$s->salesEmail}</a></p>"
        . "<p><strong>Headquarters</strong><br>{$s->address}</p>"
        . '<h3>Looking for something else?</h3><ul>'
        . "<li><a href=\"{$uri('request-quote')}\"><strong>Request a Quote</strong></a>: pricing for equipment and materials</li>"
        . "<li><a href=\"{$uri('arrow-materials')}\"><strong>Order Supplies</strong></a>: label materials and consumables</li>"
        . "<li><a href=\"{$uri('support')}\"><strong>Request Support</strong></a>: help with equipment you already own</li></ul>";
    $sample = $newPage('Try a Free Sample', 'try-a-free-sample', [
        'seoTitle' => 'Request a Free Label & Packaging Sample | Arrow Systems',
        'seoDescription' => 'Explore printed labels, label materials, lamination films, and flexible packaging from Arrow Systems. Request a free sample tailored to your project.',
        'cardTagline' => 'Tell us what you would like to try and we confirm the right sample for your project.',
        'pageBuilder' => ['sortOrder' => ['b1', 'b2'], 'entries' => [
            'b1' => ['type' => 'industryHeader', 'fields' => ['eyebrow' => 'Free sample', 'sectionHeading' => 'Request a Free [Sample]',
                'sectionLead' => 'Tell us what you would like to try. Our team can follow up to confirm the right sample for your project.']],
            'b2' => ['type' => 'columnsBlock', 'fields' => ['columnsLayout' => '3-2', 'leftStyle' => 'card', 'rightStyle' => 'panel', 'columnsAlign' => 'top', 'spacingTop' => 'small',
                'leftColumn' => ['sortOrder' => ['n1'], 'entries' => ['n1' => ['type' => 'formBlock', 'fields' => ['formieForm' => [$form->id], 'anchorId' => 'sample-form']]]],
                'rightColumn' => ['sortOrder' => ['n1'], 'entries' => ['n1' => ['type' => 'richText', 'fields' => ['richText' => $aside]]]],
            ]],
        ]],
    ]);
    say("  + page /{$sample->uri}/ (Page Header, Columns: Free Sample Request form + contact panel)");
}

// link fields (Site Settings, Button blocks) pointing at the live page → this page, label kept
$ids = (new Query())->select('es.elementId')->from(['es' => '{{%elements_sites}}'])
    ->innerJoin(['e' => '{{%elements}}'], '[[e.id]] = [[es.elementId]]')
    ->where(['e.revisionId' => null, 'e.draftId' => null, 'e.dateDeleted' => null])
    ->andWhere(['like', 'es.content', 'arrsys.com/try-a-free-sample'])->column();
foreach ($ids as $id) {
    $el = Craft::$app->getElements()->getElementById($id, null, null, ['status' => null]);
    if (!$el || !$el->getFieldLayout()) continue;
    $changed = [];
    foreach ($el->getFieldLayout()->getCustomFields() as $f) {
        if (!$f instanceof Link) continue;
        $v = $el->getFieldValue($f->handle);
        if (!$v || !preg_match('#^https://arrsys\.com/try-a-free-sample/?$#', (string)$v->url)) continue;
        $el->setFieldValue($f->handle, ['type' => 'entry', 'value' => $sample->id, 'label' => $v->label, 'target' => $v->target]);
        $changed[] = $f->handle;
    }
    if (!$changed) continue;
    Craft::$app->getElements()->saveElement($el) || fail("element $id", $el);
    say('  ~ ' . ($el instanceof Entry ? "{$el->type->handle} $id" : $el::displayName()) . ': ' . implode(', ', $changed) . ' → the page');
}

// ---- /careers/
if (!Entry::find()->section('pages')->slug('careers')->status(null)->exists()) {
    $form = Form::find()->handle('careers')->one() ?? exit("no Formie form careers: run forms/12_free_sample_and_careers.php\n");
    $volume = Craft::$app->getVolumes()->getVolumeByHandle('images');
    $folder = Craft::$app->getAssets()->ensureFolderByFullPathAndVolume('pages', $volume, false);
    $file = '17876784-Converted-01-min.png';
    $photo = Asset::find()->folderId($folder->id)->filename($file)->one();
    if (!$photo) {
        $bytes = @file_get_contents("https://arrsys.com/media/$file", false, stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0 (Arrow Systems staging migration)\r\n", 'timeout' => 90]]))
            ?: exit("could not download $file\n");
        $tmp = Craft::$app->getPath()->getTempPath() . '/' . uniqid('page') . "-$file";
        file_put_contents($tmp, $bytes);
        $photo = new Asset(['tempFilePath' => $tmp, 'filename' => $file, 'volumeId' => $volume->id, 'newFolderId' => $folder->id]);
        $photo->alt = 'Career-page-demonstration';   // live's alt
        $photo->setScenario(Asset::SCENARIO_CREATE);
        Craft::$app->getElements()->saveElement($photo) || fail('photo', $photo);
    }
    $job = '<h2>Position Open For</h2><h3>R&amp;D Engineer (Mechanical Engineer)</h3>'
        . '<p>The <strong>R&amp;D Engineer</strong> <strong>(Mechanical Engineer)</strong> is responsible for designing advanced printing systems and materials for specific customer applications as well as developing new processes and equipment for those applications. The R&amp;D Engineer is involved in the full life cycle of our products, working throughout the conceptual stage, the design process, the testing phase and then through its final production. The R&amp;D Engineer will also assist customers with equipment installation and train customers on the proper use and maintenance of that equipment.</p>'
        . '<p><strong>More specifically, duties include:</strong></p><ul>'
        . '<li>Designing and modifying equipment for specific customer-based applications.</li>'
        . '<li>Selecting manufacturing methods and operation of product design for customized printing and cutting equipment.</li>'
        . '<li>Overseeing the installation and commissioning of new equipment, to ensure that machines are functioning according to engineering specifications for optimal operational performance.</li>'
        . '<li>Analyzing customer design requirements, specifications, manuals, and other data to evaluate the feasibility of the design relating to cost and manufacturing capabilities.</li>'
        . '<li>Coordinating with manufacturing engineers on new automation projects, providing technical information and resolving system malfunctions to ensure seamless integration with existing equipment.</li>'
        . '<li>Investigating equipment failures or difficulties, including electrical system and mechanical failures and creating training and service manuals.</li>'
        . '<li>Installing equipment purchased by customers and training new business customers while providing technical customer service on issues with equipment.</li>'
        . '<li>Analyzing and reviewing manufacturing methods and cost analysis systems to assess feasibility, sustainability, potential new applications, or necessity of modification.</li>'
        . '<li>Researching, testing and recommending design modifications in response to machine malfunctions.</li></ul>'
        . '<p>Requires <strong>BS degree</strong> and relevant education or experience.</p>'
        . '<p><strong>Travel: 15% domestic</strong></p>'
        . '<p><strong>Send resume to:</strong> <a href="mailto:shaan@arrsys.com">shaan@arrsys.com</a></p>';
    $careers = $newPage('Careers', 'careers', [
        'seoTitle' => 'Careers - Arrow Systems, Inc.',
        'seoDescription' => 'The R&D Engineer (Mechanical Engineer) is responsible for designing advanced printing systems and materials for specific customer applications as well as',
        'featuredImage' => [$photo->id],
        'cardTagline' => 'Looking for a career change? Join Arrow Systems.',
        'pageBuilder' => ['sortOrder' => ['b1', 'b2', 'b3'], 'entries' => [
            'b1' => ['type' => 'industryHeader', 'fields' => ['eyebrow' => 'Careers', 'sectionHeading' => 'Looking For A Career Change? Join Arrow [System]!']],
            'b2' => ['type' => 'columnsBlock', 'fields' => ['columnsLayout' => '1-1', 'leftStyle' => 'card', 'rightStyle' => 'plain', 'columnsAlign' => 'center', 'spacingTop' => 'small',
                'leftColumn' => ['sortOrder' => ['n1'], 'entries' => ['n1' => ['type' => 'formBlock', 'fields' => ['formieForm' => [$form->id], 'anchorId' => 'careers-form']]]],
                'rightColumn' => ['sortOrder' => ['n1'], 'entries' => ['n1' => ['type' => 'image', 'fields' => ['mediaImage' => [$photo->id]]]]],
            ]],
            'b3' => ['type' => 'richText', 'fields' => ['richText' => $job]],
        ]],
    ]);
    say("  + page /{$careers->uri}/ (Page Header, Columns: Careers form + illustration, the open position)");
}

// self-check: both pages answer with their form
foreach (['try-a-free-sample' => 'freeSampleRequest', 'careers' => 'careers'] as $slug => $handle) {
    $html = @file_get_contents("http://localhost:8081/$slug/") ?: exit("/$slug/ does not load\n");
    str_contains($html, "data-fui-form") && str_contains($html, "fui-$handle") || str_contains($html, "\"formHandle\":\"$handle\"") || exit("/$slug/: form $handle not on the page\n");
}
say('  = /try-a-free-sample/ and /careers/ show their forms');
