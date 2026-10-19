<?php
/**
 * SUPERSEDED IN PART by 04_generic_builder.php (2026-10-19): the Form block is general (no office panel / map), the
 * Distributors block became the general Card Grid, and the Contact Us page is built from general blocks. Run 04 after this.
 * Pages: form + distributors blocks, reCAPTCHA, form emails (CONTACT_PAGE_PLAN.md; decisions 2026-10-19: D3–D6 as live,
 * D7 reCAPTCHA (keys to come), D8 admin emails to admin_arrowsys@yopmail.com for now).
 *
 *   Form (formBlock), Page Builder → Sections: pick ANY Formie form (Formie → Forms); Formie renders it and handles the
 *     submission (validation, spam checks, Submissions in the CP, email notifications, thank-you message).
 *     Options: heading over the form; "Office details" panel beside it (Office Heading, address / phones / email from
 *     Site Settings, optional Google Maps embed under them), as the live Contact Us page; anchor id (default "contact").
 *   Distributors (distributorsBlock): heading, Google Maps embed (e.g. a My Maps "embed" link), distributor cards
 *     (Distributor: Country, Company, Details as rich text: address, phones, email / website links), in the order given.
 *   reCAPTCHA v2 checkbox (as live): Formie → Settings → Captchas → reCAPTCHA reads RECAPTCHA_ENABLED / RECAPTCHA_SITE_KEY /
 *     RECAPTCHA_SECRET_KEY from .env (off and empty until the client's keys arrive), and is on for the Contact Us form.
 *   Contact Us form: Admin Notification → admin_arrowsys@yopmail.com.
 * Idempotent. Back up first. Run from the project root, then storage/imports/builder/01_common_blocks.php:
 *   /opt/lampp/bin/php storage/imports/pages/02_form_blocks.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';
require dirname(__DIR__) . '/products/_helpers.php';

use craft\fieldlayoutelements\CustomField;
use craft\fields\Lightswitch;
use craft\fields\Matrix;
use craft\fields\PlainText;
use verbb\formie\elements\Form;
use verbb\formie\fields\Forms;
use verbb\formie\Formie;

const ADMIN_EMAIL = 'admin_arrowsys@yopmail.com';
$fields = Craft::$app->getFields();
$f = fn(string $handle, array $opts = []) => new CustomField($fields->getFieldByHandle($handle) ?? throw new Exception("No field $handle"), $opts);

say('Fields');
field(Forms::class, ['name' => 'Form', 'handle' => 'formieForm', 'maxRelations' => 1, 'selectionLabel' => 'Choose a form',
    'instructions' => 'The form to show (create and edit forms in Formie → Forms; its submissions, emails and thank-you message are set there).']);
field(Lightswitch::class, ['name' => 'Office Details', 'handle' => 'showContactPanel', 'default' => true,
    'instructions' => 'On = the red panel beside the form: address, phones and email from Globals → Site Settings.']);
field(PlainText::class, ['name' => 'Map Embed URL', 'handle' => 'mapEmbedUrl',
    'instructions' => 'Optional Google Maps embed link (Google Maps → Share → Embed a map: the src="…" address; or a My Maps embed link).']);

$distributor = blockType('Distributor', 'distributor', 'location-dot', [
    $f('headingText', ['label' => 'Country', 'required' => true, 'instructions' => 'The card title, e.g. “Netherlands”.']),
    $f('subheading', ['label' => 'Company', 'instructions' => 'e.g. “Katun (E.D.C.) B.V.”.']),
    $f('richText', ['label' => 'Details', 'instructions' => 'Address, phones, email, website (one per line; links as needed).']),
], '{headingText} – {subheading}');
field(Matrix::class, ['name' => 'Distributors', 'handle' => 'distributorItems', 'viewMode' => Matrix::VIEW_MODE_CARDS,
    'createButtonLabel' => 'Add distributor', 'instructions' => 'One card each, in this order.'], [$distributor]);

say('Blocks');
blockType('Form', 'formBlock', 'envelope', [
    $f('formieForm', ['required' => true]),
    $f('sectionHeading', ['label' => 'Form Heading', 'instructions' => 'Over the form, e.g. “Contact Us”. Optional.']),
    $f('showContactPanel'),
    $f('headingText', ['label' => 'Office Heading', 'instructions' => 'The panel title, e.g. “Arrow Systems, Inc. USA”. Empty = the site name.']),
    $f('mapEmbedUrl', ['label' => 'Office Map', 'instructions' => 'Optional Google Maps embed link shown under the office details.']),
    $f('anchorId', ['instructions' => 'For links to this form, e.g. “contact” → /page/#contact. Empty = “contact”.']),
], '{sectionHeading}', 'A Formie form (submissions, emails and spam checks by Formie), optionally beside the office details.');
blockType('Distributors', 'distributorsBlock', 'map-location-dot', [
    $f('sectionHeading', ['label' => 'Heading', 'instructions' => 'e.g. “Distributor Location”.']),
    $f('mapEmbedUrl', ['label' => 'Map']),
    $f('distributorItems'),
], '{sectionHeading}', 'A map and the distributor cards (country, company, details).');

// ---- reCAPTCHA v2 checkbox, switched by .env (no keys yet) ----------------------------------------------------------
say('reCAPTCHA');
$env = dirname(__DIR__, 3) . '/.env';
$missing = array_filter(['RECAPTCHA_ENABLED' => 'false', 'RECAPTCHA_SITE_KEY' => '', 'RECAPTCHA_SECRET_KEY' => ''],
    fn($k) => !preg_match('/^' . $k . '=/m', file_get_contents($env)), ARRAY_FILTER_USE_KEY);
if ($missing) {
    $lines = "\n# reCAPTCHA v2 checkbox (Formie): paste the client's keys, then set RECAPTCHA_ENABLED=true\n";
    foreach ($missing as $k => $v) $lines .= "$k=$v\n";
    file_put_contents($env, $lines, FILE_APPEND);
    say('  + .env: ' . implode(', ', array_keys($missing)));
}
$integrations = Formie::$plugin->getIntegrations();
$recaptcha = $integrations->getCaptchaByHandle('recaptcha');
if ($recaptcha->getEnabled(false) !== '$RECAPTCHA_ENABLED' || $recaptcha->siteKey !== '$RECAPTCHA_SITE_KEY') {
    $recaptcha->setEnabled('$RECAPTCHA_ENABLED');
    $recaptcha->siteKey = '$RECAPTCHA_SITE_KEY';
    $recaptcha->secretKey = '$RECAPTCHA_SECRET_KEY';
    $recaptcha->type = 'v2_checkbox';
    $integrations->saveCaptcha($recaptcha) || fail('recaptcha captcha', $recaptcha);
    say('  ~ Formie reCAPTCHA: v2 checkbox, settings from .env');
}

// ---- the Contact Us form: reCAPTCHA on (takes effect with the keys), admin emails ----------------------------------
$form = Form::find()->handle('contactUs')->one() ?? exit("no Formie form contactUs\n");
if (!($form->settings->integrations['recaptcha']['enabled'] ?? false)) {
    $form->setIntegrationSettings('recaptcha', ['enabled' => true]);
    Craft::$app->getElements()->saveElement($form) || fail('form contactUs', $form);
    say('  ~ contactUs: reCAPTCHA on');
}
foreach (Formie::$plugin->getNotifications()->getFormNotifications($form) as $n) {
    if ($n->name !== 'Admin Notification' || $n->to === ADMIN_EMAIL) continue;
    $n->to = ADMIN_EMAIL;
    Formie::$plugin->getNotifications()->saveNotification($n) || fail('notification', $n);
    say('  ~ contactUs: Admin Notification → ' . ADMIN_EMAIL);
}
saveProjectConfig();
