<?php
/**
 * Formie form "Customer Service" (customerService), the live form on https://arrsys.com/customer-service/ (read
 * 2026-10-21): Name*, Email*, Phone No.*, Message*, button "Send". Made as a copy of Contact Us (same settings: labels
 * above, honeypot, reCAPTCHA switched by .env, thank-you message, notifications), then its fields reshaped:
 *   Name | Email · Phone No. (the Phone field with the country picker, checked per country by modules/forms) · Message
 * Admin Notification → admin_arrowsys@yopmail.com (as Contact Us, D8). Idempotent. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/forms/02_customer_service.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use verbb\formie\elements\Form;
use verbb\formie\Formie;

const ADMIN_EMAIL = 'admin_arrowsys@yopmail.com';
$keep = ['yourName', 'emailAddress', 'phoneNumber', 'message'];

$form = Form::find()->handle('customerService')->one();
if (!$form) {
    $source = Form::find()->handle('contactUs')->one() ?? exit("no Formie form contactUs\n");
    // Formie's own duplicate (the CP's "Duplicate"): fresh fields, rows and notifications; a plain duplicateElement() shares the fields
    $form = Craft::$app->getElements()->duplicateElement($source, array_merge($source->getDuplicateAttributes(), ['title' => 'Customer Service', 'handle' => 'customerService']));
    say("  + form customerService (copy of contactUs, id {$form->id})");
}
// the copy must own its fields (deleting one below must not touch Contact Us)
$sourceIds = array_map(fn($f) => $f->id, Form::find()->handle('contactUs')->one()->getFormLayout()->getCustomFields());
$ownIds = array_map(fn($f) => $f->id, $form->getFormLayout()->getCustomFields());
!array_intersect($sourceIds, $ownIds) || exit("customerService shares fields with contactUs: stop\n");

// fields: delete those live doesn't have (+ empty rows), relabel the phone
$fieldsService = Formie::$plugin->getFields();
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
$form = Form::find()->handle('customerService')->one();
$phone = current(array_filter($form->getFormLayout()->getCustomFields(), fn($f) => $f->handle === 'phoneNumber'));
if ($phone && $phone->label !== 'Phone No.') {
    $phone->label = 'Phone No.';
    $form->setIntegrationSettings('honeypot', ['enabled' => true]);
    $form->setIntegrationSettings('recaptcha', ['enabled' => true]);
    Craft::$app->getElements()->saveElement($form) || fail('form customerService', $form);
    say('  ~ Phone: label "Phone No." (as live)');
}

foreach (Formie::$plugin->getNotifications()->getFormNotifications($form) as $n) {
    if ($n->name !== 'Admin Notification' || $n->to === ADMIN_EMAIL) continue;
    $n->to = ADMIN_EMAIL;
    Formie::$plugin->getNotifications()->saveNotification($n) || fail('notification', $n);
    say('  ~ Admin Notification → ' . ADMIN_EMAIL);
}

$form = Form::find()->handle('customerService')->one();
$labels = array_map(fn($f) => $f->label . ($f->required ? '*' : ''), $form->getFormLayout()->getCustomFields());
$labels === ['Name*', 'Email*', 'Phone No.*', 'Message*'] || exit('customerService fields: ' . implode(' · ', $labels) . "\n");
say('  = customerService: ' . implode(' · ', $labels) . ' · notifications: ' . count(Formie::$plugin->getNotifications()->getFormNotifications($form)));
