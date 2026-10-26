<?php
/**
 * Product /product/scotchgard-paint-protection-film-sgh6-84812-8-mil-transparent-12-in-x-40-yd/ (review 2026-10-21:
 * "cluttered, segments not visible as on live"). The import put the whole live description in the header's text column
 * (description, highlights, two empty headings, two tables) and the suggested applications in a tab labelled
 * "Highlights". Rearranged with the existing blocks, live text and values unchanged, segments as on live:
 *   header text   Product Description (its three paragraphs)
 *   Tabs          Highlights: the seven highlights · Suggested Applications: the eight surfaces ·
 *                 Typical Properties: "Details" and "Dimensions and Classifications", each its own table
 * Stats Strip and Applications blocks unchanged. Idempotent (skips once the tabs are in place). Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/products/08_scotchgard_sgh6_layout.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\elements\Entry;

$product = Entry::find()->section('products')->slug('scotchgard-paint-protection-film-sgh6-84812-8-mil-transparent-12-in-x-40-yd')->status(null)->one()
    ?? exit("no product\n");
$tabsBlock = $product->getFieldValue('productBuilder')->type('tabsBlock')->status(null)->one() ?? exit("no Tabs block\n");
$tabs = $tabsBlock->getFieldValue('productTabs')->status(null)->all();
if (array_map(fn($t) => $t->headingText, $tabs) === ['Highlights', 'Suggested Applications', 'Typical Properties']) exit("already rearranged\n");

// the live description's parts, from the header text as imported
$html = (string)$product->getFieldValue('richText');
$part = function (string $heading, ?string $next) use ($html): string {
    $start = strpos($html, "<h3>$heading</h3>");
    $start !== false || exit("no “{$heading}” in the product text\n");
    $start += strlen("<h3>$heading</h3>");
    $end = $next === null ? strlen($html) : strpos($html, "<h3>$next</h3>", $start);
    return trim(substr($html, $start, $end - $start));
};
$description = $part('Product Description', 'Highlights');
$highlights = array_map(fn($p) => trim(html_entity_decode(strip_tags($p)), "· \u{00A0}"), preg_split('#</p>\s*<p>#', trim($part('Highlights', 'Suggested applications'), '<p></p>')));
$details = $part('Details', 'Dimensions and Classifications');
$dimensions = $part('Dimensions and Classifications', null);
// the applications: the old "Highlights" tab's rows
$apps = array_column(current(array_filter($tabs, fn($t) => $t->type->handle === 'highlightsTab'))->getFieldValue('highlightItems'), 'title');
count($highlights) === 7 && count($apps) === 8 && substr_count($details . $dimensions, '<table>') === 2 || exit("unexpected content: " . count($highlights) . " highlights, " . count($apps) . " applications\n");
$cell = fn(string $t) => preg_replace('#<td>\s*<p>(.*?)</p>\s*</td>#s', '<td>$1</td>', $t);   // table cells without inner paragraphs

$product->setFieldValue('richText', "<h3>Product Description</h3>$description");
Craft::$app->getElements()->saveElement($product) || fail('product', $product);

$tabsBlock->setFieldValue('productTabs', ['sortOrder' => ['t1', 't2', 't3'], 'entries' => [
    't1' => ['type' => 'highlightsTab', 'fields' => ['headingText' => 'Highlights', 'sectionHeading' => 'Highlights',
        'highlightItems' => array_map(fn($h) => ['icon' => 'check', 'title' => '', 'text' => $h], $highlights)]],
    't2' => ['type' => 'textTab', 'fields' => ['headingText' => 'Suggested Applications',
        'richText' => '<ul>' . implode('', array_map(fn($a) => '<li>' . htmlspecialchars($a) . '</li>', $apps)) . '</ul>']],
    't3' => ['type' => 'textTab', 'fields' => ['headingText' => 'Typical Properties',
        'richText' => '<h4>Details</h4>' . $cell($details) . '<h4>Dimensions and Classifications</h4>' . $cell($dimensions)]],
]]);
Craft::$app->getElements()->saveElement($tabsBlock) || fail('tabs block', $tabsBlock);

$now = Entry::find()->ownerId($tabsBlock->id)->fieldId(Craft::$app->getFields()->getFieldByHandle('productTabs')->id)->status(null)->all();
array_map(fn($t) => $t->headingText, $now) === ['Highlights', 'Suggested Applications', 'Typical Properties'] || exit("tabs not saved\n");
say('  ~ header: Product Description only; Tabs: Highlights (7), Suggested Applications (8), Typical Properties (Details + Dimensions)');
