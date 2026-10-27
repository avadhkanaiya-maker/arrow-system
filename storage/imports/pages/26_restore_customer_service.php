<?php
/**
 * /customer-service/ back to its built design (review 2026-10-26: "the design of this page is broken"). On 2026-10-21
 * 16:18, while Custom CSS was being tried out, one save from the control panel ("Applied Draft 1"):
 *   - deleted the Form block "Fill Up Our Customer Service form" (the Customer Service form)
 *   - added an empty Columns block and an empty Data Table (the empty box of lines)
 *   - gave the Page Header the class contact-us-section and the page the CSS ".contact-us-section { background: red; }"
 * Here: the Form block comes back from the trash (the same block), the two empty blocks are deleted (to the trash), the
 * test class and the page's test CSS are removed. Stops when the page has an open draft. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/pages/26_restore_customer_service.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\db\Query;
use craft\elements\Entry;

$elements = Craft::$app->getElements();
$page = Entry::find()->section('pages')->slug('customer-service')->status(null)->one() ?? exit("no page customer-service\n");
!(new Query())->from('{{%drafts}}')->where(['canonicalId' => $page->id])->exists() || exit("customer-service has an open draft: apply or delete it, then run again\n");
$pb = Craft::$app->getFields()->getFieldByHandle('pageBuilder')->id;

// 1. the Form block from the trash
$form = Entry::find()->ownerId($page->id)->fieldId($pb)->type('formBlock')->trashed()->status(null)->one();
if ($form) {
    $elements->restoreElement($form) || exit("could not restore the Form block\n");
    say("  + Form block “{$form->sectionHeading}” restored");
}

// 2. the empty Columns and Data Table blocks
foreach (Entry::find()->ownerId($page->id)->fieldId($pb)->status(null)->all() as $b) {
    $empty = match ($b->type->handle) {
        'columnsBlock' => !$b->getFieldValue('leftColumn')->status(null)->exists() && !$b->getFieldValue('rightColumn')->status(null)->exists(),
        'dataTable' => trim(str_replace("\u{a0}", '', html_entity_decode(strip_tags((string)$b->tableText) . $b->caption))) === '',   // blank cells only
        default => false,
    };
    if ($empty) {
        $elements->deleteElement($b) || exit("could not delete {$b->type->handle} #{$b->id}\n");
        say("  - empty {$b->type->handle} #{$b->id} (to the trash)");
    }
}

// 3. the test class and CSS
$header = Entry::find()->ownerId($page->id)->fieldId($pb)->type('industryHeader')->status(null)->one();
if ($header && trim((string)$header->cssClass) === 'contact-us-section') {
    $header->setFieldValue('cssClass', null);
    $elements->saveElement($header) || fail('header', $header);
    say('  ~ Page Header: test class contact-us-section removed');
}
if (str_contains((string)$page->customCss, '.contact-us-section')) {
    $page = Entry::find()->id($page->id)->status(null)->one();
    $page->setFieldValue('customCss', null);
    $elements->saveElement($page) || fail('page', $page);
    say('  ~ page Custom CSS (the red test) removed');
}

// self-check: header + the Customer Service form, nothing red
$blocks = array_map(fn($b) => $b->type->handle, Entry::find()->ownerId($page->id)->fieldId($pb)->status(null)->all());
$blocks === ['industryHeader', 'formBlock'] || exit('blocks now: ' . implode(', ', $blocks) . "\n");
$html = @file_get_contents('http://localhost:8081/customer-service/') ?: exit("/customer-service/ does not load\n");
str_contains($html, 'id="customer-service-form"') && !str_contains($html, 'background:red') || exit("/customer-service/: form missing or the red CSS still there\n");
say('  = /customer-service/: Page Header + Customer Service form, as built');
