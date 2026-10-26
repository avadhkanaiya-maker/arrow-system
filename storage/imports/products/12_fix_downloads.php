<?php
/**
 * Product downloads made the same as live (review 2026-10-22: "fix the brochure issue in all products"). Every product's
 * Downloads tabs were compared with the named download links on its live page (2026-10-22, 56 products, 105 files):
 *   - 18 downloads came from live's small PDF icon in front of each brochure name: the icon is its own link (often to
 *     another file, sometimes another product's brochure), the import took it as a download and named it
 *     "<product> Brochure". Live shows only the named brochures → removed.
 *   - ArrowJet Aqua 330R Lite (arrowjet-aqua-330r-lite-2): its brochure pointed to ArrowJet-Aqua-330R.pdf; live's is
 *     arrowjet-aqua-330r-lite.pdf (a different file, not on staging yet) → downloaded and linked.
 *   - 3M Screen Print UV Gloss Clear 9740I: its file's name as on live, "Safety Data Sheet".
 * Titles otherwise match live (live adds " (Download)", the design shows a download icon instead). The removed files stay
 * in Assets (some are other products' brochures). Stops if a product has an open draft. Idempotent. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/products/12_fix_downloads.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\elements\Asset;
use craft\elements\Entry;

const REMOVE = [   // product slug => file of the icon-made download
    'gerber-gsxplus' => 'Arrow-Overjet-Pro.pdf',
    'gerber-gs750-plus' => 'Arrow-Overjet-Pro.pdf',
    'gerber-gs15-plus-plotter' => 'Arrow-Overjet-Pro.pdf',
    'arrowcut-nova-250r-laser-label-cutter' => 'ArrowCut-Nova-250R_Web-compressed_1.pdf',
    'arrowjet-nova250r-inline-digital-printer-and-laser-die-cutter-2' => 'ArrowJet-Nova-250R_Web-compressed_1.pdf',
    'arrowjet-aqua-330r-hybrid-pro' => 'ArrowJet-AQUA-HYBRID_Pro_Web-compressed_1.pdf',
    'arrowjet-uv' => 'ArrowJet-Hyrid-Pro-M_Asi_compressed.pdf',
    'arrowjet-nova-250-plus' => 'ArrowJet-Nova-250R_Web-compressed_1.pdf',
    'arrowezcut-330rplus' => 'ArrowCut-Nova-250R_Web-compressed_1.pdf',
    'arrow-bolt-330r-hybrid-pro-m' => 'ArrowJet-Hyrid-Pro-M_Asi_compressed.pdf',
    'matan-spring3' => 'Arrow-EZCUT-240.pdf',
    'arrowezcut-350r' => 'Arrow-EZCUT-240.pdf',
    'arrowjet-aqua-330r-hybrid-pro-m' => 'ArrowJet-Hyrid-Pro-M_Asi_compressed.pdf',
    'arrowjet-nova-330r' => 'ArrowJet-Nova-330R_Web-compressed_1.pdf',
    'arrowjet-aqua-330r-hybrid-2' => 'ArrowJet-AQUA-HYBRID_Web-compressed_1.pdf',
    'arrowjet-aqua-330r-lite' => 'ArrowJet-Aqua-330R-Lite_Web-compressed_1.pdf',
    'arrowcut-nova-330r-roll-to-roll-laser-cutter' => 'ArrowCut-Nova-330R_Web-compressed_1.pdf',
    'arrow-ezcut-330r' => 'Arrow-ez-cut-330r_Web-compressed_1.pdf',
];
const REPLACE = ['arrowjet-aqua-330r-lite-2', 'ArrowJet-Aqua-330R.pdf', 'https://arrsys.com/media/arrowjet-aqua-330r-lite.pdf'];
const RENAME = ['3m_screen-print-uv-gloss-clear', '3M™ Screen Print UV Gloss Clear 9740i', 'Safety Data Sheet'];

$product = fn(string $slug) => Entry::find()->section('products')->slug($slug)->status(null)->one() ?? exit("no product $slug\n");
/** Every download item of a product: [tab entry, item entry, file name] */
$downloads = function (Entry $p): array {
    $out = [];
    foreach ($p->getFieldValue('productBuilder')->status(null)->all() as $b) {
        $tabs = $b->type->handle === 'tabsBlock' ? $b->getFieldValue('productTabs')->type('downloadsTab')->status(null)->all() : ($b->type->handle === 'downloadsBlock' ? [$b] : []);
        foreach ($tabs as $t) foreach ($t->getFieldValue('downloadItems')->status(null)->all() as $d) $out[] = [$t, $d, $d->getFieldValue('downloadFile')->one()?->filename];
    }
    return $out;
};

// open drafts: stop
$ids = array_map(fn($s) => $product($s)->id, array_merge(array_keys(REMOVE), [REPLACE[0], RENAME[0]]));
$open = (int)(new craft\db\Query())->from('{{%drafts}}')->where(['canonicalId' => $ids])->count();
$open === 0 || exit("$open of these products have an open draft: apply or delete them first\n");

$removed = 0;
foreach (REMOVE as $slug => $file) {
    foreach ($downloads($product($slug)) as [$tab, $item, $f]) {
        if ($f !== $file) continue;
        $keep = array_values(array_diff($tab->getFieldValue('downloadItems')->status(null)->ids(), [$item->id]));
        $tab->setFieldValue('downloadItems', ['sortOrder' => $keep]);
        Craft::$app->getElements()->saveElement($tab) || fail("$slug downloads", $tab);
        say("  - $slug: “{$item->headingText}” ($file)");
        $removed++;
    }
}

[$slug, $wrong, $url] = REPLACE;
foreach ($downloads($product($slug)) as [$tab, $item, $f]) {
    if ($f !== $wrong) continue;
    $filename = basename($url);
    $asset = Asset::find()->filename($filename)->one();
    if (!$asset) {
        $bytes = @file_get_contents($url, false, stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0 (Arrow Systems staging migration)\r\n", 'timeout' => 120]])) ?: exit("could not download $url\n");
        $tmp = Craft::$app->getPath()->getTempPath() . '/' . uniqid('dl') . '-' . $filename;
        file_put_contents($tmp, $bytes);
        $volume = Craft::$app->getVolumes()->getVolumeByHandle('documents');
        $asset = new Asset(['tempFilePath' => $tmp, 'filename' => $filename, 'volumeId' => $volume->id,
            'newFolderId' => Craft::$app->getAssets()->getRootFolderByVolumeId($volume->id)->id]);
        $asset->setScenario(Asset::SCENARIO_CREATE);
        Craft::$app->getElements()->saveElement($asset) || fail("asset $filename", $asset);
    }
    $item->setFieldValue('downloadFile', [$asset->id]);
    Craft::$app->getElements()->saveElement($item) || fail("$slug brochure", $item);
    say("  ~ $slug: “{$item->headingText}” → {$asset->filename} (as live)");
}

[$slug, $from, $to] = RENAME;
foreach ($downloads($product($slug)) as [$tab, $item, $f]) {
    if (trim($item->headingText) !== $from) continue;
    $item->setFieldValue('headingText', $to);
    Craft::$app->getElements()->saveElement($item) || fail("$slug download title", $item);
    say("  ~ $slug: “{$from}” → “{$to}” (as live)");
}

// self-check: none of the icon-made downloads left, the Lite brochure is live's file
foreach (REMOVE as $s => $file) foreach ($downloads($product($s)) as [, , $f]) $f !== $file || exit("$s still has $file\n");
in_array(basename(REPLACE[2]), array_column($downloads($product(REPLACE[0])), 2), true) || exit(REPLACE[0] . ": live's brochure not linked\n");
say("  = $removed icon-made downloads removed; Lite brochure and Screen Print title as live");
