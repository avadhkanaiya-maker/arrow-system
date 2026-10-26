<?php
/**
 * Formie form "Request a Quote" (requestQuote), the live form on https://arrsys.com/request-quote/ (read 2026-10-21):
 *   Name* · Email* · Phone No.* · Company · How Did You Hear About Us · Product Required* · Message* · button "Send"
 * Made as a copy of Contact Us (same settings: labels above, honeypot, reCAPTCHA switched by .env, thank-you message,
 * notifications), then its fields reshaped, two columns:
 *   Name | Email · Phone No. (country picker, checked per country by modules/forms) | Company
 *   How Did You Hear About Us | Product Required · Message
 * Admin Notification → admin_arrowsys@yopmail.com (as Contact Us, D8). Idempotent. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/forms/03_request_quote.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use verbb\formie\elements\Form;
use verbb\formie\fields;
use verbb\formie\Formie;

const ADMIN_EMAIL = 'admin_arrowsys@yopmail.com';
const LABELS = ['Name*', 'Email*', 'Phone No.*', 'Company', 'How Did You Hear About Us', 'Product Required*', 'Message*'];

$form = Form::find()->handle('requestQuote')->one();
if (!$form) {
    $source = Form::find()->handle('contactUs')->one() ?? exit("no Formie form contactUs\n");
    // Formie's own duplicate (the CP's "Duplicate"): fresh fields, rows and notifications; a plain duplicateElement() shares the fields
    $form = Craft::$app->getElements()->duplicateElement($source, array_merge($source->getDuplicateAttributes(), ['title' => 'Request a Quote', 'handle' => 'requestQuote']));
    say("  + form requestQuote (copy of contactUs, id {$form->id})");
}
// the copy must own its fields (deleting one below must not touch Contact Us)
$sourceIds = array_map(fn($f) => $f->id, Form::find()->handle('contactUs')->one()->getFormLayout()->getCustomFields());
$ownIds = array_map(fn($f) => $f->id, $form->getFormLayout()->getCustomFields());
!array_intersect($sourceIds, $ownIds) || exit("requestQuote shares fields with contactUs: stop\n");

$labels = fn(Form $f) => array_map(fn($x) => $x->label . ($x->required ? '*' : ''), $f->getFormLayout()->getCustomFields());
if ($labels($form) !== LABELS) {
    $existing = [];
    foreach ($form->getFormLayout()->getCustomFields() as $f) $existing[$f->handle] = $f;
    $field = function (string $handle, string $class, array $props) use ($existing) {
        $f = $existing[$handle] ?? new $class(['handle' => $handle]);
        foreach ($props + ['required' => false, 'placeholder' => null, 'labelPosition' => null, 'instructions' => null] as $k => $v) $f->$k = $v;
        return $f;
    };
    $rows = [   // one array per row: two fields = two columns
        [$field('yourName', fields\Name::class, ['label' => 'Name', 'placeholder' => 'Name', 'required' => true]),
         $field('emailAddress', fields\Email::class, ['label' => 'Email', 'placeholder' => 'Email', 'required' => true])],
        [$field('phoneNumber', fields\Phone::class, ['label' => 'Phone No.', 'required' => true]),
         $field('company', fields\SingleLineText::class, ['label' => 'Company'])],
        [$field('howDidYouHearAboutUs', fields\SingleLineText::class, ['label' => 'How Did You Hear About Us']),
         $field('productRequired', fields\SingleLineText::class, ['label' => 'Product Required', 'required' => true])],
        [$field('message', fields\MultiLineText::class, ['label' => 'Message', 'placeholder' => 'Message', 'required' => true])],
    ];
    $page = $form->getFormLayout()->getPages()[0];
    $page->setRows(array_map(fn($fields) => ['fields' => $fields], $rows));
    $form->setIntegrationSettings('honeypot', ['enabled' => true]);
    $form->setIntegrationSettings('recaptcha', ['enabled' => true]);
    Craft::$app->getElements()->saveElement($form) || fail('form requestQuote', $form);

    // Formie only deletes what its builder lists as deleted: remove the copied fields live doesn't have (Country) + empty rows
    $keep = array_map(fn($f) => $f->handle, array_merge(...$rows));
    $fieldsService = Formie::$plugin->getFields();
    foreach (Form::find()->handle('requestQuote')->one()->getFormLayout()->getPages() as $p) {
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
    say('  ~ requestQuote: fields as live');
}

$form = Form::find()->handle('requestQuote')->one();
foreach (Formie::$plugin->getNotifications()->getFormNotifications($form) as $n) {
    if ($n->name !== 'Admin Notification' || $n->to === ADMIN_EMAIL) continue;
    $n->to = ADMIN_EMAIL;
    Formie::$plugin->getNotifications()->saveNotification($n) || fail('notification', $n);
    say('  ~ Admin Notification → ' . ADMIN_EMAIL);
}

$labels($form) === LABELS || exit('requestQuote fields: ' . implode(' · ', $labels($form)) . "\n");
say('  = requestQuote: ' . implode(' · ', LABELS) . ' · notifications: ' . count(Formie::$plugin->getNotifications()->getFormNotifications($form)));
