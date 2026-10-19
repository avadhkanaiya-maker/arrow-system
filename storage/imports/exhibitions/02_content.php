<?php
/**
 * Exhibitions, Phases 3–5: content (EXHIBITIONS_MIGRATION_PLAN.md §7, decisions D1–D7).
 *
 *   Exhibitions Page + Upcoming Exhibitions Page   texts from the design; SEO as live (D5)
 *   28 live shows (data/pages.json = WordPress REST, data/seo.json = each page's <head>, data/listing.json = the
 *       live listing's card names and order)
 *       title = the live listing card name · slug = the live slug · Has a Page on · Structure order = live order
 *       dates: year only (D3) · location: where the live page states it (the design's cards) · subtitle + intro +
 *       photos from the live page (Elementor gallery / FooGallery: every photo links to its full-size original)
 *       Label Expo 2026 = the design's pilot: its 16 photos and alt texts from exhibition-detail.html
 *       Card Photo = the design's photo for that show, nothing otherwise (D7)
 *       SEO title only where live differs from "{title} - Arrow Systems, Inc." (incl. Label Expo 2026's empty
 *       title, D5); meta description as live
 *   4 upcoming 2026 shows (the design's upcoming.html = the old live upcoming page): exact dates, location, booth, logo,
 *       no page (D2: LOUPE stays its own entry)
 *   Homepage: section G after D, showing the design's 3 shows (editable: Shows to Display)
 *   Menus: main menu + footer "Exhibitions" → the Exhibitions Page
 * Existing entries (matched by slug) keep their content unless --update. Back up first. Run from the project root:
 *   /opt/lampp/bin/php storage/imports/exhibitions/02_content.php [--update]
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\elements\Asset;
use craft\elements\Entry;
use craft\helpers\Assets as AssetsHelper;

const DESIGN = '/home/avadh/Desktop/Arrowsystem_local';
$update = in_array('--update', $argv, true);
$report = [];
$elements = Craft::$app->getElements();
$entriesService = Craft::$app->getEntries();

/** The asset for an image URL or local file, imported into Images → exhibitions/ once (matched by file name), with alt text */
function exImage(string $src, ?string $alt = null): ?Asset {
    global $report;
    static $folder, $cache = [];
    $src = html_entity_decode(trim($src));
    if (isset($cache[$src])) return $cache[$src];
    if (!$folder) {
        $volume = Craft::$app->getVolumes()->getVolumeByHandle('images');
        $folder = Craft::$app->getAssets()->ensureFolderByFullPathAndVolume('exhibitions', $volume, false);
    }
    $filename = AssetsHelper::prepareAssetName(rawurldecode(basename(parse_url($src, PHP_URL_PATH) ?: $src)));
    $asset = Asset::find()->folderId($folder->id)->filename($filename)->one();
    if (!$asset) {
        $fetch = is_file($src) ? $src : preg_replace_callback('/[^\x21-\x7e]/', fn($m) => rawurlencode($m[0]), $src);
        $data = is_file($src) ? file_get_contents($src)
            : @file_get_contents($fetch, false, stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0 (Arrow Systems staging migration)\r\n", 'timeout' => 60]]));
        if (!$data) { $report[] = "could not download $src"; return $cache[$src] = null; }
        $tmp = Craft::$app->getPath()->getTempPath() . '/' . uniqid('exh') . '-' . $filename;
        file_put_contents($tmp, $data);
        $asset = new Asset(['tempFilePath' => $tmp, 'filename' => $filename, 'volumeId' => $folder->volumeId, 'newFolderId' => $folder->id, 'avoidFilenameConflicts' => true]);
        $asset->setScenario(Asset::SCENARIO_CREATE);
        if (!Craft::$app->getElements()->saveElement($asset)) { $report[] = "could not save $filename"; return $cache[$src] = null; }
    }
    $alt = trim(html_entity_decode((string)$alt, ENT_QUOTES | ENT_HTML5));
    if ($alt !== '' && !$asset->alt) { $asset->alt = $alt; Craft::$app->getElements()->saveElement($asset); }
    return $cache[$src] = $asset;
}

function dom(string $html): DOMXPath {
    $d = new DOMDocument();
    libxml_use_internal_errors(true);
    $d->loadHTML('<?xml encoding="utf-8"?>' . $html);
    libxml_clear_errors();
    return new DOMXPath($d);
}
$text = fn(?DOMNode $n) => $n ? trim(preg_replace('/\s+/u', ' ', html_entity_decode($n->textContent, ENT_QUOTES | ENT_HTML5))) : '';
$link = fn(string $label, string $url) => ['type' => str_starts_with($url, 'tel:') ? 'tel' : 'url', 'value' => preg_replace('/^tel:/', '', $url), 'label' => $label];

// ---- design data: card photos + locations (exhibitions/index.html), pilot gallery, upcoming shows --------------------
$dx = dom(file_get_contents(DESIGN . '/exhibitions/index.html'));
$cardPhotos = [];
$locations = [];
foreach ($dx->query('//ul[@class="tl-grid"]/li') as $li) {
    $a = $dx->query('.//h4//a', $li)->item(0);
    $href = $a->getAttribute('href');
    $slug = $href === 'exhibition-detail.html' ? 'label-expo-2026' : trim(parse_url($href, PHP_URL_PATH), '/');
    $img = $dx->query('.//img', $li)->item(0);
    if ($img) $cardPhotos[$slug] = [DESIGN . '/assets/exhibitions/web/' . basename($img->getAttribute('src')), $img->getAttribute('alt')];
    $loc = $dx->query('.//*[@class="xcard__loc"]', $li)->item(0);
    if ($loc) $locations[$slug] = $text($loc);
}

// ---- 1. Listing pages -------------------------------------------------------------------------------------------
say('Listing pages');
$live = json_decode(file_get_contents(__DIR__ . '/data/seo.json'), true);
$listingPage = Entry::find()->section('exhibitionsPage')->status(null)->one();
if ($update || !$listingPage->sectionLead) {
    $hero = exImage(DESIGN . '/assets/exhibitions/web/label-expo-2026-hero.webp', 'Arrow Systems stand with ArrowJet printers and finishers at Label Expo 2026 in Chicago');
    $inset = exImage(DESIGN . '/assets/exhibitions/web/labelexpo-2025.webp', 'Arrow Systems team with an ArrowJet printer at LabelExpo 2025 in Barcelona');
    if ($inset && $inset->title !== 'LabelExpo 2025, Barcelona') {   // its (hidden) caption on the page, as in the design
        $inset->title = 'LabelExpo 2025, Barcelona';
        $elements->saveElement($inset);
    }
    $og = $live['exhibitions']['ogImage'] ? exImage($live['exhibitions']['ogImage'], 'Exhibitions') : null;
    $listingPage->title = 'Exhibitions';
    $listingPage->setFieldValues([
        'eyebrow' => 'Our Innovation in Focus',
        'sectionHeading' => 'Exhibitions',
        'sectionLead' => 'Meet the Arrow Systems team at industry events. Browse the trade shows where we have exhibited our digital printing and finishing equipment since 2013.',
        'exhibitionPhotos' => array_values(array_filter([$hero?->id, $inset?->id])),
        'caption' => 'Label Expo 2026, Chicago',
        'ctaLink' => $link('Contact Us', 'https://arrsys.com/contact-us/'),
        // SEO exactly as live (D5): title = the default "Exhibitions - Arrow Systems, Inc.", live description and image
        'seoDescription' => $live['exhibitions']['description'],
        'socialImage' => $og ? [$og->id] : [],
    ]);
    $elements->saveElement($listingPage) || fail('Exhibitions Page', $listingPage);
    say('  ~ Exhibitions Page');
}
$upcomingPage = Entry::find()->section('upcomingExhibitionsPage')->status(null)->one();
if ($update || !$upcomingPage->sectionLead) {
    $upcomingPage->title = 'Upcoming Exhibitions';
    $upcomingPage->setFieldValues([
        'eyebrow' => '2026 Trade Shows',
        'sectionHeading' => 'Upcoming [Exhibitions]',
        'sectionLead' => 'Meet the Arrow Systems team at these industry events in 2026.',
        'ctaLink' => $link('Schedule a Meeting', 'https://arrsys.com/contact-us/'),
        'helpTitle' => 'Can’t make it to a show?',
        'helpText' => 'Talk to our team anytime. Tell us your application, materials and run lengths.',
        'helpLink' => $link('Talk to a Specialist', 'tel:+18886972749'),
        'quoteLink' => $link('Contact Us', 'https://arrsys.com/contact-us/'),
        // the live URL only redirects today, so the design's description (D1)
        'seoDescription' => 'Meet the Arrow Systems team at these industry events in 2026: R2R Conference & Expo, LOUPE (Labelexpo Americas), PRINTING United Expo and PACK EXPO International.',
    ]);
    $elements->saveElement($upcomingPage) || fail('Upcoming Exhibitions Page', $upcomingPage);
    say('  ~ Upcoming Exhibitions Page');
}

// ---- 2. Upcoming 2026 shows (no pages), from the design's upcoming.html -----------------------------------------
say('Upcoming shows');
$section = $entriesService->getSectionByHandle('exhibitions');
$type = $entriesService->getEntryTypeByHandle('exhibition');
$order = [];
$ux = dom(file_get_contents(DESIGN . '/exhibitions/upcoming.html'));
foreach ($ux->query('//li[contains(@class, "show-row")]') as $row) {
    $title = $text($ux->query('.//h3[@class="show-row__title"]', $row)->item(0));
    $slug = Craft\helpers\ElementHelper::generateSlug($title) . '-2026';
    $facts = [];
    foreach ($ux->query('.//li[@class="show-fact"]', $row) as $fact) {
        $label = $text($ux->query('.//span[@class="show-fact__label"]', $fact)->item(0));
        $facts[$label] = trim(substr($text($fact), strlen($label)));
    }
    $logoImg = $ux->query('.//div[@class="show-row__logo"]//img', $row)->item(0);
    $e = Entry::find()->section('exhibitions')->slug($slug)->status(null)->one();
    $order[] = $slug;
    if ($e && !$update) { say("  = $slug"); continue; }
    $e ??= new Entry(['sectionId' => $section->id, 'typeId' => $type->id]);
    $logo = $logoImg ? exImage(DESIGN . '/assets/exhibitions/web/' . basename($logoImg->getAttribute('src')), $logoImg->getAttribute('alt')) : null;
    $tba = fn($v) => ($v === 'To be announced' || $v === '') ? null : $v;
    $e->title = $title;
    $e->slug = $slug;
    $e->setFieldValues([
        'startDate' => new DateTime($row->getAttribute('data-start')),
        'endDate' => new DateTime($row->getAttribute('data-end')),
        'datePrecision' => 'exact',
        'city' => $facts['Location'] ?? null,
        'boothNumber' => $tba($facts['Booth'] ?? ''),
        'techSpotlight' => $tba($facts['Tech Spotlight'] ?? ''),
        'showLogo' => $logo ? [$logo->id] : [],
        'hasPage' => false,
    ]);
    $elements->saveElement($e) || fail("show $slug", $e);
    say("  + $slug ({$row->getAttribute('data-start')})");
}

// ---- 3. The 28 live shows ----------------------------------------------------------------------------------------
say('Live shows');
$pages = array_column(json_decode(file_get_contents(__DIR__ . '/data/pages.json'), true), null, 'slug');
$listing = json_decode(file_get_contents(__DIR__ . '/data/listing.json'), true);
$rows = [];
foreach ($listing as $item) {
    $slug = $item['slug'];
    $order[] = $slug;
    $title = trim(preg_replace('/\s+/u', ' ', $item['name']));
    $e = Entry::find()->section('exhibitions')->slug($slug)->status(null)->one();
    if ($e && !$update) { say("  = $slug"); continue; }
    $clash = Entry::find()->uri($slug)->status(null)->andWhere(['not', ['elements.id' => $e?->id ?? 0]])->one();
    if ($clash) { $report[] = "$slug: URL already used by {$clash->section->name} #{$clash->id}; skipped"; continue; }

    $x = dom($pages[$slug]['content']['rendered']);
    // photos: every link to a full-size image (Elementor gallery and FooGallery both link to the originals)
    $photos = [];
    $liveCount = 0;
    if ($slug === 'label-expo-2026') {
        // the design's pilot: the same 16 photos, with its alt texts
        $dd = dom(file_get_contents(DESIGN . '/exhibitions/exhibition-detail.html'));
        foreach ($dd->query('//ul[@data-gallery]//a[@class="gallery__link"]') as $a) {
            $liveCount++;
            $img = $dd->query('.//img', $a)->item(0);
            $asset = exImage(DESIGN . '/assets/exhibitions/' . preg_replace('#^\.\./assets/exhibitions/#', '', $a->getAttribute('href')), $img->getAttribute('alt'));
            if ($asset) $photos[] = $asset->id;
        }
    } else {
        $seen = [];
        foreach ($x->query('//a[@href]') as $a) {
            $href = $a->getAttribute('href');
            if (!preg_match('#^https://arrsys\.com/media/(?!cache/)[^?]+\.(jpe?g|png|webp|gif)$#i', $href) || isset($seen[$href])) continue;
            $seen[$href] = true;
            $liveCount++;
            $img = $x->query('.//img', $a)->item(0);
            $labelled = $x->query('.//*[@aria-label]', $a)->item(0);
            $alt = ($img ? $img->getAttribute('alt') : '') ?: ($labelled ? $labelled->getAttribute('aria-label') : '') ?: $a->getAttribute('data-elementor-lightbox-title') ?: $title;
            $asset = exImage($href, $alt);
            if ($asset) $photos[] = $asset->id;
        }
    }
    // subtitle + intro: the page's text outside the gallery (the title heading and duplicates left out)
    foreach ($x->query('//*[contains(@class, "gallery") or contains(@class, "fg-")]') as $g) if ($g->parentNode) $g->parentNode->removeChild($g);
    $subtitle = null;
    foreach ($x->query('//h1|//h2|//h3|//h4') as $h) {
        $t = $text($h);
        if ($t !== '' && strcasecmp(preg_replace('/\W/', '', $t), preg_replace('/\W/', '', $title)) !== 0 && $h->tagName !== 'h1') { $subtitle = $t; break; }
    }
    $paras = [];
    foreach ($x->query('//p') as $p) {
        $t = $text($p);
        if ($t === '' || $t === $subtitle || $t === $title || in_array($t, $paras, true)) continue;
        $paras[] = $t;
    }

    $cp = $cardPhotos[$slug] ?? null;
    $card = $cp ? exImage($cp[0], $cp[1]) : null;
    $year = preg_match('/(19|20)\d{2}/', $slug, $m) ? $m[0] : substr($pages[$slug]['date'], 0, 4);
    $seo = $live[$slug] ?? [];
    $e ??= new Entry(['sectionId' => $section->id, 'typeId' => $type->id]);
    $e->title = $title;
    $e->slug = $slug;
    $e->setFieldValues([
        'startDate' => new DateTime("$year-01-01"),
        'endDate' => null,
        'datePrecision' => 'year',
        'city' => $locations[$slug] ?? null,
        'featuredImage' => $card ? [$card->id] : [],
        'hasPage' => true,
        'subheading' => $subtitle,
        'sectionLead' => $paras ? implode("\n\n", $paras) : null,
        'exhibitionPhotos' => $photos,
        'seoTitle' => ($seo['title'] ?? null) && $seo['title'] !== "$title - Arrow Systems, Inc." ? $seo['title'] : null,
        'seoDescription' => $seo['description'] ?? null,
        // canonical as live (D5): only where the live page points somewhere else (Drupa 2024, Printing United 2022)
        'seoCanonical' => ($seo['canonical'] ?? null) && rtrim(parse_url($seo['canonical'], PHP_URL_PATH), '/') !== "/$slug"
            ? parse_url($seo['canonical'], PHP_URL_PATH) : null,
    ]);
    $elements->saveElement($e) || fail("show $slug", $e);
    $rows[] = sprintf('| [%s](/%s/) | %s | %d / %d | %s | %s |', $title, $slug, $year, count($photos), $liveCount, $card ? 'design' : '—', $locations[$slug] ?? '');
    say(sprintf('  + %-28s %s  %2d photos  %s', $slug, $year, count($photos), $subtitle ? 'subtitle' : ''));
}

// ---- Structure order = upcoming shows first (by date), then the live listing order -------------------------------
$structures = Craft::$app->getStructures();
$prev = null;
foreach ($order as $slug) {
    $e = Entry::find()->section('exhibitions')->slug($slug)->status(null)->one();
    if (!$e) continue;
    $prev ? $structures->moveAfter($section->structureId, $e, $prev) : $structures->prependToRoot($section->structureId, $e);
    $prev = $e;
}
say('  structure order set (' . count($order) . ' shows)');

// ---- 4. Homepage section G (after D) -----------------------------------------------------------------------------
say('Homepage');
$fields = Craft::$app->getFields();
$fields->refreshFields();
$home = Entry::find()->section('homepage')->one();
$sectionsOnHome = $home->homepageSections->status(null)->all();
if (!array_filter($sectionsOnHome, fn($s) => $s->type->handle === 'exhibitionHighlights')) {
    $picked = [];
    foreach (['labelexpo-2025', 'global-pouch-forum-2025', 'packexpo-2024'] as $slug) {   // the design's 3 cards
        $id = Entry::find()->section('exhibitions')->slug($slug)->status(null)->ids()[0] ?? null;
        if ($id) $picked[] = $id;
    }
    $sortOrder = [];
    foreach ($sectionsOnHome as $s) {
        $sortOrder[] = $s->id;
        if ($s->type->handle === 'videoFeature') $sortOrder[] = 'new1';
    }
    if (!in_array('new1', $sortOrder, true)) $sortOrder[] = 'new1';
    $home->setFieldValue('homepageSections', [
        'entries' => ['new1' => ['type' => 'exhibitionHighlights', 'title' => 'G · Exhibitions', 'fields' => [
            'eyebrow' => 'Exhibitions',
            'sectionHeading' => 'Our [Innovation] | in Focus',
            'sectionLead' => 'Global exhibitions. Real connections. Lasting impact.',
            'selectedExhibitions' => $picked,
            'ctaLink' => ['type' => 'entry', 'value' => $listingPage->id, 'label' => 'All exhibitions'],
            'anchorId' => 'events',
        ]]],
        'sortOrder' => $sortOrder,
    ]);
    $elements->saveElement($home) || fail('homepage', $home);
    say('  + G · Exhibitions (after D), showing ' . count($picked) . ' picked shows');
} else {
    say('  = G · Exhibitions already on the homepage');
}

// ---- 5. Menus: "Exhibitions" → the Exhibitions Page --------------------------------------------------------------
foreach (verbb\navigation\elements\Node::find()->status(null)->all() as $n) {
    if (trim($n->title) === 'Exhibitions' && (string)$n->url === 'https://arrsys.com/exhibitions/') {
        $n->type = Entry::class;
        $n->elementId = $listingPage->id;
        $n->url = null;
        $elements->saveElement($n) || fail("menu node {$n->id}", $n);
        say("  ~ menu {$n->menu->handle}: Exhibitions → {$listingPage->url}");
    }
}

$out = ["# Exhibitions import report", '', 'Run: ' . date('Y-m-d H:i'), '',
    '| Show | Year | Photos (Craft / live) | Card photo | Location |', '|---|---|---|---|---|', ...$rows, ''];
if ($report) $out = array_merge($out, ['## Notes', '', ...array_map(fn($r) => "- $r", $report)]);
file_put_contents(__DIR__ . '/report.md', implode("\n", $out) . "\n");
say('Report: storage/imports/exhibitions/report.md' . ($report ? ' (' . count($report) . ' notes)' : ''));
