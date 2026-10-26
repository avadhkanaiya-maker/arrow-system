<?php
/**
 * Formie form "Product Request Quote" (productRequestQuote): the one form on every product page (review 2026-10-22:
 * "one form with a hidden field instead of copies"). Live embeds a copy of an Elementor form of that name on 98 product
 * pages (98 form IDs): Name*, Email*, Contact Number*, Country/Region* (placeholders, no visible labels), button
 * "Request A Sample"; a submission knew its product only by the page it came from. Here one form:
 *   Name · Email · Contact Number (Phone, country picker) · Country/Region, labels hidden on screen (read by screen
 *   readers), as live · Product (hidden: the product's name, filled by products/_entry.twig) · Product Page (hidden: the
 *   page address, Formie fills it) · button "Request a Free Sample" (asked 2026-10-22) · Live Submission ID, Live Form,
 *   User Agent (hidden, filled only by the import of live's submissions) · Product (entry): the product as an entry, for
 *   the Submissions filter (not on the page; modules/forms sets it on save) · submission titles "Product · date"
 * Made as a copy of Contact Us (honeypot, reCAPTCHA by .env, thank-you message, notifications); Admin Notification →
 * admin_arrowsys@yopmail.com. Idempotent. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/forms/06_product_request_quote.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use verbb\formie\elements\Form;
use verbb\formie\fields;
use verbb\formie\Formie;
use verbb\formie\positions\Hidden as HiddenLabel;

const ADMIN_EMAIL = 'admin_arrowsys@yopmail.com';
const LABELS = ['Name*', 'Email*', 'Contact Number*', 'Country/Region*', 'Product', 'Product Page', 'Live Submission ID', 'Live Form', 'User Agent', 'Product (entry)'];

$form = Form::find()->handle('productRequestQuote')->one();
if (!$form) {
    $source = Form::find()->handle('contactUs')->one() ?? exit("no Formie form contactUs\n");
    // Formie's own duplicate (the CP's "Duplicate"): fresh fields, rows and notifications; a plain duplicateElement() shares the fields
    $form = Craft::$app->getElements()->duplicateElement($source, array_merge($source->getDuplicateAttributes(), ['title' => 'Product Request Quote', 'handle' => 'productRequestQuote']));
    say("  + form productRequestQuote (copy of contactUs, id {$form->id})");
}
// the copy must own its fields (deleting one below must not touch Contact Us)
$sourceIds = array_map(fn($f) => $f->id, Form::find()->handle('contactUs')->one()->getFormLayout()->getCustomFields());
$ownIds = array_map(fn($f) => $f->id, $form->getFormLayout()->getCustomFields());
!array_intersect($sourceIds, $ownIds) || exit("productRequestQuote shares fields with contactUs: stop\n");

$labels = fn(Form $f) => array_map(fn($x) => $x->label . ($x->required ? '*' : ''), $f->getFormLayout()->getCustomFields());
if ($labels($form) !== LABELS) {
    $existing = [];
    foreach ($form->getFormLayout()->getCustomFields() as $f) $existing[$f->handle] = $f;
    $field = function (string $handle, string $class, array $props) use ($existing) {
        $f = $existing[$handle] ?? new $class(['handle' => $handle]);
        foreach ($props + ['required' => false, 'placeholder' => null, 'labelPosition' => null, 'instructions' => null] as $k => $v) if ($f->canSetProperty($k)) $f->$k = $v;   // a Hidden field has no placeholder
        return $f;
    };
    $hidden = ['labelPosition' => HiddenLabel::class, 'required' => true];   // placeholders only, as live
    $rows = [   // one field per row: the form sits in the narrow column under the product photo
        [$field('yourName', fields\Name::class, ['label' => 'Name', 'placeholder' => 'Name'] + $hidden)],
        [$field('emailAddress', fields\Email::class, ['label' => 'Email', 'placeholder' => 'Email'] + $hidden)],
        [$field('phoneNumber', fields\Phone::class, ['label' => 'Contact Number', 'placeholder' => 'Contact Number'] + $hidden)],
        [$field('country', fields\SingleLineText::class, ['label' => 'Country/Region', 'placeholder' => 'Country/Region'] + $hidden)],
        [$field('product', fields\Hidden::class, ['label' => 'Product', 'defaultOption' => 'custom']),
         $field('productPage', fields\Hidden::class, ['label' => 'Product Page', 'defaultOption' => 'currentUrl'])],
        // filled only by the import of live's submissions (07_product_request_quote_submissions.php): live's own record
        [$field('liveSubmissionId', fields\Hidden::class, ['label' => 'Live Submission ID', 'defaultOption' => 'custom']),
         $field('liveForm', fields\Hidden::class, ['label' => 'Live Form', 'defaultOption' => 'custom']),
         $field('userAgent', fields\Hidden::class, ['label' => 'User Agent', 'defaultOption' => 'custom'])],
        // the product as a Craft entry, for the Submissions filter (Filter → Product (entry)); not on the page (disabled):
        // modules/forms sets it on save from the Product Page address
        [$field('productEntry', fields\Entries::class, ['label' => 'Product (entry)', 'visibility' => 'disabled',
            'sources' => ['section:' . Craft::$app->getEntries()->getSectionByHandle('products')->uid]])],
    ];
    $page = $form->getFormLayout()->getPages()[0];
    $page->setRows(array_map(fn($fields) => ['fields' => $fields], $rows));
    $page->getPageSettings()->submitButtonLabel = 'Request a Free Sample';
    $form->setIntegrationSettings('honeypot', ['enabled' => true]);
    $form->setIntegrationSettings('recaptcha', ['enabled' => true]);
    Craft::$app->getElements()->saveElement($form) || fail('form productRequestQuote', $form);

    // Formie only deletes what its builder lists as deleted: remove the copied fields live doesn't have (Message, How Did You Hear About Us) + empty rows
    $keep = array_map(fn($f) => $f->handle, array_merge(...$rows));
    $fieldsService = Formie::$plugin->getFields();
    foreach (Form::find()->handle('productRequestQuote')->one()->getFormLayout()->getPages() as $p) {
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
    say('  ~ productRequestQuote: fields as live');
}

$form = Form::find()->handle('productRequestQuote')->one();
if ($form->settings->submissionTitleFormat !== '{field:product} · {timestamp}') {   // the product in the Submissions list
    $form->settings->submissionTitleFormat = '{field:product} · {timestamp}';
    Craft::$app->getElements()->saveElement($form) || fail('form productRequestQuote', $form);
    say('  ~ submission titles: Product · date');
}
foreach (Formie::$plugin->getNotifications()->getFormNotifications($form) as $n) {
    if ($n->name !== 'Admin Notification' || $n->to === ADMIN_EMAIL) continue;
    $n->to = ADMIN_EMAIL;
    Formie::$plugin->getNotifications()->saveNotification($n) || fail('notification', $n);
    say('  ~ Admin Notification → ' . ADMIN_EMAIL);
}

$labels($form) === LABELS || exit('productRequestQuote fields: ' . implode(' · ', $labels($form)) . "\n");
say('  = productRequestQuote: ' . implode(' · ', LABELS) . ' · notifications: ' . count(Formie::$plugin->getNotifications()->getFormNotifications($form)));
