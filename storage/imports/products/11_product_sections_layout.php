<?php
/**
 * Products imported from WooCommerce with the whole live description in the header text (review 2026-10-22: "you entered
 * all contents inside intro instead of the page builder", same as Scotchgard SGH6 in 08). The header text holds the live
 * sections one after the other (<h3>Product Description</h3>, Highlights, Suggested applications, Typical properties →
 * Details / Dimensions and Classifications tables), and the import put the suggested applications in a tab labelled
 * "Highlights". This splits them as the live page shows them, live text and values unchanged:
 *   header text   Product Description (its paragraphs; its "(Download)" links are the Resources files)
 *   Tabs          Highlights (one row per point; a bold lead-in "Aesthetics:" → its title) · Suggested Applications ·
 *                 Typical Properties (one heading + table per live table) · the existing Downloads tabs kept as they are
 *                 (live: tabs such as "Data Sheets (2)" / "Installation Guide" under "Resources")
 * Other blocks (Stats Strip, What You Can Print …) unchanged. Stops if the product has an open draft, or if a section it
 * expects is missing. Skips a product already split. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/products/11_product_sections_layout.php <product slug> [--dry]
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\elements\Entry;

$slug = $argv[1] ?? exit("usage: … 11_product_sections_layout.php <product slug> [--dry]\n");
$dry = in_array('--dry', $argv, true);
$product = Entry::find()->section('products')->slug($slug)->status(null)->one() ?? exit("no product $slug\n");
(int)(new craft\db\Query())->from('{{%drafts}}')->where(['canonicalId' => $product->id])->count() === 0 || exit("$slug has an open draft: apply or delete it first\n");

$html = (string)$product->getFieldValue('richText');
preg_match_all('#<h3>(.*?)</h3>#s', $html, $m, PREG_OFFSET_CAPTURE);
count($m[0]) > 1 || exit("$slug: the header text has no live sections (already split?)\n");
$sections = [];   // heading => html up to the next heading
foreach ($m[0] as $i => [$tag, $at]) {
    $start = $at + strlen($tag);
    $end = $m[0][$i + 1][1] ?? strlen($html);
    $sections[trim(html_entity_decode(strip_tags($m[1][$i][0])))] = trim(substr($html, $start, $end - $start));
}
$get = fn(string $name) => $sections[$name] ?? null;
$description = $get('Product Description') ?? exit("$slug: no Product Description\n");
$description = preg_replace('#<p>\s*<a [^>]*href="[^"]+\.pdf"[^>]*>.*?</p>#si', '', $description);   // the download links: the Resources tab
$paragraphs = fn(?string $h) => $h === null ? [] : array_values(array_filter(array_map('trim', preg_split('#</p>\s*<p>|</?p>#', $h)), fn($p) => trim(strip_tags($p)) !== ''));

$tabsBlock = $product->getFieldValue('productBuilder')->type('tabsBlock')->status(null)->one() ?? exit("$slug: no Tabs block\n");
$oldTabs = $tabsBlock->getFieldValue('productTabs')->status(null)->all();

// Highlights: one row per paragraph; "<strong>Aesthetics</strong>: text" → title + text; a leading "· " dropped
$highlights = [];
foreach ($paragraphs($get('Highlights')) as $p) {
    $p = preg_replace('#<br\s*/?>\s*(<br\s*/?>)?#', ' ', $p);
    if (preg_match('#^\s*<strong>(.*?)</strong>\s*:?\s*(.*)$#s', $p, $b)) {
        $highlights[] = ['icon' => 'check', 'title' => trim(strip_tags($b[1]), " :\u{00A0}"), 'text' => trim(html_entity_decode(strip_tags($b[2])))];
    } else {
        $highlights[] = ['icon' => 'check', 'title' => '', 'text' => trim(html_entity_decode(strip_tags($p)), "· \u{00A0}")];
    }
}
// Suggested applications: the header's list, else the rows of the tab the import mislabelled "Highlights"
$apps = [];
if ($get('Suggested applications') && preg_match_all('#<li>(.*?)</li>#s', $get('Suggested applications'), $li)) $apps = array_map(fn($x) => trim(html_entity_decode(strip_tags($x))), $li[1]);
if (!$apps) foreach ($oldTabs as $t) if ($t->type->handle === 'highlightsTab') $apps = array_values(array_filter(array_column($t->getFieldValue('highlightItems'), 'title')));
// Typical properties: every section with a table, in order (Details, Dimensions and Classifications …)
$properties = '';
foreach ($sections as $name => $h) {
    if (!str_contains($h, '<table')) continue;
    $properties .= '<h4>' . htmlspecialchars($name) . '</h4>' . preg_replace('#<td>\s*<p>(.*?)</p>\s*</td>#s', '<td>$1</td>', $h);
}
// Resources: the existing Downloads tabs, kept as they are (live shows them as tabs under "Resources")
$downloads = array_values(array_filter($oldTabs, fn($t) => $t->type->handle === 'downloadsTab'));

$tabs = ['sortOrder' => [], 'entries' => []];
$add = function (string $type, array $fields) use (&$tabs) { $k = 't' . (count($tabs['sortOrder']) + 1); $tabs['sortOrder'][] = $k; $tabs['entries'][$k] = ['type' => $type, 'fields' => $fields]; };
if ($highlights) $add('highlightsTab', ['headingText' => 'Highlights', 'sectionHeading' => 'Highlights', 'highlightItems' => $highlights]);
if ($apps) $add('textTab', ['headingText' => 'Suggested Applications', 'richText' => '<ul>' . implode('', array_map(fn($a) => '<li>' . htmlspecialchars($a) . '</li>', $apps)) . '</ul>']);
if ($properties) $add('textTab', ['headingText' => 'Typical Properties', 'richText' => $properties]);
foreach ($downloads as $t) { $tabs['sortOrder'][] = $t->id; $tabs['entries'][$t->id] = ['type' => 'downloadsTab', 'fields' => ['headingText' => $t->headingText]]; }
$tabs['sortOrder'] || exit("$slug: nothing to put in tabs\n");

say("$slug");
say('  header: Product Description, ' . count($paragraphs($description)) . ' paragraphs');
foreach ($tabs['entries'] as $t) say('  tab ' . $t['fields']['headingText'] . ': ' . match ($t['type']) {
    'highlightsTab' => count($t['fields']['highlightItems']) . ' points',
    'downloadsTab' => 'kept as is',
    default => substr_count($t['fields']['richText'], '<li>') ? substr_count($t['fields']['richText'], '<li>') . ' items' : substr_count($t['fields']['richText'], '<table>') . ' tables, ' . substr_count($t['fields']['richText'], '<tr>') . ' rows',
});
if ($dry) exit(0);

$product->setFieldValue('richText', '<h3>Product Description</h3>' . $description);
Craft::$app->getElements()->saveElement($product, false) || fail('product', $product);
$tabsBlock->setFieldValue('productTabs', $tabs);
Craft::$app->getElements()->saveElement($tabsBlock) || fail('tabs block', $tabsBlock);

$now = Entry::find()->id($tabsBlock->id)->status(null)->one()->getFieldValue('productTabs')->status(null)->all();   // reloaded: the old tabs are gone
array_map(fn($t) => $t->headingText, $now) === array_map(fn($t) => $t['fields']['headingText'], $tabs['entries']) || exit("tabs not saved\n");
say('  = saved');
