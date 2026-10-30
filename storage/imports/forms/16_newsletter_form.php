<?php
/**
 * Formie form "Newsletter" (newsletter): the subscribers of the newsletter band at the end of every page
 * (_partials/newsletter.twig + site.js post its one email address here; client request 2026-10-29: "implement the
 * subscribe to newsletter functionality"). One field, Email (email): required, Unique Value on, so an address signs up
 * once ("already subscribed" on the site). Subscribers: Formie → Submissions → Newsletter (export: CSV).
 * Copy of Contact Us (its settings) with the honeypot on and reCAPTCHA off (the band has no reCAPTCHA widget) and no
 * notifications (Contact Us's would thank them for contacting us). Idempotent. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/forms/16_newsletter_form.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use verbb\formie\elements\Form;
use verbb\formie\fields;
use verbb\formie\Formie;

$form = Form::find()->handle('newsletter')->one();
if (!$form) {
    $source = Form::find()->handle('contactUs')->one() ?? exit("no Formie form contactUs\n");
    // Formie's own duplicate (the CP's "Duplicate"): fresh fields, rows and notifications
    $form = Craft::$app->getElements()->duplicateElement($source, array_merge($source->getDuplicateAttributes(), ['title' => 'Newsletter', 'handle' => 'newsletter']));
    say("  + form newsletter (copy of contactUs, id {$form->id})");
}
$sourceIds = array_map(fn($f) => $f->id, Form::find()->handle('contactUs')->one()->getFormLayout()->getCustomFields());
!array_intersect($sourceIds, array_map(fn($f) => $f->id, $form->getFormLayout()->getCustomFields())) || exit("newsletter shares fields with contactUs: stop\n");

$handles = fn(Form $f) => array_map(fn($x) => $x->handle, $f->getFormLayout()->getCustomFields());
if ($handles($form) !== ['email']) {
    $email = new fields\Email(['handle' => 'email', 'label' => 'Email', 'placeholder' => 'Your email', 'required' => true, 'uniqueValue' => true]);
    $page = $form->getFormLayout()->getPages()[0];
    $page->setRows([['fields' => [$email]]]);
    $page->getPageSettings()->submitButtonLabel = 'Subscribe Now';
    $form->setIntegrationSettings('honeypot', ['enabled' => true]);
    $form->setIntegrationSettings('recaptcha', ['enabled' => false]);
    Craft::$app->getElements()->saveElement($form) || fail('form newsletter', $form);

    // Formie deletes only what its builder lists as deleted: remove the copied Contact Us fields + empty rows
    $fieldsService = Formie::$plugin->getFields();
    foreach (Form::find()->handle('newsletter')->one()->getFormLayout()->getPages() as $p) {
        foreach ($p->getRows() as $row) {
            $left = 0;
            foreach ($row->getFields() as $f) {
                if ($f->handle === 'email') { $left++; continue; }
                $fieldsService->deleteFieldById($f->id);
            }
            if (!$left && $row->id) $fieldsService->deleteRowById($row->id);
        }
    }
    say('  ~ newsletter: one field, Email (required, unique)');
}

$form = Form::find()->handle('newsletter')->one();
foreach (Formie::$plugin->getNotifications()->getFormNotifications($form) as $n) {
    Formie::$plugin->getNotifications()->deleteNotificationById($n->id);
    say("  - notification {$n->name}");
}

$f = $form->getFormLayout()->getCustomFields();
($handles($form) === ['email'] && $f[0]->required && $f[0]->uniqueValue) || exit('newsletter fields: ' . implode(', ', $handles($form)) . "\n");
$captchas = (array)($form->settings->integrations ?? []);
say('  = newsletter: Email (required, unique) · honeypot ' . json_encode($captchas['honeypot']['enabled'] ?? null) . ' · reCAPTCHA ' . json_encode($captchas['recaptcha']['enabled'] ?? null)
    . ' · notifications ' . count(Formie::$plugin->getNotifications()->getFormNotifications($form)));
