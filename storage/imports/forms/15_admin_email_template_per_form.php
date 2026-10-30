<?php
/**
 * Each form gets its own admin email template, so one form's email can change later without the others (user, 2026-10-29):
 *   Formie → Settings → Email Templates → "<Form> – Admin" (<formHandle>Admin) = templates/_emails/admin/<form-handle>.twig,
 *   which only extends the shared design _emails/admin-notification (the email looks as before). The form's Admin
 *   Notification uses it. "Admin Notification" (13) stays registered for a new form.
 * Idempotent. Back up first. Run from the project root after 13 and 14:
 *   /opt/lampp/bin/php storage/imports/forms/15_admin_email_template_per_form.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\helpers\StringHelper;
use craft\web\View;
use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\models\EmailTemplate;

$templates = Formie::$plugin->getEmailTemplates();
foreach (Form::find()->all() as $form) {
    $path = '_emails/admin/' . StringHelper::toKebabCase($form->handle);
    Craft::$app->getView()->doesTemplateExist($path, View::TEMPLATE_MODE_SITE) || exit("no file templates/$path.twig for the {$form->title} form\n");
    $handle = $form->handle . 'Admin';
    $template = $templates->getTemplateByHandle($handle);
    if (!$template) {
        $template = new EmailTemplate(['name' => "{$form->title} – Admin", 'handle' => $handle, 'template' => $path]);
        $templates->saveTemplate($template) || fail("email template $handle", $template);
        say("  + Formie email template “{$template->name}” = $path");
    }
    foreach (Formie::$plugin->getNotifications()->getFormNotifications($form) as $n) {
        if ($n->name !== 'Admin Notification' || $n->templateId == $template->id) continue;
        $n->templateId = $template->id;
        Formie::$plugin->getNotifications()->saveNotification($n) || fail("{$form->handle} notification {$n->name}", $n);
        say("  ~ {$form->title} › Admin Notification: template “{$template->name}”");
    }
}
saveProjectConfig();
