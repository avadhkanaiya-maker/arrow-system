<?php
/**
 * Pages: /aviation-films/, from the live page (https://arrsys.com/aviation-films/, read 2026-10-22; no design file). Live:
 * the H1 "3M Aviation Films", the heading "Product Category" and a grid of 16 3M products (photo, name, Read More).
 * General blocks only:
 *   Page Header           Full-width banner: "3M Aviation [Films]", the banner uploaded with the live page
 *                         (3M_Banner_website-1.webp: the plane + 3M logo, without the words; live doesn't show it now),
 *                         button Request a Quote
 *   Recommended Products  "Product [Category]": the 15 live products that exist (picked, in the live order). Live's
 *                         "Versatile Print Label 7908V" links to the A7322 film by mistake: here its own product; live's
 *                         "Microfluidic Diagnostic Tape 9965" no longer exists on live (its link redirects here): left out
 *   SEO                   the live title and description
 * Also: Recommended Products takes up to 24 picked products (was 12), for any page or industry.
 * Then the menu items and link fields pointing at the live page link here. Skips the page when it exists. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/pages/19_aviation_films.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\db\Query;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\fields\Link;
use verbb\navigation\elements\Node;

const LIVE_URL = 'https://arrsys.com/aviation-films/';
const PRODUCTS = [   // live order, staging slugs
    'scotchgard-paint-protection-film-sgh6-84812-8-mil-transparent-12-in-x-40-yd',
    '3m-crystal-glass-finishes-7725se-314-dusted-crystal-48-in-x-150-ft-1-roll-case',
    '3m-scotchpak-polyester-backing-film-laminate-9738-config-roll',
    '3m-scotchcal-electrocut-graphic-film-7725se-314-dusted-crystal-48-in-x-10-yd',
    '3m-print-wrap-film-ij180mc-10lse-54-in-x-50-yd',
    '3m_exterior-aircraft-graphic-film-a7322-perforated-48-in-x-50-yd',
    '3m_exterior-aircraft-graphic-film-a7322-non-perforated-48-in-x-50-yd',
    '3m_versatile-print-label-material-7908v-white-polyester-gloss-roll-config',
    '3m_controltac-graphic-film-with-comply-adhesive-180mc-10-white-48-in-x-50-yd-1-roll-case',
    '3m_adhesive-transfer-tape-468mp-clear-48-in-x-60-yd-5-mil-roll',
    '3m_screen-print-uv-gloss-clear',
    '3m-microfluidic-diagnostic-film-9962-thin-double-sided-hydrophilic-film-configurable',
    '3m-microfluidic-diagnostic-tape-9972a-double-sided-thick-spacer-tape-configurable',
    '3m-medical-film-9832f-polyurethane-on-white-78-carrier-configurable',
    '3m-medical-tape-9877-double-sided-transparent-polyester-60-liner-configurable',
];

// Recommended Products: up to 24 picked products
$fields = Craft::$app->getFields();
$picked = $fields->getFieldByHandle('selectedProducts');
if ($picked->maxRelations < 24) {
    $picked->maxRelations = 24;
    $fields->saveField($picked) || fail('field selectedProducts', $picked);
    saveProjectConfig();
    say('  ~ Recommended Products: up to 24 picked products');
}

$page = Entry::find()->section('pages')->slug('aviation-films')->status(null)->one();
if (!$page) {
    $ids = [];
    foreach (PRODUCTS as $slug) $ids[] = Entry::find()->section('products')->slug($slug)->status(null)->one()?->id ?? exit("no product $slug\n");

    $volume = Craft::$app->getVolumes()->getVolumeByHandle('images');
    $folder = Craft::$app->getAssets()->ensureFolderByFullPathAndVolume('pages', $volume, false);
    $banner = Asset::find()->folderId($folder->id)->filename('3M_Banner_website-1.webp')->one();
    if (!$banner) {
        $bytes = @file_get_contents('https://arrsys.com/media/3M_Banner_website-1.webp', false, stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0 (Arrow Systems staging migration)\r\n", 'timeout' => 90]]))
            ?: exit("could not download the banner\n");
        $tmp = Craft::$app->getPath()->getTempPath() . '/' . uniqid('page') . '-3M_Banner_website-1.webp';
        file_put_contents($tmp, $bytes);
        $banner = new Asset(['tempFilePath' => $tmp, 'filename' => '3M_Banner_website-1.webp', 'volumeId' => $volume->id, 'newFolderId' => $folder->id]);
        $banner->alt = 'Airliner wing and engine in flight, with the 3M “Science. Applied to Life.” logo';
        $banner->setScenario(Asset::SCENARIO_CREATE);
        Craft::$app->getElements()->saveElement($banner) || fail('banner', $banner);
    }
    $quote = Entry::find()->section('pages')->slug('request-quote')->one();

    $page = new Entry(['sectionId' => Craft::$app->getEntries()->getSectionByHandle('pages')->id,
        'typeId' => Craft::$app->getEntries()->getEntryTypeByHandle('page')->id, 'title' => '3M Aviation Films', 'slug' => 'aviation-films']);
    $page->setFieldValues([
        'seoTitle' => 'Aviation films - Arrow Systems, Inc.',
        'seoDescription' => '3M Aviation Films Product Category Scotchgard™ Paint Protection Film SGH6, 84812, 8 mil, Transparent, 12 in x 40 yd Read More 3M™ Crystal Glass Finishes',
        'pageBuilder' => ['sortOrder' => ['b1', 'b2'], 'entries' => [
            'b1' => ['type' => 'industryHeader', 'fields' => ['eyebrow' => 'Materials', 'sectionHeading' => '3M Aviation [Films]',
                'mediaImage' => [$banner->id], 'headerStyle' => 'banner', 'bannerHeight' => 'whole',
                'primaryCta' => $quote ? ['type' => 'entry', 'value' => $quote->id, 'label' => 'Request a Quote'] : ['type' => 'url', 'value' => 'https://arrsys.com/request-quote/', 'label' => 'Request a Quote']]],
            'b2' => ['type' => 'productShowcase', 'fields' => ['sectionHeading' => 'Product [Category]', 'productSource' => 'picked', 'selectedProducts' => $ids]],
        ]],
    ]);
    Craft::$app->getElements()->saveElement($page) || fail('page aviation-films', $page);
    $page->uri === 'aviation-films' || exit("the page got /{$page->uri}/: another entry uses /aviation-films/\n");
    $pb = $fields->getFieldByHandle('pageBuilder')->id;
    ($saved = (int)Entry::find()->ownerId($page->id)->fieldId($pb)->status(null)->count()) === 2 || exit("aviation-films: $saved of 2 blocks saved\n");
    $grid = Entry::find()->ownerId($page->id)->fieldId($pb)->type('productShowcase')->status(null)->one();
    $grid->getFieldValue('selectedProducts')->status(null)->ids() === $ids || exit("aviation-films: the products didn't save in order\n");
    say("  + page /{$page->uri}/ (Page Header banner, Recommended Products: " . count($ids) . ')');
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

// link fields pointing at the live page → this page, label kept; rich text links stay
$ids = (new Query())->select('es.elementId')->from(['es' => '{{%elements_sites}}'])
    ->innerJoin(['e' => '{{%elements}}'], '[[e.id]] = [[es.elementId]]')
    ->where(['e.revisionId' => null, 'e.draftId' => null, 'e.dateDeleted' => null])
    ->andWhere(['like', 'es.content', 'arrsys.com/aviation-films'])->column();
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
    say('  ~ ' . ($el instanceof Entry ? "{$el->type->handle} $id" : $el::displayName() . ' ' . ($el->title ?? $el->name ?? $id)) . ': ' . implode(', ', $changed) . ' → the page');
}
