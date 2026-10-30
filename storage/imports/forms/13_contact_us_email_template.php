<?php
/**
 * Contact Us: a designed email for the admin, no email to the visitor.
 *   Email template  Formie → Settings → Email Templates → "Admin Notification" (adminNotification) = the Twig file
 *                   templates/_emails/admin-notification.twig (was "Contact Us – Admin"; renamed in 14, every form uses
 *                   it) (logo: web/assets/email/arrow-systems-logo.png, a PNG copy of the site logo: Outlook doesn't show WebP)
 *   Admin Notification   (its template: 15) Subject as live ("New Contact Form Submission"); Content = a short intro (the template
 *                   lists the fields itself, so no {allFields} here). Recipient unchanged (the dummy inbox until launch, D8).
 *   User Notification    switched off (live sends no email to the visitor; switch it on again in the form builder)
 * Idempotent. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/forms/13_contact_us_email_template.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\models\EmailTemplate;

$templates = Formie::$plugin->getEmailTemplates();
$template = $templates->getTemplateByHandle('adminNotification');
if (!$template) {
    $template = new EmailTemplate(['name' => 'Admin Notification', 'handle' => 'adminNotification', 'template' => '_emails/admin-notification']);
    $templates->saveTemplate($template) || fail('email template adminNotification', $template);
    say('  + Formie email template “Admin Notification”');
}

$form = Form::find()->handle('contactUs')->one() ?? exit("no Contact Us form\n");
$intro = json_encode([['type' => 'paragraph', 'content' => [['type' => 'text',
    'text' => 'Someone sent a message through the Contact Us form on the website. Their details are below.']]]]);
foreach (Formie::$plugin->getNotifications()->getFormNotifications($form) as $n) {
    $changes = $n->name === 'Admin Notification'
        ? ['subject' => 'New Contact Form Submission', 'content' => $intro] // the template: 15
        : ($n->name === 'User Notification' ? ['enabled' => false] : []);
    $changes = array_filter($changes, fn($v, $k) => $n->$k != $v, ARRAY_FILTER_USE_BOTH);
    if (!$changes) continue;
    foreach ($changes as $k => $v) $n->$k = $v;
    Formie::$plugin->getNotifications()->saveNotification($n) || fail("notification {$n->name}", $n);
    say("  ~ Contact Us › {$n->name}: " . implode(', ', array_keys($changes)));
}
saveProjectConfig();
