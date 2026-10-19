<?php
/**
 * Products, new product page design: content (structure: 05_new_design.php).
 *   - Globals → Stats Strip: the design's 4 figures (only when empty; TODO(client): the figures are unverified)
 *   - Every product: + a Stats Strip block before the first Tabs block and a What You Can Print block after the last one
 *     (automatic industries); existing blocks untouched. Skipped when the product already has a Stats Strip block.
 *   - The pilot (arrowjet-aqua-330r-lite-2) from the design: Subtitle, Intro (the design's description), Header Specs, main image
 *     Caption "Front view", Overview tab → Highlights (6 cards), Features tab → the 20 merged specifications, the 6
 *     industries of "What You Can Print". The live name is kept (TODO(client) in the design).
 * Idempotent. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/products/06_new_design_content.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\elements\Entry;

const DESIGN_PAGE = '/home/avadh/server/theglobalwebdev/demo/public_html/Arrowsystem/digital-label-printers/product-detail.html';
const PILOT = 'arrowjet-aqua-330r-lite-2';

$doc = new DOMDocument();
@$doc->loadHTML('<?xml encoding="utf-8"?>' . file_get_contents(DESIGN_PAGE));
$x = new DOMXPath($doc);
$t = fn(?DOMNode $n) => $n ? trim(preg_replace('/\s+/u', ' ', $n->textContent)) : '';
$icon = fn(DOMNode $n) => preg_replace('/^#i-/', '', $x->query('.//use', $n)->item(0)?->getAttribute('href') ?? '');
$save = function (Entry $e) { Craft::$app->getElements()->saveElement($e) || fail("entry {$e->slug}", $e); };

// ---- Globals → Stats Strip ----------------------------------------------------------------------------------------
$set = Craft::$app->getGlobals()->getSetByHandle('statsStrip');
if (!array_filter(array_column($set->getFieldValue('statItems') ?? [], 'value'))) { // an empty table still has one blank row
    $rows = [];
    foreach ($x->query('//ul[@class="pd-stats__list"]/li') as $li)
        $rows[] = ['value' => $t($x->query('.//strong', $li)->item(0)), 'label' => $t($x->query('.//span', $li)->item(0)), 'icon' => $icon($li)];
    count($rows) === 4 || exit("stats: expected 4 rows, got " . count($rows) . "\n");
    $set->setFieldValue('statItems', $rows);
    Craft::$app->getElements()->saveElement($set) || fail('global statsStrip', $set);
    say('  ~ Stats Strip: ' . implode(' / ', array_column($rows, 'value')));
}

// ---- Every product: + Stats Strip, + What You Can Print -----------------------------------------------------------
$added = 0;
foreach (Entry::find()->section('products')->status(null)->all() as $product) {
    $blocks = $product->getFieldValue('productBuilder')->status(null)->all();
    $types = array_map(fn($b) => $b->type->handle, $blocks);
    if (in_array('statsStrip', $types, true)) continue;
    $ids = array_map(fn($b) => $b->id, $blocks);
    $tabs = array_keys($types, 'tabsBlock', true);
    $leadingVideos = 0; // header videos stay first
    while (($types[$leadingVideos] ?? null) === 'productVideo') $leadingVideos++;
    $statsAt = $tabs ? $tabs[0] : $leadingVideos;
    $appsAt = $tabs ? end($tabs) + 1 : count($ids);
    $order = array_merge(array_slice($ids, 0, $statsAt), ['new1'], array_slice($ids, $statsAt, $appsAt - $statsAt), ['new2'], array_slice($ids, $appsAt));
    $product->setFieldValue('productBuilder', ['sortOrder' => $order, 'entries' => [
        'new1' => ['type' => 'statsStrip', 'fields' => []],
        'new2' => ['type' => 'productApplications', 'fields' => ['eyebrow' => 'Applications', 'sectionHeading' => 'What You [Can Print]',
            'sectionLead' => 'The industries the {name} is built to serve.']],
    ]]);
    $save($product);
    $after = array_map(fn($b) => $b->type->handle, $product->getFieldValue('productBuilder')->status(null)->all());
    count($after) === count($blocks) + 2 || exit("{$product->slug}: blocks lost (" . implode(',', $after) . ")\n");
    $added++;
}
say("  ~ products: + Stats Strip and What You Can Print on $added");

// ---- The pilot ----------------------------------------------------------------------------------------------------
$p = Entry::find()->section('products')->slug(PILOT)->one() ?? exit("no pilot\n");
if (!array_filter(array_column($p->getFieldValue('headerSpecs') ?? [], 'value'))) {
    $p->setFieldValue('subheading', $t($x->query('//p[@class="pd-hero__subtitle"]')->item(0)));
    $p->setFieldValue('richText', '<p>' . htmlspecialchars($t($x->query('//p[@class="pd-hero__intro"]')->item(0))) . '</p>');
    $specs = [];
    foreach ($x->query('//dl[@class="pd-keyspecs"]/div') as $div) {
        $dt = $x->query('dt', $div)->item(0);
        $note = $x->query('span', $dt)->item(0);
        $specs[] = ['value' => $t($x->query('.//strong', $div)->item(0)), 'label' => trim(str_replace($t($note), '', $t($dt))), 'note' => $t($note), 'icon' => $icon($div)];
    }
    $p->setFieldValue('headerSpecs', $specs);

    $highlights = [];
    foreach ($x->query('//ul[@class="pd-hl"]/li') as $li)
        $highlights[] = ['icon' => $icon($li), 'title' => $t($x->query('.//h4', $li)->item(0)), 'text' => $t($x->query('.//p', $li)->item(0))];
    $specRows = [];
    foreach ($x->query('//div[@id="features"]//dl[@class="spec-list"]/div') as $div)
        $specRows[] = ['term' => $t($x->query('dt', $div)->item(0)), 'description' => $t($x->query('dd', $div)->item(0))];
    count($highlights) === 6 && count($specRows) === 20 || exit('design: ' . count($highlights) . ' highlights, ' . count($specRows) . " specs\n");

    $blocks = $p->getFieldValue('productBuilder')->status(null)->all();
    $value = ['sortOrder' => [], 'entries' => []];
    foreach ($blocks as $b) {
        $value['sortOrder'][] = $b->id;
        if ($b->type->handle === 'tabsBlock') {
            $tabs = ['sortOrder' => [], 'entries' => []];
            foreach ($b->getFieldValue('productTabs')->status(null)->all() as $i => $tab) {
                if ($tab->type->handle === 'textTab' && $tab->headingText === 'Overview') { // → Highlights
                    $tabs['sortOrder'][] = "new$i";
                    $tabs['entries']["new$i"] = ['type' => 'highlightsTab', 'fields' => ['headingText' => 'Overview', 'sectionHeading' => 'Highlights', 'highlightItems' => $highlights]];
                } elseif ($tab->type->handle === 'specsTab') {
                    $tabs['sortOrder'][] = $tab->id;
                    $tabs['entries'][$tab->id] = ['type' => 'specsTab', 'fields' => ['headingText' => $tab->headingText, 'specItems' => $specRows]];
                } else $tabs['sortOrder'][] = $tab->id;
            }
            $value['entries'][$b->id] = ['type' => 'tabsBlock', 'fields' => ['productTabs' => $tabs]];
        } elseif ($b->type->handle === 'productApplications') {
            $picked = [];
            foreach ($x->query('//h3[@class="pd-app__title"]/a/@href') as $href) {
                $slug = basename(rtrim($href->value, '/'));
                if ($slug === 'industry-detail.html') $slug = 'flexible-packaging-and-custom-mylar-bags'; // the design's own industry page
                $picked[] = Entry::find()->section('industries')->slug($slug)->one()?->id ?? exit("no industry $slug\n");
            }
            $value['entries'][$b->id] = ['type' => 'productApplications', 'fields' => ['eyebrow' => $b->eyebrow, 'sectionHeading' => $b->sectionHeading,
                'sectionLead' => $t($x->query('//p[@class="pd-apps__lead"]')->item(0)), 'applications' => $picked]];
        }
    }
    $p->setFieldValue('productBuilder', $value);
    $save($p);

    $image = $p->getFieldValue('mediaImage')->one();
    if ($image && !$image->getFieldValue('caption')) {
        $image->setFieldValue('caption', $t($x->query('//figcaption[@class="pd-media__label"]')->item(0)));
        Craft::$app->getElements()->saveElement($image) || fail('asset caption', $image);
    }
    say("  ~ pilot {$p->slug}: subtitle, description, 4 header specs, Highlights tab (6), specifications (" . count($specRows) . "), applications (" . count($picked ?? []) . ')');
}
