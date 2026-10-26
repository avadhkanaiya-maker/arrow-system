<?php
/**
 * Two Formie forms from live (read 2026-10-22), made like the others as copies of Contact Us (labels above, honeypot,
 * reCAPTCHA switched by .env, thank-you message, Admin Notification → admin_arrowsys@yopmail.com):
 *   Free Sample Request (freeSampleRequest), https://arrsys.com/try-a-free-sample/: Sample type* (the live select's
 *     options, as the design's cards) · Full name* · Email* · Phone · Country / region* · Product or material of interest
 *     · What would you like to test? · in the design's three numbered steps, its privacy note, button "Request My Sample"
 *   Careers (careers), https://arrsys.com/careers/: Name* · Email* · Contact* · Upload Your CV* · button "Send"
 * CVs are personal data: they go to the volume "Career CVs", stored in storage/career-cvs (outside web/, no public
 * address; open them in the control panel). The labels are live's, so 04_live_submissions.php imports the exports.
 * Idempotent. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/forms/12_free_sample_and_careers.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\fs\Local;
use craft\models\Volume;
use verbb\formie\elements\Form;
use verbb\formie\fields;
use verbb\formie\Formie;

const ADMIN_EMAIL = 'admin_arrowsys@yopmail.com';

// the CV volume: files outside the web root, no URLs
$volumes = Craft::$app->getVolumes();
if (!$cvs = $volumes->getVolumeByHandle('careerCvs')) {
    $fsService = Craft::$app->getFs();
    $fs = $fsService->getFilesystemByHandle('careerCvs') ?? $fsService->createFilesystem(['type' => Local::class, 'name' => 'Career CVs',
        'handle' => 'careerCvs', 'hasUrls' => false, 'settings' => ['path' => '@storage/career-cvs']]);
    $fs->id || $fsService->saveFilesystem($fs) || fail('filesystem careerCvs', $fs);
    $cvs = new Volume(['name' => 'Career CVs', 'handle' => 'careerCvs', 'fsHandle' => 'careerCvs']);
    $volumes->saveVolume($cvs) || fail('volume careerCvs', $cvs);
    saveProjectConfig();
    say('  + volume Career CVs (storage/career-cvs, no public URLs)');
}

/** Creates or reshapes one form; $rows($field) returns one array of fields per row */
function makeForm(string $handle, string $title, array $labels, string $button, callable $rows): void {
    $form = Form::find()->handle($handle)->one();
    if (!$form) {
        $source = Form::find()->handle('contactUs')->one() ?? exit("no Formie form contactUs\n");
        // Formie's own duplicate (the CP's "Duplicate"): fresh fields, rows and notifications
        $form = Craft::$app->getElements()->duplicateElement($source, array_merge($source->getDuplicateAttributes(), ['title' => $title, 'handle' => $handle]));
        say("  + form $handle (copy of contactUs, id {$form->id})");
    }
    // the copy must own its fields (deleting one below must not touch Contact Us)
    $sourceIds = array_map(fn($f) => $f->id, Form::find()->handle('contactUs')->one()->getFormLayout()->getCustomFields());
    !array_intersect($sourceIds, array_map(fn($f) => $f->id, $form->getFormLayout()->getCustomFields())) || exit("$handle shares fields with contactUs: stop\n");

    $labelsOf = fn(Form $f) => array_map(fn($x) => $x->label . ($x->required ? '*' : ''), $f->getFormLayout()->getCustomFields());
    if ($labelsOf($form) !== $labels) {
        $existing = [];
        foreach ($form->getFormLayout()->getCustomFields() as $f) $existing[$f->handle] = $f;
        $field = function (string $handle, string $class, array $props) use ($existing) {
            $f = $existing[$handle] ?? new $class(['handle' => $handle]);
            foreach ($props + ['required' => false, 'placeholder' => null, 'labelPosition' => null, 'instructions' => null] as $k => $v) if ($f->canSetProperty($k)) $f->$k = $v;
            return $f;
        };
        $rows = $rows($field);
        $page = $form->getFormLayout()->getPages()[0];
        $page->setRows(array_map(fn($fields) => ['fields' => $fields], $rows));
        $page->getPageSettings()->submitButtonLabel = $button;
        $form->setIntegrationSettings('honeypot', ['enabled' => true]);
        $form->setIntegrationSettings('recaptcha', ['enabled' => true]);
        Craft::$app->getElements()->saveElement($form) || fail("form $handle", $form);

        // Formie only deletes what its builder lists as deleted: remove the copied fields live doesn't have + empty rows
        $keep = array_map(fn($f) => $f->handle, array_merge(...$rows));
        $fieldsService = Formie::$plugin->getFields();
        foreach (Form::find()->handle($handle)->one()->getFormLayout()->getPages() as $p) {
            foreach ($p->getRows() as $row) {
                $left = 0;
                foreach ($row->getFields() as $f) {
                    if (in_array($f->handle, $keep, true)) { $left++; continue; }
                    $fieldsService->deleteFieldById($f->id);
                    say("  - field {$f->handle}");
                }
                if (!$left && $row->id) $fieldsService->deleteRowById($row->id);
            }
        }
        say("  ~ $handle: fields as live");
    }

    $form = Form::find()->handle($handle)->one();
    foreach (Formie::$plugin->getNotifications()->getFormNotifications($form) as $n) {
        if ($n->name !== 'Admin Notification' || $n->to === ADMIN_EMAIL) continue;
        $n->to = ADMIN_EMAIL;
        Formie::$plugin->getNotifications()->saveNotification($n) || fail('notification', $n);
        say('  ~ Admin Notification → ' . ADMIN_EMAIL);
    }
    $labelsOf($form) === $labels || exit("$handle fields: " . implode(' · ', $labelsOf($form)) . "\n");
    say("  = $handle: " . implode(' · ', $labels) . " · button “{$button}”");
}

// the design (Arrowsystem/try-a-free-sample): three numbered steps (Heading fields), sample types as cards (option label
// "**Title** description": craft.css shows the bold part as the card's title), the privacy note beside the button
$privacy = Craft::$app->getGlobals()->getSetByHandle('newsletter')?->noteLink?->url ?: 'https://arrsys.com/privacy-policy/';
makeForm('freeSampleRequest', 'Free Sample Request',
    ['What would you like to try?', 'Sample type*', 'Your details', 'Full name*', 'Email*', 'Phone', 'Country / region*', 'Your project',
     'Product or material of interest', 'What would you like to test?', 'Privacy note'],
    'Request My Sample', fn($field) => [
        [$field('stepSample', fields\Heading::class, ['label' => 'What would you like to try?', 'headingSize' => 'h3'])],
        [$field('sampleType', fields\Radio::class, ['label' => 'Sample type', 'required' => true, 'layout' => 'horizontal',
            'labelPosition' => verbb\formie\positions\Hidden::class, 'emailValue' => 'value',
            'options' => array_map(fn($o) => ['label' => "**{$o[0]}** {$o[1]}", 'value' => $o[0], 'isDefault' => false], [
                ['Printed label', 'A finished label printed on our equipment'], ['Label material', 'Facestock and adhesive samples'],
                ['Lamination film', 'Protective film for labels'], ['Flexible packaging', 'Printed pouch and film samples'],
                ['Not sure yet', 'Our team will help you choose']])])],
        [$field('stepDetails', fields\Heading::class, ['label' => 'Your details', 'headingSize' => 'h3'])],
        [$field('yourName', fields\Name::class, ['label' => 'Full name', 'required' => true]),
         $field('emailAddress', fields\Email::class, ['label' => 'Email', 'required' => true])],
        [$field('phoneNumber', fields\Phone::class, ['label' => 'Phone']),
         $field('country', fields\SingleLineText::class, ['label' => 'Country / region', 'required' => true])],
        [$field('stepProject', fields\Heading::class, ['label' => 'Your project', 'headingSize' => 'h3'])],
        [$field('productOfInterest', fields\SingleLineText::class, ['label' => 'Product or material of interest', 'instructions' => 'Leave blank if you are not sure.', 'instructionsPosition' => verbb\formie\positions\BelowInput::class])],
        [$field('message', fields\MultiLineText::class, ['label' => 'What would you like to test?', 'placeholder' => 'Tell us about the application, size, surface, or finish.'])],
        [$field('privacyNote', fields\Html::class, ['label' => 'Privacy note', 'labelPosition' => verbb\formie\positions\Hidden::class,
            'htmlContent' => "<p>By sending this form you agree that Arrow Systems may contact you about your sample. See our <a href=\"$privacy\">Privacy Policy</a>.</p>"])],
    ]);

makeForm('careers', 'Careers', ['Name*', 'Email*', 'Contact*', 'Upload Your CV*'], 'Send', fn($field) => [
    [$field('yourName', fields\Name::class, ['label' => 'Name', 'placeholder' => 'Name', 'required' => true]),
     $field('emailAddress', fields\Email::class, ['label' => 'Email', 'placeholder' => 'Email', 'required' => true])],
    [$field('phoneNumber', fields\Phone::class, ['label' => 'Contact', 'required' => true])],
    // live's CVs: pdf, doc/docx, jpeg
    [$field('cv', fields\FileUpload::class, ['label' => 'Upload Your CV', 'required' => true, 'limitFiles' => '1', 'sizeLimit' => '10',
        'restrictFiles' => true, 'allowedKinds' => ['pdf', 'word', 'image'], 'uploadLocationSource' => 'volume:' . $cvs->uid])],
]);
