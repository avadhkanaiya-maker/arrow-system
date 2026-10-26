<?php
/**
 * Product Request Quote in Formie → Submissions (2026-10-22: "how can I tell which submission is for which product"):
 *   - Title "Product · date" (the form's title format since then: '{field:product} · {timestamp}'); the submissions
 *     saved before keep their date and get the product in front (none when Product is empty).
 *   - "Product (entry)" set to the staging product their Product Page names (modules/forms does it on save), so
 *     Filter → Product (entry) lists the products. A page no longer on staging stays unlinked (Product text only).
 * Idempotent. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/forms/11_product_request_quote_list.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\db\Table;
use craft\helpers\Db;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

$form = Form::find()->handle('productRequestQuote')->one() ?? exit("no Formie form productRequestQuote\n");
$ids = Submission::find()->formId($form->id)->status(null)->ids();
$linked = $titled = $unlinked = 0;
foreach ($ids as $id) {   // one at a time: 1,000+ saves in one query run out of memory
    $s = Submission::find()->id($id)->status(null)->one();
    if (!$s->getFieldValue('productEntry')->ids()) {
        Craft::$app->getElements()->saveElement($s, false) || fail("submission {$s->id}", $s);   // modules/forms links the product
        $s->getFieldValue('productEntry')->ids() ? $linked++ : $unlinked++;
    }
    $product = trim((string)$s->getFieldValue('product'));
    $date = preg_replace('/^.* · /', '', (string)$s->title);   // the date part, also on a second run
    $title = $product !== '' ? "$product · $date" : $date;
    if ($title !== $s->title) {
        Db::update(Table::ELEMENTS_SITES, ['title' => $title], ['elementId' => $s->id, 'siteId' => $s->siteId]);
        $titled++;
    }
}
say(count($ids) . " submissions: $linked linked to their product, $unlinked with no product on staging; $titled retitled");

// self-check: every submission with a staging product page is linked, and its title starts with its product
foreach (Submission::find()->formId($form->id)->status(null)->each() as $s) {
    $product = trim((string)$s->getFieldValue('product'));
    $product === '' || str_starts_with((string)$s->title, "$product · ") || exit("submission {$s->id}: title “{$s->title}”\n");
    if (preg_match('#/product/([^/?\#]+)#', (string)$s->getFieldValue('productPage'), $m)
        && craft\elements\Entry::find()->section('products')->slug($m[1])->status(null)->exists()) {
        $s->getFieldValue('productEntry')->ids() || exit("submission {$s->id}: product page {$m[1]} not linked\n");
    }
}
say('  = titles and product links as expected');
