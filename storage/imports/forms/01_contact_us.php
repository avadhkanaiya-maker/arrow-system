<?php
/**
 * Formie form "Contact Us" (contactUs, created in the CP) made the same as the live form on https://arrsys.com/contact-us/
 * (checked 2026-10-15). Live fields, all required, labels above:
 *   Name (placeholder "Name") · Email ("Email") · Number (a number input) · Country ("Enter your country")
 *   Message ("Message") · How Did You Hear About Us · reCAPTCHA v2 checkbox + a hidden honeypot field · button "Send"
 * Here: the same fields (Name as one input, not first / last), button "Send", Formie's Honeypot captcha on (live's
 * hidden field), and (asked 2026-10-15) two columns: Name | Email, Number | Country, then Message, How Did You Hear
 * About Us; "Number" is a Phone field with a country (dial code) picker, United States by default, validated per country. reCAPTCHA needs the site's own keys
 * (live's key only works on arrsys.com): Formie → Settings → Captchas → reCAPTCHA, then enable it on this form.
 * The form's notifications (admin, and the visitor's copy to {field:emailAddress}) are kept.
 * Idempotent. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/forms/01_contact_us.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use verbb\formie\elements\Form;
use verbb\formie\fields;
use verbb\formie\Formie;

$form = Form::find()->handle('contactUs')->one() ?? fail('form contactUs', new craft\base\Model());
$layout = $form->getFormLayout();
$page = $layout->getPages()[0];
$existing = [];
foreach ($layout->getCustomFields() as $f) $existing[$f->handle] = $f;

/** The existing field with this handle (kept: same id), else a new one of $class; then the live settings */
$field = function (string $handle, string $class, array $props) use ($existing) {
    $f = isset($existing[$handle]) && $existing[$handle] instanceof $class ? $existing[$handle] : new $class(['handle' => $handle]);
    foreach ($props + ['required' => true, 'labelPosition' => null, 'instructions' => null] as $k => $v) $f->$k = $v;   // live: every label shown above its field, no hints
    return $f;
};
$rows = [   // one array per row: two fields = two columns
    [$field('yourName', fields\Name::class, ['label' => 'Name', 'placeholder' => 'Name', 'useMultipleFields' => false]),
     $field('emailAddress', fields\Email::class, ['label' => 'Email', 'placeholder' => 'Email'])],
    [$field('phoneNumber', fields\Phone::class, ['label' => 'Number', 'placeholder' => null, 'countryEnabled' => true, 'countryDefaultValue' => 'US']),
     $field('country', fields\SingleLineText::class, ['label' => 'Country', 'placeholder' => 'Enter your country'])],
    [$field('message', fields\MultiLineText::class, ['label' => 'Message', 'placeholder' => 'Message'])],
    [$field('howDidYouHearAboutUs', fields\SingleLineText::class, ['label' => 'How Did You Hear About Us', 'placeholder' => null])],
];
$page->setRows(array_map(fn($fields) => ['fields' => $fields], $rows));
$page->getPageSettings()->submitButtonLabel = 'Send';

// Honeypot (live: a hidden text field): on for the site, then on this form
$integrations = Formie::$plugin->getIntegrations();
$honeypot = $integrations->getCaptchaByHandle('honeypot');
if (!$honeypot->getEnabled(false)) {
    $honeypot->setEnabled(true);
    $integrations->saveCaptcha($honeypot) || fail('honeypot captcha', $honeypot);
    saveProjectConfig();   // plugin settings live in project config: standalone scripts must write it
    say('  ~ Formie: Honeypot captcha enabled');
}
$form->setIntegrationSettings('honeypot', ['enabled' => true]);

Craft::$app->getElements()->saveElement($form) || fail('form contactUs', $form);

// Formie only deletes what its builder lists as deleted: remove fields not in the live list (the old Phone) + empty rows
$fieldsService = Formie::$plugin->getFields();
$keep = array_map(fn($f) => $f->handle, array_merge(...$rows));
$form = Form::find()->handle('contactUs')->one();
foreach ($form->getFormLayout()->getPages() as $p) {
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
$form = Form::find()->handle('contactUs')->one();
say('  ~ contactUs: ' . implode(' · ', array_map(fn($f) => $f->label . ($f->required ? '*' : ''), $form->getFormLayout()->getCustomFields())));
