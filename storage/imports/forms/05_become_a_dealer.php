<?php
/**
 * Formie form "Become a Dealer" (becomeADealer), the live form on https://arrsys.com/become-a-dealer/ (read 2026-10-21):
 *   Name* · Email* · Contact No.* · Country* · Existing Business History (several lines, "Brief us in Few Words") ·
 *   Existing Dealership details · Sales and Service History · Reason For Becoming a Dealer* · button "Send"
 * Made as a copy of Contact Us (same settings: labels above, honeypot, reCAPTCHA switched by .env, thank-you message,
 * notifications), then its fields reshaped, two columns:
 *   Name | Email · Contact No. (country picker, checked per country by modules/forms) | Country
 *   Existing Business History · Existing Dealership details | Sales and Service History · Reason For Becoming a Dealer
 * Admin Notification → admin_arrowsys@yopmail.com (as Contact Us, D8). Idempotent. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/forms/05_become_a_dealer.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use verbb\formie\elements\Form;
use verbb\formie\fields;
use verbb\formie\Formie;

const ADMIN_EMAIL = 'admin_arrowsys@yopmail.com';
const LABELS = ['Name*', 'Email*', 'Contact No.*', 'Country*', 'Existing Business History', 'Existing Dealership details', 'Sales and Service History', 'Reason For Becoming a Dealer*'];

$form = Form::find()->handle('becomeADealer')->one();
if (!$form) {
    $source = Form::find()->handle('contactUs')->one() ?? exit("no Formie form contactUs\n");
    // Formie's own duplicate (the CP's "Duplicate"): fresh fields, rows and notifications; a plain duplicateElement() shares the fields
    $form = Craft::$app->getElements()->duplicateElement($source, array_merge($source->getDuplicateAttributes(), ['title' => 'Become a Dealer', 'handle' => 'becomeADealer']));
    say("  + form becomeADealer (copy of contactUs, id {$form->id})");
}
// the copy must own its fields (deleting one below must not touch Contact Us)
$sourceIds = array_map(fn($f) => $f->id, Form::find()->handle('contactUs')->one()->getFormLayout()->getCustomFields());
$ownIds = array_map(fn($f) => $f->id, $form->getFormLayout()->getCustomFields());
!array_intersect($sourceIds, $ownIds) || exit("becomeADealer shares fields with contactUs: stop\n");

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
        [$field('phoneNumber', fields\Phone::class, ['label' => 'Contact No.', 'required' => true]),
         $field('country', fields\SingleLineText::class, ['label' => 'Country', 'placeholder' => 'Country', 'required' => true])],
        [$field('existingBusinessHistory', fields\MultiLineText::class, ['label' => 'Existing Business History', 'placeholder' => 'Brief us in Few Words'])],
        [$field('existingDealershipDetails', fields\SingleLineText::class, ['label' => 'Existing Dealership details']),
         $field('salesAndServiceHistory', fields\SingleLineText::class, ['label' => 'Sales and Service History'])],
        [$field('reasonForBecomingADealer', fields\SingleLineText::class, ['label' => 'Reason For Becoming a Dealer', 'required' => true])],
    ];
    $page = $form->getFormLayout()->getPages()[0];
    $page->setRows(array_map(fn($fields) => ['fields' => $fields], $rows));
    $form->setIntegrationSettings('honeypot', ['enabled' => true]);
    $form->setIntegrationSettings('recaptcha', ['enabled' => true]);
    Craft::$app->getElements()->saveElement($form) || fail('form becomeADealer', $form);

    // Formie only deletes what its builder lists as deleted: remove the copied fields live doesn't have (Message, How Did You Hear About Us) + empty rows
    $keep = array_map(fn($f) => $f->handle, array_merge(...$rows));
    $fieldsService = Formie::$plugin->getFields();
    foreach (Form::find()->handle('becomeADealer')->one()->getFormLayout()->getPages() as $p) {
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
    say('  ~ becomeADealer: fields as live');
}

$form = Form::find()->handle('becomeADealer')->one();
foreach (Formie::$plugin->getNotifications()->getFormNotifications($form) as $n) {
    if ($n->name !== 'Admin Notification' || $n->to === ADMIN_EMAIL) continue;
    $n->to = ADMIN_EMAIL;
    Formie::$plugin->getNotifications()->saveNotification($n) || fail('notification', $n);
    say('  ~ Admin Notification → ' . ADMIN_EMAIL);
}

$labels($form) === LABELS || exit('becomeADealer fields: ' . implode(' · ', $labels($form)) . "\n");
say('  = becomeADealer: ' . implode(' · ', LABELS) . ' · notifications: ' . count(Formie::$plugin->getNotifications()->getFormNotifications($form)));
