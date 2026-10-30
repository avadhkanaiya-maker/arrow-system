<?php
/**
 * The designed admin email (13: Contact Us) for every other form too.
 *   Email template  "Contact Us – Admin" renamed to "Admin Notification" (adminNotification,
 *                   templates/_emails/admin-notification.twig) by the project config YAML; nothing in it is form-specific
 *   Admin Notification   of each form below (its template: 15): Subject per form; Content = a short intro (the template lists the
 *                   fields itself, so no {allFields} here). Recipient, Reply-To and the User Notifications unchanged.
 *   Careers › Upload Your CV   email link = the control panel URL (the CV volume has no public URLs, so the default
 *                   link was empty); the CV is still attached to the email.
 * Idempotent. Back up first. Apply the project config first (craft up). Run from the project root:
 *   /opt/lampp/bin/php storage/imports/forms/14_admin_email_all_forms.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use verbb\formie\Formie;
use verbb\formie\elements\Form;

$subjects = [
    'customerService' => 'New Customer Service Request',
    'requestQuote' => 'New Quote Request',
    'becomeADealer' => 'New Dealer Application',
    'productRequestQuote' => 'New Product Request',
    'requestQuoteFromBlog' => 'New Quote Request from the Blog',
    'freeSampleRequest' => 'New Free Sample Request',
    'careers' => 'New Job Application',
];
foreach ($subjects as $handle => $subject) {
    $form = Form::find()->handle($handle)->one() ?? exit("no form $handle\n");
    $intro = json_encode([['type' => 'paragraph', 'content' => [['type' => 'text',
        'text' => "Someone filled in the {$form->title} form on the website. Their details are below."]]]]);
    foreach (Formie::$plugin->getNotifications()->getFormNotifications($form) as $n) {
        if ($n->name !== 'Admin Notification') continue;
        $changes = ['subject' => $subject, 'content' => $intro]; // the template: 15
        $changes = array_filter($changes, fn($v, $k) => $n->$k != $v, ARRAY_FILTER_USE_BOTH);
        if (!$changes) continue;
        foreach ($changes as $k => $v) $n->$k = $v;
        Formie::$plugin->getNotifications()->saveNotification($n) || fail("$handle notification {$n->name}", $n);
        say("  ~ {$form->title} › {$n->name}: " . implode(', ', array_keys($changes)));
    }
}

$cv = Form::find()->handle('careers')->one()->getFieldByHandle('cv');
if ($cv->emailValue !== 'cpUrl') {
    $cv->emailValue = 'cpUrl';
    Formie::$plugin->getFields()->saveField($cv) || fail('careers field cv', $cv);
    say('  ~ Careers › Upload Your CV: email link = control panel URL');
}
