<?php
/**
 * Formie form "Request Quote From Blog" (requestQuoteFromBlog): the form at the end of every blog post, as live (review
 * 2026-10-22: "put the actual form in each post instead of the CTA, same as live"). Live: an Elementor form of that name
 * in 244 of 354 posts (one copy per post), heading "Contact With Our Team Now", labels shown:
 *   Name* · Email* · Contact No.* ("Place your contact no. with country code") · Message ("Place your requirement or a
 *   query") · button "Send"
 * Here one form: those fields (Contact No. = the Phone field with a country picker), + hidden Blog Post (the post's
 * title, filled by blog/_entry.twig), Blog Post Page (the address, Formie fills it), Live Submission ID, Live Form,
 * User Agent (filled only by the import of live's submissions, 09_blog_quote_submissions.php).
 * Also the Blog Page's "Default CTA" tab becomes "Article Form": the form to show (this one), its heading and an optional
 * intro; the red CTA box only when no form is chosen. Copy of Contact Us (honeypot, reCAPTCHA by .env, notifications;
 * admin → admin_arrowsys@yopmail.com). Idempotent. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/forms/08_blog_quote_form.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\elements\Entry;
use verbb\formie\elements\Form;
use verbb\formie\fields;
use verbb\formie\Formie;

const ADMIN_EMAIL = 'admin_arrowsys@yopmail.com';
const LABELS = ['Name*', 'Email*', 'Contact No.*', 'Message', 'Blog Post', 'Blog Post Page', 'Live Submission ID', 'Live Form', 'User Agent'];

$form = Form::find()->handle('requestQuoteFromBlog')->one();
if (!$form) {
    $source = Form::find()->handle('contactUs')->one() ?? exit("no Formie form contactUs\n");
    // Formie's own duplicate (the CP's "Duplicate"): fresh fields, rows and notifications; a plain duplicateElement() shares the fields
    $form = Craft::$app->getElements()->duplicateElement($source, array_merge($source->getDuplicateAttributes(), ['title' => 'Request Quote From Blog', 'handle' => 'requestQuoteFromBlog']));
    say("  + form requestQuoteFromBlog (copy of contactUs, id {$form->id})");
}
// the copy must own its fields (deleting one below must not touch Contact Us)
$sourceIds = array_map(fn($f) => $f->id, Form::find()->handle('contactUs')->one()->getFormLayout()->getCustomFields());
$ownIds = array_map(fn($f) => $f->id, $form->getFormLayout()->getCustomFields());
!array_intersect($sourceIds, $ownIds) || exit("requestQuoteFromBlog shares fields with contactUs: stop\n");

$labels = fn(Form $f) => array_map(fn($x) => $x->label . ($x->required ? '*' : ''), $f->getFormLayout()->getCustomFields());
if ($labels($form) !== LABELS) {
    $existing = [];
    foreach ($form->getFormLayout()->getCustomFields() as $f) $existing[$f->handle] = $f;
    $field = function (string $handle, string $class, array $props) use ($existing) {
        $f = $existing[$handle] ?? new $class(['handle' => $handle]);
        foreach ($props + ['required' => false, 'placeholder' => null, 'labelPosition' => null, 'instructions' => null] as $k => $v) if ($f->canSetProperty($k)) $f->$k = $v;   // a Hidden field has no placeholder
        return $f;
    };
    $rows = [   // one field per row, labels above, as live
        [$field('yourName', fields\Name::class, ['label' => 'Name', 'placeholder' => 'Name', 'required' => true])],
        [$field('emailAddress', fields\Email::class, ['label' => 'Email', 'placeholder' => 'Email', 'required' => true])],
        [$field('phoneNumber', fields\Phone::class, ['label' => 'Contact No.', 'placeholder' => 'Place your contact no. with country code', 'required' => true])],
        [$field('message', fields\MultiLineText::class, ['label' => 'Message', 'placeholder' => 'Place your requirement or a query'])],
        [$field('blogPost', fields\Hidden::class, ['label' => 'Blog Post', 'defaultOption' => 'custom']),
         $field('blogPostPage', fields\Hidden::class, ['label' => 'Blog Post Page', 'defaultOption' => 'currentUrl'])],
        // filled only by the import of live's submissions (09_blog_quote_submissions.php): live's own record
        [$field('liveSubmissionId', fields\Hidden::class, ['label' => 'Live Submission ID', 'defaultOption' => 'custom']),
         $field('liveForm', fields\Hidden::class, ['label' => 'Live Form', 'defaultOption' => 'custom']),
         $field('userAgent', fields\Hidden::class, ['label' => 'User Agent', 'defaultOption' => 'custom'])],
    ];
    $page = $form->getFormLayout()->getPages()[0];
    $page->setRows(array_map(fn($fields) => ['fields' => $fields], $rows));
    $page->getPageSettings()->submitButtonLabel = 'Send';
    $form->setIntegrationSettings('honeypot', ['enabled' => true]);
    $form->setIntegrationSettings('recaptcha', ['enabled' => true]);
    Craft::$app->getElements()->saveElement($form) || fail('form requestQuoteFromBlog', $form);

    // Formie only deletes what its builder lists as deleted: remove the copied fields live doesn't have (Country, How Did You Hear About Us) + empty rows
    $keep = array_map(fn($f) => $f->handle, array_merge(...$rows));
    $fieldsService = Formie::$plugin->getFields();
    foreach (Form::find()->handle('requestQuoteFromBlog')->one()->getFormLayout()->getPages() as $p) {
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
    say('  ~ requestQuoteFromBlog: fields as live');
}

$form = Form::find()->handle('requestQuoteFromBlog')->one();
foreach (Formie::$plugin->getNotifications()->getFormNotifications($form) as $n) {
    if ($n->name !== 'Admin Notification' || $n->to === ADMIN_EMAIL) continue;
    $n->to = ADMIN_EMAIL;
    Formie::$plugin->getNotifications()->saveNotification($n) || fail('notification', $n);
    say('  ~ Admin Notification → ' . ADMIN_EMAIL);
}

$labels($form) === LABELS || exit('requestQuoteFromBlog fields: ' . implode(' · ', $labels($form)) . "\n");
say('  = requestQuoteFromBlog: ' . implode(' · ', LABELS) . ' · notifications: ' . count(Formie::$plugin->getNotifications()->getFormNotifications($form)));

// ---- Blog Page: "Default CTA" → "Article Form" (the form at the end of every post) ---------------------------------
$entries = Craft::$app->getEntries();
$fields = Craft::$app->getFields();
$type = $entries->getEntryTypeByHandle('blogPage') ?? exit("no blogPage entry type\n");
$layout = $type->getFieldLayout();
if (!$layout->getFieldByHandle('formieForm')) {
    $tabs = $layout->getTabs();
    foreach ($tabs as $tab) {
        if ($tab->name !== 'Default CTA') continue;
        $tab->name = 'Article Form';
        $els = [new craft\fieldlayoutelements\CustomField($fields->getFieldByHandle('formieForm'), ['label' => 'Form',
            'instructions' => 'The form at the end of every blog post (as live: “Request Quote From Blog”). Its submissions: Formie → Submissions, each with the post it came from. Empty = the red Call to Action box below instead.'])];
        foreach ($tab->getElements() as $el) {
            if ($el instanceof craft\fieldlayoutelements\CustomField) {
                $el->label = ['ctaHeading' => 'Heading', 'ctaText' => 'Intro', 'ctaLink' => 'Button (only without a form)'][$el->attribute()] ?? $el->label;
                if ($el->attribute() === 'ctaHeading') $el->instructions = 'Over the form, e.g. “Contact With Our Team Now” (as live).';
                if ($el->attribute() === 'ctaText') $el->instructions = 'Optional text under the heading.';
            }
            $els[] = $el;
        }
        $tab->setElements($els);
    }
    $layout->setTabs($tabs);
    $type->setFieldLayout($layout);
    $entries->saveEntryType($type) || fail('entry type blogPage', $type);
    saveProjectConfig();
    say('  ~ Blog Page: “Default CTA” → “Article Form” (+ Form)');
}
$blogPage = Entry::find()->section('blogPage')->one() ?? exit("no Blog Page entry\n");
if (!$blogPage->getFieldValue('formieForm')->ids()) {
    $blogPage->setFieldValue('formieForm', [$form->id]);
    $blogPage->setFieldValue('ctaText', '');   // live has only the heading over the form
    Craft::$app->getElements()->saveElement($blogPage) || fail('Blog Page', $blogPage);
    say('  ~ Blog Page: Form = Request Quote From Blog, heading “' . $blogPage->getFieldValue('ctaHeading') . '”');
}
