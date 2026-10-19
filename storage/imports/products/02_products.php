<?php
/**
 * Products, content: the 104 live product pages (arrsys.com/product/…) as Products entries.
 *   Source: data/store-products.json (WooCommerce Store API: name, slug, SKU, categories, image, description) +
 *   cache/<slug>.html (the live pages, read by _extract.php) + data/practice-brands.json (brands from the practice
 *   project) + data/design-cards.json (the 12 cards of the design listing: tagline, key specs, card photo).
 *   The 9 products that 301 on live are not imported (config/redirects.php sends their URLs on, as live).
 *
 *   Pilot arrowjet-aqua-330r-lite-2: content from the design (digital-label-printers/product-detail.html), incl. its 3 related printers.
 *   Others, from the live page: H1 → name, first image → Main Image, next heading → Headline, text before the tabs →
 *   Intro (its first bullet list → Highlights), the tabs → Tabs:
 *     PDF links only            → Downloads tab (PDFs into the Documents volume)
 *     2-column table(s) only    → Specifications tab (rows: label / value)
 *     anything else             → Text tab (headings → h4; 3-column tables stay tables)
 *     accordion (FAQ)           → one Text tab "FAQ"; text after the tabs → added to the first Text tab
 *     image boxes               → Highlights (short key figures) or a list in the Intro
 *   Video widget → Video URL. SEO from the live <head>: title (when not the default), description, robots.
 *   Video and tabs are written as Page Builder blocks (04_page_builder.php): a Video block, then a Tabs block.
 *   --update rewrites a product's Page Builder from the live page: editors' builder changes on it are replaced.
 *   The 6 pages not built with Elementor: name, image and the WooCommerce description.
 *
 * Idempotent: existing products are skipped (--update re-imports them). --only=<slug> for one product, --dry to only
 * print what would happen. Report: storage/imports/products/report.md. Back up first. Run from the project root:
 *   /opt/lampp/bin/php storage/imports/products/02_products.php [--update] [--only=slug] [--dry]
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';
require __DIR__ . '/_extract.php';

use craft\elements\Asset;
use craft\elements\Entry;
use craft\helpers\Assets as AssetsHelper;

const DESIGN = '/home/avadh/Desktop/Arrowsystem_local';
const PILOT = 'arrowjet-aqua-330r-lite-2';
const SITE_NAME = 'Arrow Systems, Inc.';
$update = in_array('--update', $argv, true);
$dry = in_array('--dry', $argv, true);
$only = null;
foreach ($argv as $a) if (str_starts_with($a, '--only=')) $only = substr($a, 7);
$report = [];
$elements = Craft::$app->getElements();

$store = json_decode(file_get_contents(__DIR__ . '/data/store-products.json'), true);
$brands = json_decode(file_get_contents(__DIR__ . '/data/practice-brands.json'), true);
$cards = [];
$cardSlug = ['product-detail.html' => PILOT, 'arrowjet-bolt' => 'arrow-bolt-330r-hybrid-pro-m',   // design hrefs → live slugs
    'arrowjet-hybrid-m-pro-plus' => 'arrowjet-aqua-330r-hybrid-pro-plus-m', 'arrowjet-nova-250' => 'arrowjet-nova-250-plus'];
foreach (json_decode(file_get_contents(__DIR__ . '/data/design-cards.json'), true) as $c) $cards[$cardSlug[$c['slug']] ?? $c['slug']] = $c;
$section = Craft::$app->getEntries()->getSectionByHandle('products');
$type = Craft::$app->getEntries()->getEntryTypeByHandle('product');
$categoryIds = [];
foreach (Entry::find()->section('productCategories')->status(null)->all() as $c) $categoryIds[$c->slug] = $c->id;

// ---- helpers --------------------------------------------------------------------------------------------------------

/** An asset for a URL or local file, imported into <volume>/<folder> once (matched by file name); alt for images */
function importFile(string $src, string $volumeHandle, string $folderPath, ?string $alt = null, ?string $title = null): ?Asset {
    global $report;
    static $cache = [];
    $src = html_entity_decode(trim($src));
    $key = "$volumeHandle/$folderPath/$src";
    if (array_key_exists($key, $cache)) return $cache[$key];
    $volume = Craft::$app->getVolumes()->getVolumeByHandle($volumeHandle);
    $folder = $folderPath === '' ? Craft::$app->getAssets()->getRootFolderByVolumeId($volume->id)
        : Craft::$app->getAssets()->ensureFolderByFullPathAndVolume($folderPath, $volume, false);
    $filename = AssetsHelper::prepareAssetName(rawurldecode(basename(parse_url($src, PHP_URL_PATH) ?: $src)));
    $asset = Asset::find()->folderId($folder->id)->filename($filename)->one();
    if (!$asset) {
        $fetch = is_file($src) ? $src : preg_replace_callback('/[^\x21-\x7e]/', fn($m) => rawurlencode($m[0]), $src);
        $data = is_file($src) ? file_get_contents($src)
            : @file_get_contents($fetch, false, stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0 (Arrow Systems staging migration)\r\n", 'timeout' => 90]]));
        if (!$data) { $report[] = "could not download $src"; return $cache[$key] = null; }
        $tmp = Craft::$app->getPath()->getTempPath() . '/' . uniqid('prd') . '-' . $filename;
        file_put_contents($tmp, $data);
        // alt goes in with the first save (set afterwards on this same object, it isn't saved)
        $asset = new Asset(['tempFilePath' => $tmp, 'filename' => $filename, 'volumeId' => $volume->id, 'newFolderId' => $folder->id, 'avoidFilenameConflicts' => true]);
        if ($title) $asset->title = $title;
        $newAlt = trim(html_entity_decode((string)$alt, ENT_QUOTES | ENT_HTML5));
        if ($newAlt !== '' && preg_match('/\.(jpe?g|png|webp|gif|svg|avif)$/i', $filename)) $asset->alt = $newAlt;
        $asset->setScenario(Asset::SCENARIO_CREATE);
        if (!Craft::$app->getElements()->saveElement($asset)) { $report[] = "could not save $filename: " . json_encode($asset->getErrors()); return $cache[$key] = null; }
    }
    $alt = trim(html_entity_decode((string)$alt, ENT_QUOTES | ENT_HTML5));
    if ($alt !== '' && $asset->kind === Asset::KIND_IMAGE && !$asset->alt) {
        $asset = Asset::find()->id($asset->id)->one();
        $asset->alt = $alt;
        Craft::$app->getElements()->saveElement($asset);
    }
    return $cache[$key] = $asset;
}

/** Rich text for CKEditor (headings 3–4 only): headings → $level, images → imported assets (Images → products/) */
function richHtml(string $html, string $level): string {
    $html = preg_replace('#<h[1-6]>(.*?)</h[1-6]>#s', "<$level>$1</$level>", $html);
    $html = preg_replace_callback("#<$level>(.*?)</$level>#s", fn($m) => "<$level>" . trim(preg_replace('#</?strong>|<br>#', '', $m[1])) . "</$level>", $html);
    $html = preg_replace_callback('#<img ([^>]*)src="([^"]+)"([^>]*)>#', function ($m) {
        $alt = preg_match('#alt="([^"]*)"#', $m[0], $a) ? $a[1] : '';
        $asset = importFile($m[2], 'images', 'products', $alt);
        return $asset ? '<img src="{asset:' . $asset->id . ':url||' . $asset->url . '}" alt="' . $alt . '">' : $m[0];
    }, $html);
    return trim(str_replace('</ul><ul>', '', $html));
}

/** Rows of a 2-column table (header rows like "Specification | Details" dropped, a row with an empty label joins the row
 *  above), or null when not such a table */
function tableRows(DOMElement $table): ?array {
    $rows = [];
    foreach ((new DOMXPath($table->ownerDocument))->query('.//tr', $table) as $i => $tr) {
        $cells = [];
        foreach ($tr->childNodes as $c) if ($c instanceof DOMElement && in_array($c->nodeName, ['td', 'th'], true)) $cells[] = exText($c);
        if (count($cells) !== 2) return null;
        if ($cells[0] === '' && $cells[1] === '') continue;
        if ($cells[0] === '' && $rows) { $rows[count($rows) - 1]['description'] .= '; ' . $cells[1]; continue; }   // continuation row
        $isHeader = $tr->getElementsByTagName('th')->length || ($i === 0 && preg_match('/^(specifications?|features?|parameters?|items?|properties)$/i', $cells[0]));
        if ($isHeader) continue;
        $rows[] = ['term' => $cells[0], 'description' => $cells[1]];
    }
    return $rows ?: null;
}

/** Live tab → Tabs block (Text / Specifications / Downloads) */
function tabBlock(string $title, string $html, string $productTitle): array {
    $x = new DOMXPath(exDoc('<div id="__t">' . $html . '</div>'));
    $root = $x->query('//*[@id="__t"]')->item(0);
    // Downloads: the tab is (almost) only PDF links
    $pdfs = [];
    foreach ($x->query('.//a[@href]', $root) as $a) {
        if (preg_match('/\.pdf(\?|#|$)/i', $a->getAttribute('href'))) $pdfs[$a->getAttribute('href')] ??= exText($a);
    }
    $rest = exText($root);
    foreach ($pdfs as $label) $rest = str_replace($label, '', $rest);
    if ($pdfs && mb_strlen(trim(preg_replace('/\b(download|brochure|here|click)\b|[:|–-]/i', '', $rest))) < 120) {
        $items = [];
        foreach ($pdfs as $href => $label) {
            $label = trim(preg_replace('/\s*\(?\s*download\s*\)?\s*$|^\s*download\s*:?\s*/i', '', $label));
            $file = rawurldecode(basename(parse_url($href, PHP_URL_PATH)));
            if ($label === '') $label = $productTitle . ' ' . ($title ?: 'Brochure');
            $spanish = preg_match('/spanish|espa[nñ]ol/i', $label . ' ' . $file);
            $items[] = ['label' => $label, 'href' => $href, 'note' => $spanish ? 'Español' : 'English'];
        }
        return ['downloads', $title, $items];
    }
    // Specifications: only (an optional heading and) 2-column tables
    $tables = iterator_to_array($x->query('.//table', $root));
    if ($tables) {
        $rows = [];
        foreach ($tables as $t) { $r = tableRows($t); if ($r === null) { $rows = null; break; } $rows = array_merge($rows, $r); }
        if ($rows) {
            foreach ($tables as $t) $t->parentNode->removeChild($t);
            $left = exText($root);
            if (mb_strlen($left) < 80) return ['specs', $title, $rows];
        }
    }
    return ['text', $title, richHtml($html, 'h4')];
}

/** Matrix value for the Tabs field (inside the Page Builder's Tabs block) */
function tabsValue(array $tabs, string $productTitle): array {
    $entries = []; $order = [];
    foreach ($tabs as $i => [$kind, $title, $content]) {
        $key = 'new' . ($i + 1);
        $order[] = $key;
        if ($kind === 'text') {
            $entries[$key] = ['type' => 'textTab', 'fields' => ['headingText' => $title, 'richText' => $content]];
        } elseif ($kind === 'specs') {
            $entries[$key] = ['type' => 'specsTab', 'fields' => ['headingText' => $title, 'specItems' => $content]];
        } else {
            $items = []; $itemOrder = [];
            foreach ($content as $j => $d) {
                $file = importFile($d['href'], 'documents', '', null, $d['label']);
                if (!$file) continue;
                $k = 'new' . ($j + 1);
                $itemOrder[] = $k;
                $items[$k] = ['type' => 'downloadItem', 'fields' => ['headingText' => $d['label'], 'downloadFile' => [$file->id], 'caption' => $d['note']]];
            }
            if (!$items) { array_pop($order); continue; }
            $entries[$key] = ['type' => 'downloadsTab', 'fields' => ['headingText' => $title, 'downloadItems' => ['entries' => $items, 'sortOrder' => $itemOrder]]];
        }
    }
    return ['entries' => $entries, 'sortOrder' => $order];
}

/** Page Builder value (04_page_builder.php, 06_new_design_content.php): a Video block (when there is a video), the Stats
    Strip, a Tabs block (when there are tabs), What You Can Print (automatic industries) */
function builderValue(?string $video, ?Asset $poster, ?string $caption, array $tabs): array {
    $blocks = []; $order = [];
    if ($video) {
        $blocks['new1'] = ['type' => 'productVideo', 'fields' => ['videoUrl' => $video, 'videoPoster' => $poster ? [$poster->id] : [], 'videoCaption' => $caption]];
        $order[] = 'new1';
    }
    $blocks['new3'] = ['type' => 'statsStrip', 'fields' => []];
    $order[] = 'new3';
    if ($tabs['entries']) {
        $blocks['new2'] = ['type' => 'tabsBlock', 'fields' => ['productTabs' => $tabs]];
        $order[] = 'new2';
    }
    $blocks['new4'] = ['type' => 'productApplications', 'fields' => ['eyebrow' => 'Applications', 'sectionHeading' => 'What You [Can Print]',
        'sectionLead' => 'The industries the {name} is built to serve.']];
    $order[] = 'new4';
    return ['entries' => $blocks, 'sortOrder' => $order];
}

/** SEO values from the live <head> (Rank Math) */
function liveSeo(string $html, string $title): array {
    $get = fn($re) => preg_match($re, $html, $m) ? html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5) : null;
    $pageTitle = $get('#<title>(.*?)</title>#s');
    $robots = $get('#<meta name="robots" content="([^"]*)"#');
    $default = 'follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large';
    $canonical = $get('#<link rel="canonical" href="([^"]*)"#');
    return [
        'seoTitle' => $pageTitle && $pageTitle !== $title . ' - ' . SITE_NAME ? $pageTitle : null,
        'seoDescription' => $get('#<meta name="description" content="([^"]*)"#'),
        'seoRobots' => $robots && $robots !== $default ? $robots : null,
        'canonical' => $canonical,
        'published' => $get('#"datePublished":"([^"]+)"#'),
    ];
}

// ---- the pilot: content from the design -----------------------------------------------------------------------------
function designPilot(): array {
    $x = new DOMXPath(exDoc(file_get_contents(DESIGN . '/digital-label-printers/product-detail.html')));
    $one = fn(string $q, ?DOMNode $ctx = null) => $x->query($q, $ctx)->item(0);
    $img = $one('//figure[contains(@class,"pd-media")]//img');
    $poster = $one('//aside[contains(@class,"pd-video")]//img');
    $tabs = [];
    foreach ($x->query('//div[@data-panel]') as $panel) {
        $title = exText($one('.//h3', $panel));
        if ($dl = $one('.//dl', $panel)) {
            $rows = [];
            foreach ($x->query('./div', $dl) as $row) $rows[] = ['term' => exText($one('dt', $row)), 'description' => exText($one('dd', $row))];
            $tabs[] = ['specs', $title, $rows];
        } elseif ($x->query('.//ul[contains(@class,"downloads")]', $panel)->length) {
            $items = [];
            foreach ($x->query('.//a[contains(@class,"download")]', $panel) as $a) {
                $items[] = ['label' => exText($one('.//strong', $a)), 'href' => $a->getAttribute('href'), 'note' => exText($one('.//span[contains(@class,"download__text")]/span', $a))];
            }
            $tabs[] = ['downloads', $title, $items];
        } else {
            $tabs[] = ['text', $title, exClean(exInner($one('.//div[contains(@class,"rich-text")]', $panel)))];
        }
    }
    $highlights = [];
    foreach ($x->query('//ul[contains(@class,"pd-highlights")]/li') as $li) $highlights[] = exText($li);
    $related = [];
    foreach ($x->query('//section[contains(@class,"pd-related")]//h3//a') as $a) if (preg_match('#/product/([^/]+)/#', $a->getAttribute('href'), $m)) $related[] = $m[1];
    return [
        'eyebrow' => exText($one('//header[contains(@class,"pd-hero__head")]/p')),
        'image' => ['url' => realpath(DESIGN . '/digital-label-printers/' . $img->getAttribute('src')), 'alt' => $img->getAttribute('alt')],
        'headline' => exText($one('//h2[contains(@class,"pd-hero__headline")]')),
        'intro' => exClean(exInner($one('//div[contains(@class,"pd-hero__intro")]'))),
        'highlights' => $highlights,
        'video' => 'https://youtu.be/' . $one('//aside[contains(@class,"pd-video")]//div[@data-video]')->getAttribute('data-video'),
        'videoCaption' => exText($one('//aside[contains(@class,"pd-video")]//p[contains(@class,"video__caption")]')),
        'poster' => realpath(DESIGN . '/digital-label-printers/' . $poster->getAttribute('src')),
        'tabs' => $tabs,
        'related' => $related,
    ];
}

// ---- import ---------------------------------------------------------------------------------------------------------
$counts = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'redirected' => 0];
foreach ($store as $p) {
    $slug = $p['slug'];
    if ($only && $slug !== $only) continue;
    $status = trim((string)@file_get_contents(__DIR__ . "/cache/$slug.status"));
    if (!str_starts_with($status, '200')) { $counts['redirected']++; continue; }   // 301 on live: config/redirects.php
    $existing = Entry::find()->section('products')->slug($slug)->status(null)->one();
    if ($existing && !$update) { $counts['skipped']++; continue; }

    $html = file_get_contents(__DIR__ . "/cache/$slug.html");
    $live = extractProduct($html);
    $name = trim(html_entity_decode($p['name'], ENT_QUOTES | ENT_HTML5));
    $notes = [];
    $tabs = [];
    $eyebrow = null; $poster = null; $videoCaption = null; $related = [];
    if ($slug === PILOT) {
        $d = designPilot();
        $title = $live['title'] ?? $name;
        [$eyebrow, $image, $headline, $intro, $highlights, $video, $videoCaption, $poster, $tabs] =
            [$d['eyebrow'], $d['image'], $d['headline'], $d['intro'], $d['highlights'], $d['video'], $d['videoCaption'], $d['poster'], $d['tabs']];
        $related = $d['related'];   // the design's "More Printers" (matched by slug; products imported later are linked on a re-run)
        $notes[] = 'pilot: content from the design';
    } elseif ($live) {
        $title = $live['title'] ?: $name;
        $image = $live['image'];
        $headline = $live['headline'];
        $intro = $live['intro'];
        $highlights = $live['highlights'];
        $video = $live['video'];
        // image boxes: short key figures → Highlights (when the text had none), else a list in the Intro
        $boxes = array_filter($live['boxes'], fn($b) => !$b['afterTabs']);
        if ($boxes) {
            $short = !array_filter($boxes, fn($b) => mb_strlen($b['text']) > 40);
            if ($short && !$highlights) {
                foreach ($boxes as $b) $highlights[] = trim($b['title'] . ' ' . $b['text']);
                $notes[] = count($boxes) . ' image boxes → Highlights';
            } else {
                $list = '<ul>' . implode('', array_map(fn($b) => '<li><strong>' . htmlspecialchars($b['title']) . '</strong>' . ($b['text'] !== '' ? ' – ' . htmlspecialchars($b['text']) : '') . '</li>', $boxes)) . '</ul>';
                $at = reset($boxes)['at'];   // where the boxes stood among the intro widgets
                $intro = substr($intro, 0, $at) . $list . substr($intro, $at);
                $notes[] = count($boxes) . ' image boxes → list in the Intro';
            }
        }
        $faq = '';
        foreach ($live['tabs'] as $t) {
            if ($t['from'] === 'accordion' || $t['from'] === 'toggle') { $faq .= '<h4>' . htmlspecialchars($t['title']) . '</h4>' . richHtml($t['html'], 'h4'); continue; }
            if (exBlank($t['html'])) { $notes[] = "empty tab “{$t['title']}” left out"; continue; }
            $tabs[] = tabBlock($t['title'], $t['html'], $title);
        }
        if ($faq !== '') { $tabs[] = ['text', 'FAQ', $faq]; $notes[] = 'accordion → FAQ tab'; }
        if ($live['after']) {
            $extra = richHtml(implode('', $live['after']), 'h4');
            $i = array_search('text', array_column($tabs, 0), true);
            if ($i === false) $tabs[] = ['text', 'More Information', $extra]; else $tabs[$i][2] .= $extra;
            $notes[] = 'text after the tabs → added to “' . ($i === false ? 'More Information' : $tabs[$i][1]) . '”';
        }
        foreach ($live['notes'] as $n) $notes[] = "not imported: $n";
    } else {
        // not built with Elementor: the WooCommerce data
        $title = $name;
        $image = null;
        $headline = null;
        $desc = exClean((string)$p['description']);
        $intro = '';
        $highlights = [];
        $video = null;
        if (preg_match('#^(<p>.*?</p>)(.+)$#s', $desc, $m) && mb_strlen(strip_tags($desc)) > 600) {
            $intro = $m[1];
            $tabs[] = ['text', 'Overview', richHtml($m[2], 'h4')];
        } else {
            $intro = $desc;
        }
        $notes[] = 'no Elementor content: WooCommerce description';
    }
    if (!$image && !empty($p['images'][0]['src'])) { $image = ['url' => $p['images'][0]['src'], 'alt' => $p['images'][0]['alt'] ?: $title]; $notes[] = 'image from WooCommerce'; }
    $seo = liveSeo($html, $title);
    if ($seo['canonical'] && rtrim($seo['canonical'], '/') !== "https://arrsys.com/product/$slug") $notes[] = "canonical on live: {$seo['canonical']}";
    $cats = array_values(array_filter(array_map(fn($c) => $categoryIds[$c['slug']] ?? null, $p['categories'])));
    $card = $cards[$slug] ?? null;

    $summary = sprintf('%-48s %s · %d tabs (%s)%s%s', $slug, $title === $name ? '' : "“{$title}”", count($tabs),
        implode(', ', array_map(fn($t) => "{$t[1]}:{$t[0]}", $tabs)), $video ? ' · video' : '', $card ? ' · design card' : '');
    if ($dry) { say($summary); foreach ($notes as $n) say("    - $n"); continue; }

    $e = $existing ?? new Entry(['sectionId' => $section->id, 'typeId' => $type->id, 'slug' => $slug]);
    $e->title = $title;
    if ($seo['published'] && !$existing) $e->postDate = new DateTime($seo['published']);
    $mainImage = $image ? importFile($image['url'], 'images', 'products', $image['alt'] ?: $title) : null;
    $cardPhoto = $card ? importFile(DESIGN . '/digital-label-printers/' . $card['image'], 'images', 'products/cards', $card['alt']) : null;
    $posterAsset = $poster ? importFile($poster, 'images', 'products', '') : null;
    $e->setFieldValues([
        'eyebrow' => $eyebrow,
        'productCategories' => $cats,
        'brand' => $brands[$slug]['value'] ?? '',
        'sku' => $p['sku'] ?: null,
        'mediaImage' => $mainImage ? [$mainImage->id] : [],
        'subheading' => $headline,
        'richText' => preg_replace('#(<h3>[^<]*</h3>)+$#', '', richHtml($intro, 'h3')) ?: null,   // a heading with nothing after it (e.g. "FAQs" above the accordion) is dropped
        'listItems' => array_map(fn($t) => ['text' => $t], $highlights),
        'productBuilder' => builderValue($video, $posterAsset, $videoCaption, tabsValue($tabs, $title)),
        'featuredImage' => $cardPhoto ? [$cardPhoto->id] : [],
        'headingText' => $card && $card['title'] !== $title ? $card['title'] : null,   // Card Title: the design listing's name
        'cardTagline' => $card['tagline'] ?? null,
        'keySpecs' => $card ? array_map(fn($s) => ['label' => $s[0], 'value' => $s[1]], $card['specs']) : [],
        'relatedProducts' => array_values(array_filter(array_map(fn($r) => Entry::find()->section('products')->slug($r)->status(null)->ids()[0] ?? null, $related))),
        'seoTitle' => $seo['seoTitle'],
        'seoDescription' => $seo['seoDescription'],
        'seoRobots' => $seo['seoRobots'],
    ]);
    if (!$elements->saveElement($e)) {
        $report[] = "FAILED $slug: " . json_encode($e->getErrorSummary(true));
        say("  ! $slug: " . implode('; ', $e->getErrorSummary(true)));
        continue;
    }
    $counts[$existing ? 'updated' : 'created']++;
    say(($existing ? '  ~ ' : '  + ') . $summary);
    if ($notes) $report[] = "**$slug**: " . implode('; ', $notes);
}

say(json_encode($counts));
if (!$dry && !$only) {
    file_put_contents(__DIR__ . '/report.md', "# Products import report\n\nGenerated by 02_products.php on " . date('Y-m-d H:i') .
        ". Counts: " . json_encode($counts) . "\n\n" . implode("\n", array_map(fn($r) => "- $r", $report)) . "\n");
    say('report: storage/imports/products/report.md');
} elseif ($report) {
    foreach ($report as $r) say("  $r");
}
