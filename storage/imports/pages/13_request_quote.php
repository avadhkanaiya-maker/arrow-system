<?php
/**
 * Pages: /request-quote/, from the live page (https://arrsys.com/request-quote/, read 2026-10-21; no design file). Live
 * has only the H1 "Request Quote" and the form (no image: the media library has none for it). General blocks only:
 *   Page Header  "Request [Quote]" as live, no image (the text across the full width)
 *   Columns      maroon panel: Contact Us (toll-free, direct, email, headquarters: the Site Settings details, as on
 *                /support/) | white card: the Formie form Request a Quote (forms/03_request_quote.php), anchor quote-form
 *   SEO          the live title and description
 * Then everything that links to the live page links here: the menu items, Site Settings → Request a Quote Link (every
 * "Request a Quote" button: header, mobile menu) and the link fields of blocks (CTA bands, page headers, content
 * blocks). Links typed inside rich text are left as they are. Skips the page when it exists. Back up first.
 * Run from the project root, after forms/03_request_quote.php: /opt/lampp/bin/php storage/imports/pages/13_request_quote.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\db\Query;
use craft\elements\Entry;
use craft\fields\Link;
use verbb\formie\elements\Form;
use verbb\navigation\elements\Node;

const LIVE_URL = 'https://arrsys.com/request-quote/';

$page = Entry::find()->section('pages')->slug('request-quote')->status(null)->one();
if (!$page) {
    $form = Form::find()->handle('requestQuote')->one() ?? exit("no Formie form requestQuote: run forms/03_request_quote.php\n");
    $s = Craft::$app->getGlobals()->getSetByHandle('siteSettings');
    $tel = fn(string $p) => '+' . ltrim(preg_replace('/\D/', '', str_starts_with($p, '+') ? $p : '1' . $p), '+');
    $contact = '<h2>Contact Us</h2>'
        . "<p><strong>Toll-free</strong><br><a href=\"tel:{$tel($s->tollFreePhone)}\">{$s->tollFreePhone}</a></p>"
        . "<p><strong>Direct</strong><br><a href=\"tel:{$tel($s->directPhone)}\">{$s->directPhone}</a></p>"
        . "<p><strong>Email</strong><br><a href=\"mailto:{$s->salesEmail}\">{$s->salesEmail}</a></p>"
        . "<p><strong>Headquarters</strong><br>{$s->address}</p>";

    $page = new Entry(['sectionId' => Craft::$app->getEntries()->getSectionByHandle('pages')->id,
        'typeId' => Craft::$app->getEntries()->getEntryTypeByHandle('page')->id, 'title' => 'Request a Quote', 'slug' => 'request-quote']);
    $page->setFieldValues([
        'seoTitle' => 'Request Quote - Arrow Systems, Inc.',
        'seoDescription' => 'Request Quote',
        'cardTagline' => 'Tell us the product you need and we get back to you with a quote.',
        'pageBuilder' => ['sortOrder' => ['b1', 'b2'], 'entries' => [
            'b1' => ['type' => 'industryHeader', 'fields' => ['eyebrow' => 'Request a Quote', 'sectionHeading' => 'Request [Quote]']],
            'b2' => ['type' => 'columnsBlock', 'fields' => ['columnsLayout' => '2-3', 'leftStyle' => 'panel', 'rightStyle' => 'card', 'columnsAlign' => 'top', 'spacingTop' => 'small',
                'leftColumn' => ['sortOrder' => ['n1'], 'entries' => ['n1' => ['type' => 'richText', 'fields' => ['richText' => $contact]]]],
                'rightColumn' => ['sortOrder' => ['n1'], 'entries' => ['n1' => ['type' => 'formBlock', 'fields' => [
                    'formieForm' => [$form->id], 'anchorId' => 'quote-form']]]],
            ]],
        ]],
    ]);
    Craft::$app->getElements()->saveElement($page) || fail('page request-quote', $page);
    $page->uri === 'request-quote' || exit("the page got /{$page->uri}/: another entry uses /request-quote/\n");
    $saved = (int)Entry::find()->ownerId($page->id)->fieldId(Craft::$app->getFields()->getFieldByHandle('pageBuilder')->id)->status(null)->count();
    $saved === 2 || exit("request-quote: $saved of 2 blocks saved\n");
    say("  + page /{$page->uri}/ (Page Header, Columns: contact panel + Request a Quote form)");
}

// menu items pointing at the live page → this page
foreach (Node::find()->status(null)->all() as $n) {
    if (rtrim((string)$n->url, '/') . '/' !== LIVE_URL) continue;
    $n->type = Entry::class;
    $n->elementId = $page->id;
    $n->url = null;
    Craft::$app->getElements()->saveElement($n) || fail("menu node {$n->id}", $n);
    say("  ~ menu {$n->menu->handle} “{$n->title}” → the page");
}

// link fields (Site Settings → Request a Quote Link, block buttons) pointing at the live page → this page, label kept
$ids = (new Query())->select('es.elementId')->from(['es' => '{{%elements_sites}}'])
    ->innerJoin(['e' => '{{%elements}}'], '[[e.id]] = [[es.elementId]]')
    ->where(['e.revisionId' => null, 'e.draftId' => null, 'e.dateDeleted' => null])
    ->andWhere(['like', 'es.content', 'arrsys.com/request-quote'])->column();
foreach ($ids as $id) {
    $el = Craft::$app->getElements()->getElementById($id, null, null, ['status' => null]);
    if (!$el || !$el->getFieldLayout()) continue;
    $changed = [];
    foreach ($el->getFieldLayout()->getCustomFields() as $f) {
        if (!$f instanceof Link) continue;
        $v = $el->getFieldValue($f->handle);
        if (!$v || rtrim((string)$v->url, '/') . '/' !== LIVE_URL) continue;
        $el->setFieldValue($f->handle, ['type' => 'entry', 'value' => $page->id, 'label' => $v->label, 'target' => $v->target]);
        $changed[] = $f->handle;
    }
    if (!$changed) continue;
    Craft::$app->getElements()->saveElement($el) || fail("element $id", $el);
    say('  ~ ' . ($el instanceof Entry ? "{$el->type->handle} $id" : $el::displayName() . " " . ($el->title ?? $el->name ?? $id)) . ': ' . implode(', ', $changed) . ' → the page');
}
