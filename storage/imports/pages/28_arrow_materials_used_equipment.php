<?php
/**
 * Pages: /arrow-materials/ and /used-equipments/, from the live pages (read 2026-10-27; no design file). Text, tables
 * and links word for word, read from the live HTML when the script runs. General blocks only:
 *   /arrow-materials/ (https://arrsys.com/arrow-materials/)
 *     Page Header  "ArrowJet WaterBased InkJet Receptive [Media]", live's 2 intro paragraphs; button Request a Quote
 *     Tabs         live's first tab set (AD5050A High Resin – FAR Foil … Specialty Laminates and Foils)
 *     Heading      Arrow Material Specifications
 *     Tabs × 6     live's material tab sets, one Tabs block each (as live: 4 materials a set)
 *     FAQ × 2      live's two "ARR-… Technical Information" toggles, each a table
 *     Each PDF (live: a PDF icon + a name / "Brochure") is downloaded to Documents and linked once at the tab's end.
 *   /used-equipments/ (https://arrsys.com/used-equipments/)
 *     Page Header  "Used [Equipment]"
 *     Card Grid    the 4 machines: image, name, live's line, price + a Request Quote link (the card text's link)
 *   Newsletter   last on both, as every page (the newsletter band above the footer, as live)
 *   SEO          the live title and description (social image: used equipment's og:image)
 * Then the menu items and link fields pointing at the live pages link here. Skips a page that exists. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/pages/28_arrow_materials_used_equipment.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\db\Query;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\fields\Link;
use verbb\navigation\elements\Node;

function live(string $url): DOMXPath {
    $html = @file_get_contents($url, false, stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0 (Arrow Systems staging migration)\r\n", 'timeout' => 90]]))
        ?: exit("could not read $url\n");
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML('<?xml encoding="utf-8">' . $html);
    return new DOMXPath($doc);
}

/** A file from a URL into a volume (pages/ folder for images, root for documents), added once (by file name) */
function fileAsset(string $src, string $volumeHandle, string $alt = ''): Asset {
    $volume = Craft::$app->getVolumes()->getVolumeByHandle($volumeHandle);
    $folder = $volumeHandle === 'images' ? Craft::$app->getAssets()->ensureFolderByFullPathAndVolume('pages', $volume, false) : Craft::$app->getAssets()->getRootFolderByVolumeId($volume->id);
    $filename = rawurldecode(basename(parse_url($src, PHP_URL_PATH)));
    if ($asset = Asset::find()->volumeId($volume->id)->filename($filename)->one()) return $asset;
    $bytes = @file_get_contents($src, false, stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0 (Arrow Systems staging migration)\r\n", 'timeout' => 120]]))
        ?: exit("could not download $src\n");
    $tmp = Craft::$app->getPath()->getTempPath() . '/' . uniqid('page') . '-' . $filename;
    file_put_contents($tmp, $bytes);
    $asset = new Asset(['tempFilePath' => $tmp, 'filename' => $filename, 'volumeId' => $volume->id, 'newFolderId' => $folder->id]);
    $asset->alt = $alt ?: null;
    $asset->setScenario(Asset::SCENARIO_CREATE);
    Craft::$app->getElements()->saveElement($asset) || fail("asset $filename", $asset);
    return $asset;
}

/** Live HTML → rich text: no Elementor attributes or images; PDF links → Documents, one link each at the end */
function cleanHtml(DOMNode $node): string {
    $doc = new DOMDocument();
    $doc->loadHTML('<?xml encoding="utf-8"><div id="__c">' . $node->ownerDocument->saveHTML($node) . '</div>');
    $x = new DOMXPath($doc);
    $root = $x->query('//*[@id="__c"]/*')->item(0);
    $pdfs = [];
    foreach (iterator_to_array($x->query('.//a[contains(@href, ".pdf")]', $root)) as $a) {
        $label = trim(preg_replace('/\s+/u', ' ', $a->textContent));
        $pdfs[$a->getAttribute('href')] ??= $label;
        $pdfs[$a->getAttribute('href')] = $pdfs[$a->getAttribute('href')] ?: $label;
        $a->parentNode->removeChild($a);
    }
    foreach (iterator_to_array($x->query('.//img', $root)) as $img) $img->parentNode->removeChild($img);
    foreach (iterator_to_array($x->query('.//p', $root)) as $p) {   // the paragraphs that held the PDF links ("Brochure" left over) or nothing
        if (preg_match('/^(brochure)?$/i', trim(str_replace("\u{a0}", ' ', $p->textContent)))) $p->parentNode->removeChild($p);
    }
    foreach ($x->query('.//*', $root) as $el) {
        foreach (iterator_to_array($el->attributes) as $attr) if (!in_array($attr->name, ['href', 'colspan', 'rowspan'], true)) $el->removeAttribute($attr->name);
    }
    $html = '';
    foreach ($root->childNodes as $c) $html .= $doc->saveHTML($c);
    foreach ($pdfs as $href => $label) {
        $pdf = fileAsset($href, 'documents');
        $html .= '<p><a href="{asset:' . $pdf->id . ':url||' . $pdf->url . '}">' . htmlspecialchars($label ?: 'Brochure') . '</a></p>';
    }
    return trim($html);
}

$sections = Craft::$app->getEntries();
$pb = Craft::$app->getFields()->getFieldByHandle('pageBuilder')->id;
$quote = Entry::find()->section('pages')->slug('request-quote')->one();
$quoteLink = fn(string $label) => $quote ? ['type' => 'entry', 'value' => $quote->id, 'label' => $label] : ['type' => 'url', 'value' => 'https://arrsys.com/request-quote/', 'label' => $label];
$quoteHref = $quote ? '{entry:' . $quote->id . '@' . $quote->siteId . ':url||' . $quote->url . '}' : 'https://arrsys.com/request-quote/';
$newPage = function (string $title, string $slug, array $fields) use ($sections, $pb): Entry {
    $page = new Entry(['sectionId' => $sections->getSectionByHandle('pages')->id, 'typeId' => $sections->getEntryTypeByHandle('page')->id, 'title' => $title, 'slug' => $slug]);
    $page->setFieldValues($fields);
    Craft::$app->getElements()->saveElement($page) || fail("page $slug", $page);
    $page->uri === $slug || exit("the page got /{$page->uri}/: another entry uses /$slug/\n");
    $want = count($fields['pageBuilder']['sortOrder']);
    $saved = (int)Entry::find()->ownerId($page->id)->fieldId($pb)->status(null)->count();
    $saved === $want || exit("$slug: $saved of $want blocks saved\n");
    return $page;
};
$meta = fn(DOMXPath $x, string $q) => trim($x->query($q)->item(0)?->getAttribute('content') ?? '');

// ---- /arrow-materials/
$materials = Entry::find()->section('pages')->slug('arrow-materials')->status(null)->one();
if (!$materials) {
    $x = live('https://arrsys.com/arrow-materials/');
    $body = $x->query('//*[@data-elementor-type="wp-page"]')->item(0) ?? exit("arrow-materials: no page content on live\n");
    $headings = $x->query('.//*[contains(@class,"elementor-widget-heading")]', $body);
    $headings->length === 2 || exit("arrow-materials: expected 2 headings on live, found {$headings->length}\n");
    $intro = [];
    foreach ($x->query('.//*[contains(@class,"elementor-widget-text-editor")]//p', $body) as $p) if ($t = trim($p->textContent)) $intro[] = $t;

    $blocks = ['sortOrder' => [], 'entries' => []];
    $add = function (array $block) use (&$blocks) { $k = 'b' . (count($blocks['sortOrder']) + 1); $blocks['sortOrder'][] = $k; $blocks['entries'][$k] = $block; };
    $add(['type' => 'industryHeader', 'fields' => ['eyebrow' => 'Label Materials', 'sectionHeading' => preg_replace('/\s+(\S+)$/', ' [$1]', trim($headings->item(0)->textContent)),
        'sectionLead' => implode("\n\n", $intro), 'primaryCta' => $quoteLink('Request a Quote')]]);
    $tabCount = $faqCount = 0;
    foreach ($x->query('.//*[contains(@class,"elementor-widget-tabs") or contains(@class,"elementor-widget-toggle")]', $body) as $i => $w) {
        if ($i === 1) $add(['type' => 'heading', 'fields' => ['headingText' => trim($headings->item(1)->textContent), 'headingLevel' => 'h2']]);
        $isTabs = str_contains($w->getAttribute('class'), 'elementor-widget-tabs');
        $items = ['sortOrder' => [], 'entries' => []];
        $titles = $x->query($isTabs ? './/*[contains(@class,"elementor-tab-desktop-title")]' : './/*[contains(@class,"elementor-tab-title")]', $w);
        foreach ($titles as $j => $t) {
            $content = $x->query('.//*[@id="' . $t->getAttribute('aria-controls') . '"]', $w)->item(0) ?? exit("arrow-materials: no content for “{$t->textContent}”\n");
            $items['sortOrder'][] = "i$j";
            $items['entries']["i$j"] = ['type' => $isTabs ? 'textTab' : 'faqItem', 'fields' => ['headingText' => trim(preg_replace('/\s+/u', ' ', $t->textContent)), 'richText' => cleanHtml($content)]];
        }
        $isTabs ? $tabCount += count($items['sortOrder']) : $faqCount += count($items['sortOrder']);
        $add($isTabs ? ['type' => 'tabsBlock', 'fields' => ['productTabs' => $items, 'spacingTop' => $i === 0 ? 'small' : 'none', 'spacingBottom' => 'small']]
            : ['type' => 'faq', 'fields' => ['faqItems' => $items]]);
    }
    $add(['type' => 'newsletter']);
    $materials = $newPage('Arrow Materials', 'arrow-materials', [
        'seoTitle' => trim($x->query('//title')->item(0)->textContent),
        'seoDescription' => $meta($x, '//meta[@name="description"]'),
        'cardTagline' => 'Roll label materials, foils, void materials, laminates and spec sheets for digital label printing.',
        'pageBuilder' => $blocks,
    ]);
    $saved = 0;
    foreach (Entry::find()->ownerId($materials->id)->fieldId($pb)->type(['tabsBlock', 'faq'])->status(null)->all() as $b) $saved += (int)$b->getFieldValue($b->type->handle === 'faq' ? 'faqItems' : 'productTabs')->status(null)->count();
    $saved === $tabCount + $faqCount || exit("arrow-materials: $saved of " . ($tabCount + $faqCount) . " tabs / FAQ items saved\n");
    say("  + page /{$materials->uri}/ (Page Header, Tabs: $tabCount materials, FAQ: $faqCount technical information tables)");
}

// ---- /used-equipments/
$used = Entry::find()->section('pages')->slug('used-equipments')->status(null)->one();
if (!$used) {
    $x = live('https://arrsys.com/used-equipments/');
    $body = $x->query('//*[@data-elementor-type="wp-page"]')->item(0) ?? exit("used-equipments: no page content on live\n");
    $cards = ['sortOrder' => [], 'entries' => []];
    // each machine: its image, then its name (h2/h3), its line (text), its price (h5)
    foreach ($x->query('.//img', $body) as $i => $img) {
        $after = fn(string $q) => $x->query("following::*[$q][1]", $img)->item(0);
        $name = $after('contains(@class,"elementor-widget-heading")') ?? exit("used-equipments: no name after image $i\n");
        $line = $x->query('following::*[contains(@class,"elementor-widget-text-editor")][1]', $name)->item(0);
        $price = $x->query('following::h5[1]', $name)->item(0) ?? exit("used-equipments: no price after image $i\n");
        $photo = fileAsset($img->getAttribute('src'), 'images', $img->getAttribute('alt'));
        $cards['sortOrder'][] = "c$i";
        $cards['entries']["c$i"] = ['type' => 'card', 'fields' => ['headingText' => trim($name->textContent), 'subheading' => trim($line?->textContent ?? ''), 'mediaImage' => [$photo->id],
            'richText' => '<p><strong>' . htmlspecialchars(trim($price->textContent)) . "</strong></p><p><a href=\"$quoteHref\">Request Quote</a></p>"]];
    }
    count($cards['sortOrder']) === 4 || exit('used-equipments: expected 4 machines on live, found ' . count($cards['sortOrder']) . "\n");
    $social = fileAsset($meta($x, '//meta[@property="og:image"]'), 'images');
    $used = $newPage('Used Equipment', 'used-equipments', [
        'seoTitle' => trim($x->query('//title')->item(0)->textContent),
        'seoDescription' => $meta($x, '//meta[@name="description"]'),
        'socialImage' => [$social->id],
        'cardTagline' => 'Pre-owned label presses, finishers and printers from Arrow Systems.',
        'pageBuilder' => ['sortOrder' => ['b1', 'b2', 'b3'], 'entries' => [
            'b1' => ['type' => 'industryHeader', 'fields' => ['eyebrow' => 'Used Equipment', 'sectionHeading' => 'Used [Equipment]']],
            'b2' => ['type' => 'cardGrid', 'fields' => ['cardColumns' => '4', 'cardItems' => $cards, 'spacingTop' => 'small']],
            'b3' => ['type' => 'newsletter'],
        ]],
    ]);
    say("  + page /{$used->uri}/ (Page Header, Card Grid: 4 machines)");
}

foreach (['https://arrsys.com/arrow-materials/' => $materials, 'https://arrsys.com/used-equipments/' => $used] as $liveUrl => $page) {
    // menu items pointing at the live page → this page
    foreach (Node::find()->status(null)->all() as $n) {
        if (rtrim((string)$n->url, '/') . '/' !== $liveUrl) continue;
        $n->type = Entry::class;
        $n->elementId = $page->id;
        $n->url = null;
        Craft::$app->getElements()->saveElement($n) || fail("menu node {$n->id}", $n);
        say("  ~ menu {$n->menu->handle} “{$n->title}” → /{$page->uri}/");
    }
    // link fields (block buttons, Site Settings) pointing at the live page → this page, label kept; rich text links stay
    $ids = (new Query())->select('es.elementId')->from(['es' => '{{%elements_sites}}'])
        ->innerJoin(['e' => '{{%elements}}'], '[[e.id]] = [[es.elementId]]')
        ->where(['e.revisionId' => null, 'e.draftId' => null, 'e.dateDeleted' => null])
        ->andWhere(['like', 'es.content', str_replace('https://', '', rtrim($liveUrl, '/'))])->column();
    foreach ($ids as $id) {
        $el = Craft::$app->getElements()->getElementById($id, null, null, ['status' => null]);
        if (!$el || !$el->getFieldLayout()) continue;
        $changed = [];
        foreach ($el->getFieldLayout()->getCustomFields() as $f) {
            if (!$f instanceof Link) continue;
            $v = $el->getFieldValue($f->handle);
            if (!$v || rtrim((string)$v->url, '/') . '/' !== $liveUrl) continue;
            $el->setFieldValue($f->handle, ['type' => 'entry', 'value' => $page->id, 'label' => $v->label, 'target' => $v->target]);
            $changed[] = $f->handle;
        }
        if (!$changed) continue;
        Craft::$app->getElements()->saveElement($el) || fail("element $id", $el);
        say('  ~ ' . ($el instanceof Entry ? "{$el->type->handle} $id" : $el::displayName() . ' ' . ($el->title ?? $el->name ?? $id)) . ': ' . implode(', ', $changed) . " → /{$page->uri}/");
    }
}

// self-check: both pages load with their content
foreach (['arrow-materials' => 'ARR-110 Technical Information', 'used-equipments' => 'Anyjet I/2800 MLP'] as $slug => $text) {
    $html = @file_get_contents("http://localhost:8081/$slug/") ?: exit("/$slug/ does not load\n");
    str_contains(html_entity_decode($html), $text) || exit("/$slug/: “{$text}” not on the page\n");
}
say('  = /arrow-materials/ and /used-equipments/ load with their content');
