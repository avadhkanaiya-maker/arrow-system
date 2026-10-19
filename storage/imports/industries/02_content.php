<?php
/**
 * Industries, content: the 12 live industry pages + /industries/ as Craft entries.
 *   Source: cache/<slug>.html (the live pages, fetched 2026-10-14), data/pages.json (WordPress: dates), the design
 *   (Arrowsystem_local/industries/index.html: card photos, summaries, icons; industry-detail.html: the pilot).
 *
 *   Industries Page: the design's texts (small heading, heading, intro, count line, help band) + the live SEO.
 *   Industries, in the live listing order (A–Z); names = the live listing names (D2); Corrugation Printing and
 *   Wallpaper Printer are not on the live listing → Show on Listing off (D5). Cards: the design's photo, summary, icon.
 *   Page Builder:
 *     Pilot flexible-packaging-and-custom-mylar-bags: the design's page (header, 3 recommended products, 2 content
 *     blocks, contact, 3 other industries).
 *     Others, from the live page: H1 → Industry Header (the industry name in red when the H1 contains it), first text →
 *     intro (more paragraphs → a Rich Text block), first photo → header image; "Learn More" buttons to /product/… →
 *     Recommended Products (picked, dynamic); each heading + text (+ photo) → a Content Block (more photos → a Photo
 *     Gallery after it); galleries → Photo Gallery; videos → Video; then Contact Form + Other Industries (as the design).
 *   SEO from the live <head>: title (when not "Name - Arrow Systems, Inc."), description, social image (og:image).
 *   Links in the text to migrated pages (blog, industries, products) become root-relative.
 *
 * Idempotent: existing entries are kept (--update rewrites them from the sources). --only=<slug>. Report: report.md.
 * Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/industries/02_content.php [--update]
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';
require dirname(__DIR__) . '/products/_extract.php';
require __DIR__ . '/_links.php';

use craft\elements\Asset;
use craft\elements\Entry;
use craft\helpers\Assets as AssetsHelper;

const DESIGN = '/home/avadh/Desktop/Arrowsystem_local';
const PILOT = 'flexible-packaging-and-custom-mylar-bags';
const SITE_NAME = 'Arrow Systems, Inc.';
$update = in_array('--update', $argv, true);
$only = null;
foreach ($argv as $a) if (str_starts_with($a, '--only=')) $only = substr($a, 7);
$report = [];
$elements = Craft::$app->getElements();
$entriesService = Craft::$app->getEntries();
$section = $entriesService->getSectionByHandle('industries');
$type = $entriesService->getEntryTypeByHandle('industry');
$wp = [];
foreach (json_decode(file_get_contents(__DIR__ . '/data/pages.json'), true) as $p) $wp[$p['slug']] = $p;

// ---- helpers --------------------------------------------------------------------------------------------------------

/** An image (URL or local file) in Images → industries/, imported once (by file name), with alt text */
function indImage(string $src, ?string $alt = null): ?Asset {
    global $report;
    static $folder, $cache = [];
    $src = html_entity_decode(trim($src));
    if (array_key_exists($src, $cache)) return $cache[$src];
    $volume = Craft::$app->getVolumes()->getVolumeByHandle('images');
    $folder ??= Craft::$app->getAssets()->ensureFolderByFullPathAndVolume('industries', $volume, false);
    $filename = AssetsHelper::prepareAssetName(rawurldecode(basename(parse_url($src, PHP_URL_PATH) ?: $src)));
    $alt = trim(html_entity_decode((string)$alt, ENT_QUOTES | ENT_HTML5));
    $asset = Asset::find()->folderId($folder->id)->filename($filename)->one();
    if (!$asset) {
        $fetch = is_file($src) ? $src : preg_replace_callback('/[^\x21-\x7e]/', fn($m) => rawurlencode($m[0]), $src);
        $data = is_file($src) ? file_get_contents($src)
            : @file_get_contents($fetch, false, stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0 (Arrow Systems staging migration)\r\n", 'timeout' => 90]]));
        if (!$data) { $report[] = "could not download $src"; return $cache[$src] = null; }
        $tmp = Craft::$app->getPath()->getTempPath() . '/' . uniqid('ind') . '-' . $filename;
        file_put_contents($tmp, $data);
        $asset = new Asset(['tempFilePath' => $tmp, 'filename' => $filename, 'volumeId' => $volume->id, 'newFolderId' => $folder->id, 'avoidFilenameConflicts' => true]);
        if ($alt !== '') $asset->alt = $alt;   // with the first save (set later on the same object it isn't saved)
        $asset->setScenario(Asset::SCENARIO_CREATE);
        if (!Craft::$app->getElements()->saveElement($asset)) { $report[] = "could not save $filename"; return $cache[$src] = null; }
    } elseif ($alt !== '' && !$asset->alt) {
        $asset = Asset::find()->id($asset->id)->one();
        $asset->alt = $alt;
        Craft::$app->getElements()->saveElement($asset);
    }
    return $cache[$src] = $asset;
}

/** Rich text for the CKEditor fields (headings 3–4 only): headings → $level, local links, images → Images → industries/ */
function indHtml(string $html, string $level = 'h3'): string {
    $html = exClean($html);
    $html = preg_replace('#<h[1-6]>(.*?)</h[1-6]>#s', "<$level>$1</$level>", $html);
    $html = preg_replace_callback("#<$level>(.*?)</$level>#s", fn($m) => "<$level>" . trim(preg_replace('#</?strong>|<br>#', '', $m[1])) . "</$level>", $html);
    $html = preg_replace_callback('#<img ([^>]*)src="([^"]+)"([^>]*)>#', function ($m) {
        $alt = preg_match('#alt="([^"]*)"#', $m[0], $a) ? $a[1] : '';
        $asset = indImage($m[2], $alt);
        return $asset ? '<img src="{asset:' . $asset->id . ':url||' . $asset->url . '}" alt="' . $alt . '">' : $m[0];
    }, $html);
    return localLinks(trim($html));
}

/** The product entry for a live /product/<slug>/ link (old slugs followed through config/redirects.php) */
function productFor(string $href): ?Entry {
    static $moved;
    if ($moved === null) {
        $moved = [];
        foreach (require Craft::getAlias('@config/redirects.php') as $r) {
            if (is_array($r) && preg_match('#^product/([^/]+)$#', $r['from'] ?? '', $a) && preg_match('#^/product/([^/]+)/$#', $r['to'] ?? '', $b)) $moved[$a[1]] = $b[1];
        }
    }
    if (!preg_match('#arrsys\.com/product/([^/?\#]+)#', $href, $m)) return null;
    return Entry::find()->section('products')->slug($moved[$m[1]] ?? $m[1])->status(null)->one();
}

/** [red] brackets round the industry name when the heading contains it */
function redPart(string $heading, string $name): string {
    $plain = trim(preg_replace('/\s+/', ' ', $heading));
    foreach ([$name, preg_replace('/s\b/', '', $name)] as $try) {
        $pos = mb_stripos($plain, $try);
        if ($try !== '' && $pos !== false) return mb_substr($plain, 0, $pos) . '[' . mb_substr($plain, $pos, mb_strlen($try)) . ']' . mb_substr($plain, $pos + mb_strlen($try));
    }
    return $plain;
}

/** Matrix value from a list of [type, fields] blocks */
function blocksValue(array $blocks): array {
    $entries = []; $order = [];
    foreach (array_values($blocks) as $i => [$t, $fields]) { $k = 'new' . ($i + 1); $entries[$k] = ['type' => $t, 'fields' => $fields]; $order[] = $k; }
    return ['entries' => $entries, 'sortOrder' => $order];
}
$link = fn(string $label, string $url) => ['type' => 'url', 'value' => $url, 'label' => $label];
$entryLink = fn(string $label, Entry $e) => ['type' => 'entry', 'value' => $e->id, 'label' => $label];

/** SEO values from the live <head> */
function liveSeo(string $html, string $name): array {
    $get = fn($re) => preg_match($re, $html, $m) ? html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5) : null;
    $title = $get('#<title>(.*?)</title>#s');
    return ['seoTitle' => $title && $title !== $name . ' - ' . SITE_NAME ? $title : null,
        'seoDescription' => $get('#<meta name="description" content="([^"]*)"#'),
        'socialImage' => $get('#<meta property="og:image" content="([^"]*)"#'),
        'robots' => $get('#<meta name="robots" content="([^"]*)"#')];
}

// ---- design data: listing cards ---------------------------------------------------------------------------------------
$dx = new DOMXPath(exDoc(file_get_contents(DESIGN . '/industries/index.html')));
$design = [];
foreach ($dx->query('//li[contains(@class,"ind-card")]') as $li) {
    $a = $dx->query('.//h2//a', $li)->item(0);
    $img = $dx->query('.//img', $li)->item(0);
    $href = $a->getAttribute('href') === 'industry-detail.html' ? PILOT : trim(parse_url($a->getAttribute('href'), PHP_URL_PATH), '/');
    $design[$href] = ['photo' => realpath(DESIGN . '/industries/' . $img->getAttribute('src')), 'alt' => $img->getAttribute('alt'),
        'summary' => exText($dx->query('.//p[contains(@class,"ind-card__text")]', $li)->item(0)),
        'icon' => preg_replace('/^#i-/', '', $dx->query('.//span[contains(@class,"ind-card__icon")]//use', $li)->item(0)->getAttribute('href'))];
}

// ---- the live listing: names + order -----------------------------------------------------------------------------------
$lx = new DOMXPath(exDoc(file_get_contents(__DIR__ . '/cache/industries.html')));
$order = [];
foreach ($lx->query('//*[@data-elementor-type="wp-page"]//*[@data-widget_type="button.default"]//a') as $a) {
    $order[trim(parse_url($a->getAttribute('href'), PHP_URL_PATH), '/')] = trim(preg_replace('/\s+/', ' ', $a->textContent));
}
$order += ['corrugation-printing' => 'Corrugation Printing', 'wallpaper-printer' => 'Wallpaper Printer'];   // not on the live listing (D5)
$hiddenIcons = ['corrugation-printing' => 'package', 'wallpaper-printer' => 'image'];

// ---- 1. Industries Page ---------------------------------------------------------------------------------------------
say('Industries Page');
$page = Entry::find()->section('industriesPage')->one();
$listingSeo = liveSeo(file_get_contents(__DIR__ . '/cache/industries.html'), 'Industries');
if ($page && ($update || !$page->sectionHeading)) {
    $dh = fn(string $q) => exText($dx->query($q)->item(0));
    $page->title = 'Industries';
    $page->setFieldValues([
        'eyebrow' => $dh('//div[contains(@class,"ind-hero__intro")]/p[contains(@class,"eyebrow")]'),
        'sectionHeading' => 'Industries [We Serve]',
        'sectionLead' => $dh('//p[contains(@class,"page-hero__lead")]'),
        'caption' => $dh('//p[contains(@class,"ind-hero__meta")]/span[2]'),
        'ctaHeading' => $dh('//section[contains(@class,"ind-cta")]//h2'),
        'ctaText' => $dh('//section[contains(@class,"ind-cta")]//p'),
        'seoTitle' => $listingSeo['seoTitle'],
        'seoDescription' => $listingSeo['seoDescription'],
        'socialImage' => $listingSeo['socialImage'] && ($a = indImage($listingSeo['socialImage'], 'Industries')) ? [$a->id] : [],
    ]);
    $elements->saveElement($page) || fail('industries page', $page);
    say('  ~ /industries/: design texts + live SEO');
}

// ---- 2. Industries: create all first (Other Industries / links need them) ---------------------------------------------
say('Industries');
$made = [];
foreach ($order as $slug => $name) {
    $e = Entry::find()->section('industries')->slug($slug)->status(null)->one();
    if (!$e) {
        $e = new Entry(['sectionId' => $section->id, 'typeId' => $type->id, 'slug' => $slug, 'title' => $name]);
        if (isset($wp[$slug])) $e->postDate = new DateTime($wp[$slug]['date']);
        $elements->saveElement($e) || fail("industry $slug", $e);   // new structure entries go to the end: the listing order
        say("  + $slug");
        $made[$slug] = true;
    }
}

// ---- 3. Content ---------------------------------------------------------------------------------------------------------
$contactBlock = fn(string $eyebrow) => ['contactBlock', ['eyebrow' => $eyebrow, 'sectionHeading' => 'Contact With Our Team Now',
    'sectionLead' => 'Tell us your application, materials and run lengths.', 'anchorId' => 'contact']];
$otherBlock = fn(array $ids) => ['otherIndustries', ['eyebrow' => 'Keep exploring', 'sectionHeading' => 'Other [Industries]', 'relatedIndustries' => $ids,
    'ctaLink' => $page ? ['type' => 'entry', 'value' => $page->id, 'label' => 'View all industries'] : null]];

/** The pilot from the design page */
function pilotBlocks(callable $contactBlock, callable $otherBlock, callable $link): array {
    $x = new DOMXPath(exDoc(file_get_contents(DESIGN . '/industries/industry-detail.html')));
    $one = fn(string $q, ?DOMNode $c = null) => $x->query($q, $c)->item(0);
    $hero = $one('//section[contains(@class,"ind-detail-hero")]');
    $h1 = $one('.//h1', $hero);
    $heading = '';
    foreach ($h1->childNodes as $n) $heading .= $n instanceof DOMElement && str_contains($n->getAttribute('class'), 'hl') ? '[' . $n->textContent . ']' : $n->textContent;
    $img = $one('.//figure//img', $hero);
    $buttons = iterator_to_array($x->query('.//div[contains(@class,"btn-row")]/a', $hero));
    $apps = [];
    foreach ($x->query('.//ul[contains(@class,"app-tags")]/li', $hero) as $li) $apps[] = ['text' => exText($li)];
    $blocks = [['industryHeader', [
        'eyebrow' => exText($one('.//p[contains(@class,"eyebrow")]', $hero)),
        'sectionHeading' => trim($heading),
        'sectionLead' => exText($one('.//p[contains(@class,"ind-detail-hero__lead")]', $hero)),
        'listItems' => $apps,
        'mediaImage' => ($a = indImage(realpath(DESIGN . '/industries/' . $img->getAttribute('src')), $img->getAttribute('alt'))) ? [$a->id] : [],
        'primaryCta' => $link(exText($buttons[0]), $buttons[0]->getAttribute('href')),
        'secondaryCta' => $link(exText($buttons[1]), $buttons[1]->getAttribute('href')),
    ]]];
    $eq = $one('//section[contains(@class,"ind-equipment")]');
    $h2 = $one('.//h2', $eq);
    $eqHeading = '';
    foreach ($h2->childNodes as $n) $eqHeading .= $n instanceof DOMElement && str_contains($n->getAttribute('class'), 'hl') ? '[' . $n->textContent . ']' : $n->textContent;
    $products = [];
    $slugs = ['product-detail.html' => 'arrowjet-aqua-330r-lite-2'];
    foreach ($x->query('.//h3//a', $eq) as $a) {
        $href = $a->getAttribute('href');
        $slug = $slugs[basename($href)] ?? (preg_match('#/product/([^/]+)/#', $href, $m) ? $m[1] : null);
        if ($slug && ($p = Entry::find()->section('products')->slug($slug)->one())) $products[] = $p->id;
    }
    $view = $one('.//a[contains(@class,"text-link")]', $eq);
    $blocks[] = ['productShowcase', [
        'eyebrow' => exText($one('.//p[contains(@class,"eyebrow")]', $eq)), 'sectionHeading' => trim($eqHeading),
        'sectionLead' => exText($one('.//p[contains(@class,"ind-equipment__intro")]', $eq)),
        'productSource' => 'picked', 'selectedProducts' => $products,
        'ctaLink' => $link(exText($view), $view->getAttribute('href')), 'anchorId' => $eq->getAttribute('id'),
    ]];
    foreach ($x->query('//article[contains(@class,"content-block")]') as $art) {
        $img = $one('.//img', $art);
        $more = $one('.//a[contains(@class,"text-link")]', $art);
        $blocks[] = ['contentBlock', [
            'eyebrow' => exText($one('.//p[contains(@class,"eyebrow")]', $art)),
            'headingText' => exText($one('.//h2', $art)),
            'richText' => exClean(exInner($one('.//div[contains(@class,"content-block__text")]', $art))),
            'mediaImage' => $img && ($a = indImage(realpath(DESIGN . '/industries/' . $img->getAttribute('src')), $img->getAttribute('alt'))) ? [$a->id] : [],
            'ctaLink' => $more ? $link(exText($more), $more->getAttribute('href')) : null,
            'anchorId' => $art->getAttribute('id'),
        ]];
    }
    $blocks[] = $contactBlock(exText($one('//p[contains(@class,"contact-panel__eyebrow")]')));
    $other = [];
    foreach ($x->query('//section[contains(@class,"ind-more")]//h3//a') as $a) {
        $slug = trim(parse_url($a->getAttribute('href'), PHP_URL_PATH), '/');
        if ($e = Entry::find()->section('industries')->slug($slug)->status(null)->one()) $other[] = $e->id;
    }
    $blocks[] = $otherBlock($other);
    return $blocks;
}

/** The widgets of one live Elementor column (or row) as plain data */
function columnData(DOMXPath $x, DOMElement $col, array &$notes): array {
    $c = ['headings' => [], 'html' => '', 'textLength' => 0, 'images' => [], 'buttons' => [], 'gallery' => [], 'video' => null, 'form' => false];
    foreach ($x->query('.//*[@data-widget_type]', $col) as $w) {
        if ($x->query('ancestor::*[@data-widget_type]', $w)->length) continue;
        $t = explode('.', $w->getAttribute('data-widget_type'))[0];
        $body = $x->query('.//*[contains(@class,"elementor-widget-container")]', $w)->item(0) ?? $w;
        switch ($t) {
            case 'heading':
                if (($text = exText($body)) !== '') {
                    if ($c['html'] === '') $c['headings'][] = $text; else $c['html'] .= '<h4>' . htmlspecialchars($text) . '</h4>';
                }
                break;
            case 'text-editor':
                $c['html'] .= exInner($body); $c['textLength'] += mb_strlen(exText($body)); break;
            case 'image-box':
                $tt = $x->query('.//*[contains(@class,"elementor-image-box-title")]', $body)->item(0);
                $dd = $x->query('.//*[contains(@class,"elementor-image-box-description")]', $body)->item(0);
                $c['html'] .= ($tt ? '<h4>' . htmlspecialchars(exText($tt)) . '</h4>' : '') . ($dd ? '<p>' . htmlspecialchars(exText($dd)) . '</p>' : '');
                $c['textLength'] += mb_strlen(exText($body));
                break;
            case 'image':
                if ($img = $x->query('.//img', $body)->item(0)) $c['images'][] = ['url' => exImageUrl($img), 'alt' => trim($img->getAttribute('alt'))];
                break;
            case 'button':
                if ($a = $x->query('.//a', $body)->item(0)) $c['buttons'][] = ['label' => exText($a), 'href' => $a->getAttribute('href')];
                break;
            case 'image-gallery': case 'gallery': case 'image-carousel':
                $urls = [];
                foreach ($x->query('.//a[@href]', $body) as $a) if (preg_match('/\.(jpe?g|png|webp|gif)$/i', parse_url($a->getAttribute('href'), PHP_URL_PATH) ?? '')) $urls[$a->getAttribute('href')] = trim(($x->query('.//img', $a)->item(0)?->getAttribute('alt')) ?? '');
                if (!$urls) foreach ($x->query('.//img', $body) as $img) $urls[exImageUrl($img)] = trim($img->getAttribute('alt'));
                foreach ($urls as $u => $alt) $c['gallery'][] = ['url' => $u, 'alt' => $alt];
                break;
            case 'video':
                $set = json_decode(html_entity_decode($w->getAttribute('data-settings')), true) ?: [];
                if (!empty($set['youtube_url'])) $c['video'] = $set['youtube_url'];
                break;
            case 'form': $c['form'] = true; break;
            case 'spacer': case 'divider': case 'rating': case 'menu-anchor': break;
            default: $notes[] = "not imported: $t widget “" . mb_substr(exText($body), 0, 50) . '”';
        }
    }
    return $c;
}

/**
 * The other industries from their live page, row by row (live: each Elementor row = text in one column, a photo in
 * the other). Row → Content Block with its photo (or video) beside the text; a heading-only row → the heading of the
 * next block / gallery; product teasers (a "Learn More" to /product/…) → Recommended Products; a button naming a
 * product (e.g. "Gerber EDGE FX") → that product's photo + link; galleries → Photo Gallery; a lone video → Video.
 */
function liveBlocks(string $slug, string $name, string $html, callable $contactBlock, callable $otherBlock, callable $link, array &$notes): array {
    $x = new DOMXPath(exDoc($html));
    $root = $x->query('//*[@data-elementor-type="wp-page"]')->item(0);
    $rowQ = '@data-element_type="section" or @data-element_type="container"';
    $depth = $x->query("ancestor::*[$rowQ]", $root)->length;
    $rows = [];
    foreach ($x->query(".//*[$rowQ]", $root) as $row) {
        if ($x->query("ancestor::*[$rowQ]", $row)->length > $depth) continue;   // top-level rows only
        $cols = [];
        foreach ($x->query('.//*[@data-element_type="column"]', $row) as $col) {
            if ($x->query('ancestor::*[@data-element_type="column"]', $col)->length > $x->query('ancestor::*[@data-element_type="column"]', $row)->length) continue;
            $cols[] = columnData($x, $col, $notes);
        }
        if (!$cols) $cols[] = columnData($x, $row, $notes);
        $cols = array_values(array_filter($cols, fn($c) => $c['headings'] || $c['textLength'] || $c['images'] || $c['buttons'] || $c['gallery'] || $c['video'] || $c['form']));
        if ($cols) $rows[] = $cols;
    }
    $productByLabel = function (string $label): ?Entry {   // "Gerber EDGE FX" → the Gerber Edge FX product
        foreach (Entry::find()->section('products')->status(null)->all() as $p) if (strcasecmp(trim($label), trim($p->title)) === 0) return $p;
        return null;
    };

    $header = null; $lead = []; $headerImage = null; $productIds = []; $body = []; $pending = null;
    foreach ($rows as $cols) {
        $all = ['headings' => [], 'html' => '', 'textLength' => 0, 'images' => [], 'buttons' => [], 'gallery' => [], 'video' => null, 'form' => false];
        // product teasers: a row with a /product/ button. A short row (≤ 600 characters of text; live teasers have 239–545)
        // is replaced by the product card (its photo too); a long one keeps its text and photo (Corrugation, 1,544)
        $rowProducts = []; $rowText = 0;
        foreach ($cols as $i => $c) {
            $rowText += $c['textLength'];
            foreach ($c['buttons'] as $b) if ($p = productFor($b['href'])) $rowProducts[] = $p;
            $cols[$i]['buttons'] = array_values(array_filter($c['buttons'], fn($b) => !productFor($b['href'])));
        }
        if ($rowProducts) {
            foreach ($rowProducts as $p) $productIds[] = $p->id;
            $first = $cols[0]['headings'][0] ?? ($cols[1]['headings'][0] ?? mb_substr(trim(strip_tags($cols[0]['html'] ?? '')), 0, 40));
            if ($rowText > 600) {
                $notes[] = "row “{$first}” ($rowText characters) kept as content; its product → Recommended Products";
            } else {
                $notes[] = "product teaser “{$first}” → Recommended Products: " . implode(', ', array_map(fn($p) => $p->slug, $rowProducts));
                if (!array_filter($cols, fn($c) => $c['gallery'] || $c['video'] || $c['form'])) continue;
                foreach ($cols as $i => $c) $cols[$i] = array_merge($c, ['headings' => [], 'html' => '', 'textLength' => 0, 'images' => []]);
            }
        }
        foreach ($cols as $c) {
            $all['headings'] = array_merge($all['headings'], $c['headings']);
            $all['html'] .= ($all['html'] !== '' && $c['headings'] ? '<h4>' . htmlspecialchars(implode(' ', $c['headings'])) . '</h4>' : '') . $c['html'];
            $all['textLength'] += $c['textLength'];
            foreach (['images', 'buttons', 'gallery'] as $k) $all[$k] = array_merge($all[$k], $c[$k]);
            $all['video'] ??= $c['video'];
            $all['form'] = $all['form'] || $c['form'];
        }
        $headings = $all['headings'];
        $hasText = $all['textLength'] > 0 && !exBlank(exClean($all['html']));

        // the header: the first heading (H1) + the text right under it
        if ($header === null) {
            if (!$headings && !$hasText) continue;
            $header = array_shift($headings) ?? $name;
            foreach ($headings as $h) $lead[] = $h;   // a second heading in the header row (Label Production) → intro
            if ($hasText) foreach (preg_split('#</p>#', exClean($all['html'])) as $p) if (($t = trim(html_entity_decode(strip_tags($p), ENT_QUOTES | ENT_HTML5))) !== '') $lead[] = $t;
            if ($all['images']) $headerImage = array_shift($all['images']);
            continue;
        }
        if (!$lead && !$headings && $hasText && !$all['images'] && !$body) {   // the text under a heading-only header row
            foreach (preg_split('#</p>#', exClean($all['html'])) as $p) if (($t = trim(html_entity_decode(strip_tags($p), ENT_QUOTES | ENT_HTML5))) !== '') $lead[] = $t;
            continue;
        }
        if ($all['form']) { $pending = null; continue; }   // "Contact With Our Team Now" + form → the Contact Form block
        if ($headings && !$hasText && !$all['images'] && !$all['gallery'] && !$all['video'] && !$all['buttons']) { $pending = implode(' ', $headings); continue; }
        if ($all['gallery']) {
            $ids = array_values(array_filter(array_map(fn($g) => indImage($g['url'], $g['alt'])?->id, $all['gallery'])));
            if ($ids) $body[] = ['photoGallery', ['sectionHeading' => $pending ?? ($headings[0] ?? null), 'galleryImages' => $ids]];
            $pending = null;
            if (!$hasText) continue;
        }
        if ($all['video'] && !$hasText) { $body[] = ['video', ['videoUrl' => $all['video'], 'caption' => $pending]]; $pending = null; continue; }
        if (!$hasText && !$all['images']) { if ($headings) $pending = implode(' ', $headings); continue; }

        $heading = $pending ?? array_shift($headings);
        $pending = null;
        $text = ($headings ? '<h4>' . htmlspecialchars(implode(' ', $headings)) . '</h4>' : '') . $all['html'];
        $image = array_shift($all['images']);
        $cta = null;
        foreach ($all['buttons'] as $b) {
            if (!$b['href'] || $b['href'] === '#') continue;
            if ($p = $productByLabel($b['label'])) {   // a button naming a product: its photo + a link to it
                $cta = ['type' => 'entry', 'value' => $p->id, 'label' => $b['label']];
                if (!$image && !$all['video'] && ($pi = $p->mediaImage->one() ?? $p->featuredImage->one())) $image = ['asset' => $pi];
            } else {
                $cta = $link($b['label'] === 'Learn More' ? 'Learn more' : $b['label'], localHref($b['href']));
            }
            break;
        }
        // text-only row right after a block without its own heading: it continues that block (Corrugation)
        $last = count($body) - 1;
        if (!$heading && !$image && !$all['video'] && !$cta && $last >= 0 && $body[$last][0] === 'contentBlock') {
            $body[$last][1]['richText'] .= indHtml($text, 'h4');
            continue;
        }
        $body[] = ['contentBlock', [
            'headingText' => $heading,
            'richText' => $hasText ? indHtml($text, 'h4') : null,
            'mediaImage' => array_filter([$image ? (isset($image['asset']) ? $image['asset']->id : indImage($image['url'], $image['alt'] ?: ($heading ?? $name))?->id) : null]),
            'videoUrl' => $all['video'],
            'ctaLink' => $cta,
        ]];
        foreach ($all['images'] as $extra) $notes[] = 'extra photo ' . basename(parse_url($extra['url'], PHP_URL_PATH)) . ' in “' . ($heading ?? 'a row') . '” → gallery';
        if ($all['images']) {
            $ids = array_values(array_filter(array_map(fn($g) => indImage($g['url'], $g['alt'])?->id, $all['images'])));
            if ($ids) $body[] = ['photoGallery', ['galleryImages' => $ids]];
        }
    }
    if ($slug === 'wallpaper-printer' && ($p = Entry::find()->section('products')->slug('arrowjet-aqua-800m')->one())) {
        $productIds[] = $p->id;   // the live page is the ArrowJet Aqua 800M's wallpaper page
        $notes[] = 'Recommended Products: arrowjet-aqua-800m (the page is about it)';
    }

    // assemble: header, products, the rows, contact, other industries (the design's order)
    $productIds = array_values(array_unique($productIds));
    $img = $headerImage ? indImage($headerImage['url'], $headerImage['alt'] ?: $name) : null;
    if (!$img) $notes[] = 'header image: the industry\'s Card Photo (live has a background banner)';
    // the header keeps about 2 paragraphs (≤ ~450 characters); a longer live intro continues in a text block (2 columns)
    $keep = []; $chars = 0;
    foreach ($lead as $i => $p) { if ($i > 0 && $chars + mb_strlen($p) > 450) break; $keep[] = $p; $chars += mb_strlen($p); }
    $restLead = array_slice($lead, count($keep));
    if ($restLead) array_unshift($body, ['contentBlock', ['richText' => '<p>' . implode('</p><p>', array_map('htmlspecialchars', $restLead)) . '</p>']]);
    $blocks = [['industryHeader', [
        'eyebrow' => $name,
        'sectionHeading' => redPart($header ?? $name, $name),
        'sectionLead' => implode("\n\n", $keep) ?: null,
        'mediaImage' => $img ? [$img->id] : [],
        'primaryCta' => $link('Talk to a Specialist', '#contact'),
        'secondaryCta' => $productIds ? $link('Recommended Equipment', '#equipment') : null,
    ]]];
    if ($productIds) $blocks[] = ['productShowcase', ['eyebrow' => 'Recommended equipment', 'sectionHeading' => 'Equipment for [' . $name . ']',
        'productSource' => 'picked', 'selectedProducts' => $productIds, 'anchorId' => 'equipment']];
    $blocks = array_merge($blocks, $body);
    $blocks[] = $contactBlock($name);
    $blocks[] = $otherBlock([]);
    return $blocks;
}

say('Pages');
$rows = [];
foreach ($order as $slug => $name) {
    if ($only && $slug !== $only) continue;
    $e = Entry::find()->section('industries')->slug($slug)->status(null)->one();
    if (!isset($made[$slug]) && !$update && $e->industryBuilder->status(null)->exists()) { say("  = $slug"); continue; }
    $html = file_get_contents(__DIR__ . "/cache/$slug.html");
    $notes = [];
    $blocks = $slug === PILOT ? pilotBlocks($contactBlock, $otherBlock, $link) : liveBlocks($slug, $name, $html, $contactBlock, $otherBlock, $link, $notes);
    if ($slug === PILOT) $notes[] = 'pilot: the design page';
    $d = $design[$slug] ?? null;
    $seo = liveSeo($html, $name);
    $social = $seo['socialImage'] ? indImage($seo['socialImage'], $name) : null;
    $e->title = $name;
    $e->setFieldValues([
        'featuredImage' => $d && ($a = indImage($d['photo'], $d['alt'])) ? [$a->id] : [],
        'cardTagline' => $d['summary'] ?? null,
        'menuIcon' => $d['icon'] ?? ($hiddenIcons[$slug] ?? ''),
        'showOnListing' => !isset($hiddenIcons[$slug]),
        'industryBuilder' => blocksValue($blocks),
        'seoTitle' => $seo['seoTitle'],
        'seoDescription' => $seo['seoDescription'],
        'socialImage' => $social ? [$social->id] : [],
    ]);
    if ($seo['robots'] !== 'follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large') $notes[] = "live robots: {$seo['robots']}";
    $elements->saveElement($e) || fail("industry $slug", $e);
    $types = array_count_values(array_map(fn($b) => $b[0], $blocks));
    say(sprintf('  ~ %-46s %s', $slug, implode(', ', array_map(fn($t, $n) => "{$t}×{$n}", array_keys($types), $types))));
    $rows[] = "| `$slug` | {$name} | " . implode(', ', array_map(fn($t, $n) => "{$t} × {$n}", array_keys($types), $types)) . ' | ' . implode('; ', $notes) . ' |';
}

if (!$only) {
    $out = ['# Industries import report', '', 'Run: ' . date('Y-m-d H:i'), '', '| Page | Name | Blocks | Notes |', '|---|---|---|---|', ...$rows, ''];
    if ($report) $out = array_merge($out, ['## Problems', '', ...array_map(fn($r) => "- $r", $report)]);
    file_put_contents(__DIR__ . '/report.md', implode("\n", $out) . "\n");
    say('report: storage/imports/industries/report.md' . ($report ? ' (' . count($report) . ' problems)' : ''));
} elseif ($report) foreach ($report as $r) say("  ! $r");
