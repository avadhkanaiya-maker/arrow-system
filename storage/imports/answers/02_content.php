<?php
/**
 * Answers, content: the live /answers/ hub and its 30 child pages (WordPress REST export in cache/children.json and
 * cache/hub.json, the live <head> of each page in cache/{slug}.html, fetched 2026-10-27) → the Answers Page and one
 * Answer entry per page, at the live URLs, in the hub's order. Run 01_structure.php first.
 *
 * Live answer pages are Elementor "HTML" widgets, hand-written per page (class names differ on every page), so each
 * widget is read by its structure, not its classes, and becomes general page-builder blocks:
 *   H1 + audience text + button + product card   Page Header (small heading "Answer", image = the product photo)
 *   short answer box (no H2)                     Callout (rich text, links kept)
 *   H2 + repeated H3 cards without photos        Card Grid (small heading, heading, intro, cards; labels as subtitles)
 *   H2 + repeated cards with photos (products)   Card Grid with photos, subtitle = fit label, tags as a list, link;
 *                                                the section's own link ("View Equipment") → its Section Link
 *   H2 + table                                   Heading (small heading) + intro paragraph + Data Table
 *   H2 + numbered steps (+ related links)        Heading + Steps (titled steps) or Rich Text list, + Rich Text links
 *   H2 + <details> questions                     Heading (anchor #faq) + FAQ (→ FAQPage schema on answers)
 *   H2 + text + button                           CTA Band
 *   H2 + list of linked answers                  Card Grid (title, text, link)
 *   anything else                                Heading + Rich Text (headings, text, lists, tables, links kept)
 * Links to arrsys.com pages that exist on staging become root-relative; images go to Images → answers/.
 * SEO from the live <head>: title (only when not "{title} - Arrow Systems, Inc."), description, robots (only when not
 * the site default), og:image as Social Image. Card Summary = the hub card's text; Card Icon = the design's icon.
 *
 * Options: --dry (print the mapping, save nothing) · --slugs=a,b (only these answers) · --update (rebuild existing
 * answers; stops if one has an open draft). Existing answers are skipped otherwise. Writes report.md. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/answers/02_content.php --dry
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';
require dirname(__DIR__) . '/blog/_helpers.php';

use craft\db\Query;
use craft\elements\Entry;

const CACHE = __DIR__ . '/cache';
const SITE_ROBOTS = 'follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large';
const ICONS = [   // the design's card icons (Arrowsystem_local/answers/index.html); the 6 newer answers: chosen here
    'top-digital-label-printers-for-packaging-companies' => 'award', 'digital-label-printers-for-mid-sized-packaging-printers' => 'factory',
    'in-house-digital-label-printing-systems-for-food-and-beverage-brands' => 'coffee', 'white-ink-clear-metallic-dark-specialty-substrates' => 'sparkles',
    'durable-equipment-warning-product-id-labels' => 'shield', 'digital-label-finishing-laser-die-cutting-laminating-slitting-matrix-removal' => 'scissors',
    'food-packaging-water-based-pigment-inks' => 'leaf', 'flexible-packaging-custom-mylar-bags' => 'package',
    'aircraft-interior-placards-instrument-panel-decals' => 'plane', 'hybrid-digital-label-press-roll-flatbed-prototypes-specialty-packaging' => 'layers',
    'short-run-pouches-flexible-packaging-in-house' => 'shopping-bag', 'beer-wine-water-bottle-label-printer' => 'wine',
    'membrane-switches-overlays-nameplates-industrial-control-panels' => 'sliders', 'uv-inkjet-label-press-premium-labels-white-ink-varnish' => 'sun',
    'in-house-label-printer-cosmetics-personal-care' => 'droplet', 'pharmaceutical-supplement-label-printer' => 'pill',
    'digital-label-printer-upgrades-from-flexo' => 'trending-up', 'why-packaging-companies-switch-flexo-digital-label-printers' => 'refresh',
    'best-digital-label-printer-short-runs' => 'zap', 'digital-label-printers-improve-short-run-packaging-jobs' => 'gauge',
    'old-label-printing-equipment-bottlenecks' => 'alert', 'reliable-color-consistency-digital-label-printers' => 'palette',
    'digital-label-printers-mid-volume-print-shops' => 'printer', 'variable-data-label-printing-digital-printer' => 'qr-code',
    'medical-device-udi-label-printing-equipment' => 'scan', 'short-run-supplement-label-printing-multiple-skus' => 'pill',
    'cannabis-gummy-labels-vs-pouches-in-house' => 'leaf', 'white-label-cosmetics-printing-solutions' => 'droplet',
    'cosmetics-short-run-label-finishers' => 'scissors', 'small-batch-private-label-cosmetics-printers' => 'printer',
];

$opts = ['dry' => in_array('--dry', $argv, true), 'update' => in_array('--update', $argv, true), 'slugs' => null];
foreach ($argv as $a) if (preg_match('/^--slugs=(.+)$/', $a, $m)) $opts['slugs'] = explode(',', $m[1]);
$GLOBALS['report'] = [];

// ---------------------------------------------------------------------------------------------------------------------
// HTML reading
// ---------------------------------------------------------------------------------------------------------------------

function xp(string $html): DOMXPath {
    $d = new DOMDocument();
    libxml_use_internal_errors(true);
    $d->loadHTML('<?xml encoding="utf-8"?><div id="root">' . $html . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    $x = new DOMXPath($d);
    foreach (iterator_to_array($x->query('//style | //script | //svg | //noscript | //title | //meta | //comment()')) as $n) $n->parentNode->removeChild($n);
    return $x;
}
function q(DOMXPath $x, string $expr, ?DOMNode $ctx = null): array { return iterator_to_array($x->query($expr, $ctx)); }
function txt(?DOMNode $n): string { return $n ? trim(preg_replace('/\s+/u', ' ', html_entity_decode($n->textContent, ENT_QUOTES | ENT_HTML5))) : ''; }
function hasClass(DOMNode $n, string $re): bool { return $n instanceof DOMElement && preg_match($re, $n->getAttribute('class')); }

/** Every arrsys.com URL whose path is a staging entry (or an answer) → root-relative */
function href(string $url): string {
    static $uris;
    $uris ??= array_fill_keys(array_filter((new Query())->select('uri')->from('{{%elements_sites}}')->where(['not', ['uri' => null]])->column()), true);
    if (!preg_match('#^https?://(?:www\.)?arrsys\.com/([^?\#]*)(.*)$#i', trim($url), $m)) return trim($url);
    $path = trim($m[1], '/');
    $path = preg_replace('#/+#', '/', $path);
    if (isset($uris[$path]) || str_starts_with($path, 'answers/') || str_starts_with($path, 'product/')) return '/' . $path . '/' . $m[2];
    if (str_contains($path, '/') && isset($uris[dirname($path)])) {   // a broken live link (/contact-us//-us/, 301 to a 404): its page
        $GLOBALS['report'][] = "broken live link $url → /" . dirname($path) . '/';
        return '/' . dirname($path) . '/';
    }
    return trim($url);
}
/** A Link field value: a staging entry when the URL is one, else the (root-relative) URL */
function linkValue(string $url, string $label): array {
    $h = href($url);
    $e = str_starts_with($h, '/') && !str_contains($h, '#') ? Entry::find()->uri(trim($h, '/'))->status(null)->one() : null;
    return $e ? ['type' => 'entry', 'value' => $e->id, 'label' => $label] : ['type' => 'url', 'value' => $h, 'label' => $label];
}

/** Clean rich text: p, a, strong, em, lists, h3/h4, tables, br; everything else unwrapped */
function rich(DOMNode $n): string {
    $out = '';
    foreach ($n->childNodes as $c) {
        if ($c instanceof DOMText) { $out .= htmlspecialchars(preg_replace('/\s+/u', ' ', $c->textContent), ENT_NOQUOTES); continue; }
        if (!$c instanceof DOMElement) continue;
        $tag = strtolower($c->tagName);
        $inner = rich($c);
        $out .= match (true) {
            $tag === 'a' => $c->getAttribute('href') && !str_starts_with($c->getAttribute('href'), '#')
                ? '<a href="' . htmlspecialchars(href($c->getAttribute('href'))) . '">' . trim($inner) . '</a>' : $inner,
            in_array($tag, ['strong', 'b']) => trim($inner) === '' ? '' : '<strong>' . $inner . '</strong>',
            in_array($tag, ['em', 'i']) => trim($inner) === '' ? '' : '<em>' . $inner . '</em>',
            in_array($tag, ['p', 'ul', 'ol', 'li', 'h3', 'h4', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'caption']) => "<$tag>" . trim($inner) . "</$tag>",
            $tag === 'h2' => '<h3>' . trim($inner) . '</h3>',
            $tag === 'br' => '<br>',
            in_array($tag, ['img', 'button', 'input', 'summary']) => '',
            in_array($tag, ['div', 'section', 'article', 'header', 'footer', 'aside', 'figure']) => ' ' . $inner . ' ',
            default => $inner,
        };
    }
    return preg_replace(['/\s+/u', '#\s*(</?(?:p|ul|ol|li|h3|h4|table|thead|tbody|tr|th|td|caption)>)\s*#'], [' ', '$1'], $out);
}
/** rich() of a node as paragraphs: loose text gets a <p> */
function paras(DOMNode $n): string {
    $h = trim(rich($n));
    return $h === '' ? '' : (preg_match('#^<(p|ul|ol|h3|h4|table)>#', $h) ? $h : "<p>$h</p>");
}

/** The element (inside $root) that holds $node and no other node of $all: one card / step / question */
function itemOf(DOMNode $node, DOMNode $root, array $all): DOMNode {
    $item = $node;
    while ($item->parentNode && !$item->parentNode->isSameNode($root)) {
        $p = $item->parentNode;
        $n = 0;
        foreach ($all as $o) if ($o !== $node && contains($p, $o)) $n++;
        if ($n) break;
        $item = $p;
    }
    return $item;
}
function contains(DOMNode $a, DOMNode $b): bool { for ($n = $b; $n; $n = $n->parentNode) if ($n->isSameNode($a)) return true; return false; }
function inside(DOMNode $n, array $tags): bool {
    for ($p = $n->parentNode; $p; $p = $p->parentNode) if ($p instanceof DOMElement && in_array(strtolower($p->tagName), $tags)) return true;
    return false;
}

/** A section's headings (h1–h5, not in tables / questions), by level: [section title, item headings, other top-level headings].
    The highest level is the title (live uses h1, h2 or h3 per page); the next level with 2+ headings, the items. When the
    title and the cards use the same level, the title is the one outside the cards' grid. */
function headings(DOMXPath $x, DOMElement $w): array {
    $by = [];
    foreach (q($x, './/h1 | .//h2 | .//h3 | .//h4 | .//h5', $w) as $h) if (!inside($h, ['table', 'details', 'button']) && txt($h) !== '') $by[(int)$h->tagName[1]][] = $h;
    ksort($by);
    $levels = array_keys($by);
    if (!$levels) return [null, [], []];
    $top = $by[$levels[0]];
    if (count($levels) === 1 && count($top) >= 3) {
        $grid = array_map(fn($h) => itemOf($h, $w, $top)->parentNode, $top);
        return !$grid[0]->isSameNode($grid[1]) && $grid[1]->isSameNode($grid[2]) ? [$top[0], array_slice($top, 1), []] : [null, $top, []];
    }
    foreach (array_slice($levels, 1) as $l) if (count($by[$l]) >= 2) return [$top[0], $by[$l], array_slice($top, 1)];
    return [$top[0], [], array_slice($top, 1)];
}

/** The section's small heading (short text before the title), title and intro (first longer text after it), up to the first item */
function head(DOMXPath $x, DOMElement $w, ?DOMElement $title, array $items): array {
    $eyebrow = $intro = null;
    $seen = false;
    foreach (q($x, './/*', $w) as $el) {
        if ($title && $el->isSameNode($title)) { $seen = true; continue; }
        foreach ($items as $it) if (contains($it, $el)) break 2;
        if ($title && contains($title, $el)) continue;
        $t = txt($el);
        if ($t === '' || q($x, './/p | .//h2 | .//h3 | .//h4 | .//table | .//ul | .//ol | .//div', $el)) continue;   // text elements only
        $tag = strtolower($el->tagName);
        if (!$seen && $title && !$eyebrow && mb_strlen($t) <= 60 && in_array($tag, ['p', 'span', 'div', 'small'])) { $eyebrow = $t; continue; }
        if (($seen || !$title) && !$intro && in_array($tag, ['p', 'div', 'span']) && mb_strlen($t) > 30) $intro = $t;
    }
    return ['eyebrow' => $eyebrow, 'title' => txt($title), 'intro' => $intro, 'titleNode' => $title];
}

/** A card (an item of a cards / products section) */
function card(DOMXPath $x, DOMNode $it, ?DOMElement $h): array {
    $h ??= q($x, './/h2 | .//h3 | .//h4 | .//h5 | .//*[contains(@class, "name") or contains(@class, "title")]', $it)[0] ?? null;
    $img = q($x, './/img', $it)[0] ?? null;
    $a = ($h ? q($x, './/a[@href]', $h)[0] ?? null : null) ?? q($x, './/a[@href]', $it)[0] ?? (strtolower($it->nodeName) === 'a' ? $it : null);
    $short = [];   // short standalone texts (a "Best Short-Run Fit" tag, a "Hybrid Digital Label Press" category line)
    foreach (q($x, './/*', $it) as $el) {
        $t = txt($el);
        if ($t === '' || mb_strlen($t) > 60 || preg_match('/^\d{1,2}\.?$/', $t) || ($h && contains($h, $el)) || inside($el, ['p', 'li', 'button', 'h1', 'h2', 'h3', 'h4', 'h5'])
            || array_filter(q($x, 'ancestor::a', $el), fn($a) => !q($x, './/img', $a))   // in a text link (a tag over a photo counts)
            || in_array(strtolower($el->tagName), ['p', 'li', 'a', 'ul', 'ol', 'img', 'h1', 'h2', 'h3', 'h4', 'h5'])) continue;
        foreach (q($x, './*', $el) as $child) if (txt($child) !== '') continue 2;   // only the innermost element
        $short[] = $t;
    }
    $label = $short[0] ?? null;
    $text = array_map(fn($t) => '<p>' . htmlspecialchars($t) . '</p>', array_slice($short, 1));
    foreach (q($x, './/p', $it) as $p) {
        $t = txt($p);
        if ($t === '' || $t === $label || ($h && contains($h, $p)) || (q($x, './/a[@href]', $p) && mb_strlen($t) <= 40)) continue;
        $text[] = paras($p);
    }
    $title = txt($h) ?: (string)$img?->getAttribute('alt');
    return ['title' => $title, 'label' => $label === $title ? null : $label, 'text' => implode('', $text),
        'tags' => array_map('txt', array_filter(q($x, './/li', $it), fn($l) => txt($l) !== '')),
        'href' => $a?->getAttribute('href'), 'img' => $img ? ($img->getAttribute('data-src') ?: $img->getAttribute('src')) : null, 'alt' => $img?->getAttribute('alt')];
}
/** The section's own link, outside its items ("View Equipment") */
function moreLink(DOMXPath $x, DOMElement $w, array $items): ?array {
    foreach (q($x, './/a[@href]', $w) as $a) {
        foreach ($items as $it) if (contains($it, $a)) continue 2;
        if (txt($a) !== '') return ['label' => trim(txt($a), ' ↗→'), 'href' => $a->getAttribute('href')];
    }
    return null;
}

/** One html widget → ['kind' => …, …] */
function readSection(DOMXPath $x, DOMElement $w): array {
    [$title, $itemHs, $others] = headings($x, $w);
    $tables = q($x, './/table', $w);
    $details = q($x, './/details', $w);
    $imgs = q($x, './/img', $w);

    // questions: <details><summary>, or a button / heading per question ending in "?" (+ its answer beside it)
    $qs = array_values(array_filter(q($x, './/summary | .//button | .//h3 | .//h4 | .//h5 | .//dt', $w), fn($n) => str_ends_with(trim(preg_replace('/[\s+−–↓▾▸›⌄→]+$/u', '', txt($n))), '?')));
    if ($details || count($qs) >= 2) {
        $cta = null;   // some pages end the FAQ widget with the "Next step" band
        foreach (q($x, './/*[contains(@class, "cta")]', $w) as $c) if (q($x, './/h1 | .//h2 | .//h3', $c) && q($x, './/a[@href]', $c) && !q($x, './/details | .//button', $c)) { $cta = ctaOf($x, $c); break; }
        if ($cta) {
            $cta['node']->parentNode->removeChild($cta['node']);
            foreach (q($x, './/a[@href]', $w) as $a) if (!inside($a, ['details']) && !in_array($a, $qs, true) && txt($a) !== '') { $cta['button2'] ??= ['label' => txt($a), 'href' => $a->getAttribute('href')]; break; }
        }
        $items = $details ?: array_map(fn($n) => itemOf($n, $w, $qs), $qs);
        $title = headings($x, $w)[0];
        if ($title && in_array($title, $qs, true)) $title = null;
        $faq = [];
        foreach ($items as $i => $it) {
            $qn = $details ? (q($x, './/summary', $it)[0] ?? null) : $qs[$i];
            $question = trim(preg_replace('/[\s+−–↓▾▸›⌄→]+$/u', '', txt($qn)));
            $head = head($x, $w, $title, $items);
            if ($qn) $qn->parentNode->removeChild($qn);
            $faq[] = ['q' => $question, 'a' => paras($it)];
        }
        return ['kind' => 'faq', 'items' => $faq, 'cta' => $cta] + $head;
    }
    if ($tables) {
        $t = $tables[0];
        $cap = q($x, './/caption', $t)[0] ?? null;
        $caption = txt($cap);
        if ($cap) $cap->parentNode->removeChild($cap);
        return ['kind' => 'table', 'table' => '<table>' . rich($t) . '</table>', 'caption' => $caption] + head($x, $w, $title, $tables);
    }
    $lis = array_values(array_filter(q($x, './/ol/li', $w), fn($l) => txt($l) !== ''));
    $numbered = array_values(array_filter(q($x, './/p | .//div | .//span', $w), fn($p) => ($prev = prevEl($p)) && preg_match('/^\d{1,2}\.?$/', txt($prev))
        && txt($p) !== '' && !q($x, './/p | .//div', $p)));   // "1" + text: steps drawn with a number badge
    if (count($lis) >= 2 || count($numbered) >= 2) {
        $steps = [];
        foreach ($lis ?: $numbered as $s) {
            $strong = q($x, './/strong | .//b', $s)[0] ?? null;
            $st = txt($strong);
            if ($strong && $st !== '' && str_starts_with(txt($s), $st)) { $strong->parentNode->removeChild($strong); $steps[] = ['title' => rtrim($st, ':'), 'text' => txt($s)]; }
            else $steps[] = ['title' => null, 'text' => txt($s)];
        }
        $stepNodes = array_map(fn($s) => itemOf($s, $w, $lis ?: $numbered), $lis ?: $numbered);
        $links = [];   // the related links beside the steps (a card's title link and its "Open Link" go to one place: once)
        $as = array_values(array_filter(q($x, './/a[@href]', $w), function ($a) use ($stepNodes) { foreach ($stepNodes as $sn) if (contains($sn, $a)) return false; return txt($a) !== ''; }));
        foreach ($as as $a) {
            $label = trim(txt($a), ' ↗→');
            if (preg_match('/^(open|view|learn|read|visit|see|explore)\b/i', $label)) {   // "Open Link": the card's heading names it
                $h = q($x, './/h1 | .//h2 | .//h3 | .//h4 | .//h5', itemOf($a, $w, $as))[0] ?? null;
                if ($h) $label = txt($h);
            }
            $href = trim($a->getAttribute('href'));
            $links[$href === '#' || $href === '' ? "#$label" : $href] ??= ['label' => $label, 'href' => $href];   // live's dead "#" links: listed, unlinked
        }
        $lone = array_values(array_filter(array_merge($others, $itemHs), fn($h) => !q($x, './/a', $h) && !in_array(txt($h), array_column($links, 'label'), true)));
        $linksTitle = count($lone) === 1 ? txt($lone[0]) : 'Related';   // as the design, when live has no heading over the links
        return ['kind' => 'steps', 'steps' => $steps, 'links' => array_values($links), 'linksTitle' => $linksTitle]
            + head($x, $w, $title, array_merge($stepNodes, $others, $itemHs));
    }
    if (count($imgs) >= 2) {   // product cards: one card around each photo
        $items = array_map(fn($i) => itemOf($i, $w, $imgs), $imgs);
        return ['kind' => 'fits', 'cards' => array_map(fn($it) => card($x, $it, null), $items), 'more' => moreLink($x, $w, $items)] + head($x, $w, $title, $items);
    }
    if (count($itemHs) >= 2) {
        $items = array_map(fn($h) => itemOf($h, $w, $itemHs), $itemHs);
        $cards = array_map(fn($it, $h) => card($x, $it, $h), $items, $itemHs);
        return ['kind' => 'cards', 'cards' => $cards, 'more' => moreLink($x, $w, $items)] + head($x, $w, $title, $items);
    }
    $linked = array_values(array_filter(q($x, './/li', $w), fn($l) => q($x, './/a[@href]', $l) && q($x, './/p', $l)));
    if ($linked && $title) {
        $cards = array_map(fn($l) => ['title' => txt(q($x, './/a', $l)[0]), 'href' => q($x, './/a', $l)[0]->getAttribute('href'),
            'text' => implode('', array_map('paras', q($x, './/p', $l)))], $linked);
        return ['kind' => 'links', 'cards' => $cards] + head($x, $w, $title, $linked);
    }
    if (count($imgs) === 1 && count(q($x, './/p', $w)) <= 3) {   // a product card: photo, label, name, link
        $c = card($x, $w, $title);
        return ['kind' => 'product', 'img' => $c['img'], 'alt' => $c['alt'] ?: $c['title'], 'label' => $c['label'], 'name' => $c['title'], 'href' => $c['href']];
    }
    $points = array_values(array_filter(q($x, './/*[contains(@class, "item")]', $w), fn($n) => txt($n) !== '' && !q($x, './/*[contains(@class, "item")]', $n)));
    if (!$title && count($points) >= 2 && !q($x, './/p', $w)) return ['kind' => 'list', 'items' => array_map('txt', $points)];   // a row of short points
    if (!$title) {   // the short answer: text (+ links) without a heading; its small label ("The short answer") dropped
        foreach (q($x, './/*', $w) as $el) if (hasClass($el, '/eyebrow|label/i') && mb_strlen(txt($el)) <= 40) $el->parentNode->removeChild($el);
        return ['kind' => 'short', 'html' => paras($w)];
    }
    $as = array_values(array_filter(q($x, './/a[@href]', $w), fn($a) => txt($a) !== ''));
    if ($as && count($as) <= 2 && mb_strlen(txt($w)) < 700) return ctaOf($x, $w) + ['kind' => 'cta'];
    $hd = head($x, $w, $title, []);
    $title->parentNode->removeChild($title);
    if ($hd['eyebrow']) foreach (q($x, './/*', $w) as $el) if (txt($el) === $hd['eyebrow'] && !q($x, './/*', $el)) { $el->parentNode->removeChild($el); break; }
    return ['kind' => 'rich', 'html' => paras($w)] + $hd;
}
function prevEl(DOMNode $n): ?DOMElement { for ($p = $n->previousSibling; $p; $p = $p->previousSibling) if ($p instanceof DOMElement) return $p; return null; }
function ctaOf(DOMXPath $x, DOMElement $c): array {
    [$title] = headings($x, $c);
    $hd = head($x, $c, $title, []);
    $as = array_values(array_filter(q($x, './/a[@href]', $c), fn($a) => txt($a) !== ''));
    $a = $as[0] ?? null;
    $text = null;
    foreach (q($x, './/p', $c) as $p) if (txt($p) !== $hd['eyebrow'] && (!$a || !contains($p, $a)) && mb_strlen(txt($p)) > 20) { $text = txt($p); break; }
    $btn = fn($a) => ['label' => trim(txt($a), " ↗→\u{FE0E}"), 'href' => $a->getAttribute('href')];
    return ['kind' => 'cta', 'eyebrow' => $hd['eyebrow'], 'title' => $hd['title'], 'intro' => $text,
        'button' => $a ? $btn($a) : null, 'button2' => isset($as[1]) && $as[1]->getAttribute('href') !== $a->getAttribute('href') ? $btn($as[1]) : null, 'node' => $c];
}

/** One live answer page → its parts in order */
function readAnswer(string $html): array {
    $x = xp($html);
    $out = ['title' => null, 'audience' => [], 'button' => null, 'sections' => []];
    foreach (q($x, '//*[@data-widget_type]') as $w) {
        $type = explode('.', $w->getAttribute('data-widget_type'))[0];
        if ($type === 'heading') {
            $t = txt($w);
            if (!$out['title']) $out['title'] = $t; else $out['sections'][] = ['kind' => 'heading', 'title' => $t];
        } elseif ($type === 'text-editor') {
            $out[$out['sections'] && end($out['sections'])['kind'] !== 'short' ? 'sections' : 'audience'][] = $out['sections'] && end($out['sections'])['kind'] !== 'short'
                ? ['kind' => 'rich', 'html' => paras($w), 'title' => null, 'eyebrow' => null, 'intro' => null] : txt($w);
        } elseif ($type === 'button') {
            $a = q($x, './/a[@href]', $w)[0] ?? null;
            if ($a && !$out['button']) $out['button'] = ['label' => txt($a), 'href' => $a->getAttribute('href')];
        } elseif ($type === 'html' && txt($w) !== '') {
            $out['sections'][] = readSection($x, $w);
        }
    }
    return $out;
}

// ---------------------------------------------------------------------------------------------------------------------
// Blocks
// ---------------------------------------------------------------------------------------------------------------------

function blocksFor(array $a, string $slug): array {
    $b = [];
    $product = array_values(array_filter($a['sections'], fn($s) => $s['kind'] === 'product'))[0] ?? null;
    $image = $product ? importImage($product['img'], $product['alt'], 'answers') : null;
    $b[] = ['type' => 'industryHeader', 'fields' => array_filter([
        'eyebrow' => 'Answer', 'sectionHeading' => $a['title'], 'sectionLead' => implode("\n\n", array_filter($a['audience'])) ?: null,
        'mediaImage' => $image ? [$image->id] : null,
        'primaryCta' => $a['button'] ? linkValue($a['button']['href'], $a['button']['label']) : null,
    ])];
    foreach ($a['sections'] as $s) {
        $head = function (?string $anchor = null) use (&$b, $s) {   // Heading (+ the intro as a paragraph)
            $b[] = ['type' => 'heading', 'fields' => array_filter(['eyebrow' => $s['eyebrow'] ?? null, 'headingText' => $s['title'], 'headingLevel' => 'h2', 'anchorId' => $anchor])];
            if (!empty($s['intro'])) $b[] = ['type' => 'richText', 'fields' => ['richText' => '<p>' . htmlspecialchars($s['intro']) . '</p>']];
        };
        switch ($s['kind']) {
            case 'product': break;   // its photo is the Page Header image
            case 'short': $b[] = ['type' => 'callout', 'fields' => ['shortRichText' => $s['html'], 'calloutStyle' => 'callout']]; break;
            case 'heading': $b[] = ['type' => 'heading', 'fields' => ['headingText' => $s['title'], 'headingLevel' => 'h2']]; break;
            case 'cards': case 'fits': case 'links':
                $cards = [];
                foreach ($s['cards'] as $c) {
                    $img = !empty($c['img']) ? importImage($c['img'], $c['alt'] ?: $c['title'], 'answers') : null;
                    $text = ($c['text'] ?? '') . (!empty($c['tags']) ? '<ul>' . implode('', array_map(fn($t) => '<li>' . htmlspecialchars($t) . '</li>', $c['tags'])) . '</ul>' : '');
                    $cards[] = ['type' => 'card', 'fields' => array_filter(['headingText' => $c['title'], 'subheading' => $c['label'] ?? null,
                        'mediaImage' => $img ? [$img->id] : null, 'richText' => $text ?: null, 'ctaLink' => !empty($c['href']) ? linkValue($c['href'], $c['title']) : null])];
                }
                $n = count($cards);
                $b[] = ['type' => 'cardGrid', 'fields' => array_filter(['eyebrow' => $s['eyebrow'] ?? null, 'sectionHeading' => $s['title'] ?: null, 'sectionLead' => $s['intro'] ?? null,
                    'ctaLink' => !empty($s['more']) ? linkValue($s['more']['href'], $s['more']['label']) : null,
                    'cardColumns' => $n % 4 === 0 && $s['kind'] === 'cards' ? '4' : ($n % 2 === 0 && $n % 3 !== 0 ? '2' : '3'), 'cardItems' => matrix($cards)])];
                break;
            case 'table':
                $head();
                $b[] = ['type' => 'dataTable', 'fields' => array_filter(['caption' => $s['caption'] ?: null, 'tableText' => $s['table']])];
                break;
            case 'steps':
                $head();
                $titled = count(array_filter($s['steps'], fn($t) => $t['title'])) === count($s['steps']);
                $b[] = $titled
                    ? ['type' => 'steps', 'fields' => ['stepItems' => matrix(array_map(fn($t) => ['type' => 'step', 'fields' => array_filter(['headingText' => $t['title'], 'sectionBody' => $t['text'] ?: null])], $s['steps']))]]
                    : ['type' => 'richText', 'fields' => ['richText' => '<ol>' . implode('', array_map(fn($t) => '<li>' . htmlspecialchars($t['text']) . '</li>', $s['steps'])) . '</ol>']];
                if ($s['links']) $b[] = ['type' => 'richText', 'fields' => ['richText' => ($s['linksTitle'] ? '<h3>' . htmlspecialchars($s['linksTitle']) . '</h3>' : '')
                    . '<ul>' . implode('', array_map(fn($l) => '<li>' . (in_array($l['href'], ['', '#'], true) ? htmlspecialchars($l['label'])
                        : '<a href="' . htmlspecialchars(href($l['href'])) . '">' . htmlspecialchars($l['label']) . '</a>') . '</li>', $s['links'])) . '</ul>']];
                break;
            case 'faq':
                $head('faq');
                $b[] = ['type' => 'faq', 'fields' => ['faqItems' => matrix(array_map(fn($f) => ['type' => 'faqItem', 'fields' => ['headingText' => $f['q'], 'richText' => $f['a']]], $s['items']))]];
                if ($s['cta']) $b[] = cta($s['cta']);
                break;
            case 'cta': $b[] = cta($s); break;
            case 'list': $b[] = ['type' => 'richText', 'fields' => ['richText' => '<ul>' . implode('', array_map(fn($t) => '<li>' . htmlspecialchars($t) . '</li>', $s['items'])) . '</ul>']]; break;
            case 'rich':
                if ($s['title']) $head();
                if ($s['html']) $b[] = ['type' => 'richText', 'fields' => ['richText' => $s['html']]];
                break;
        }
    }
    $b[] = ['type' => 'newsletter', 'fields' => []];
    return $b;
}
function cta(array $s): array {
    return ['type' => 'ctaBand', 'fields' => array_filter(['eyebrow' => $s['eyebrow'], 'sectionHeading' => $s['title'], 'sectionLead' => $s['intro'],
        'ctaLink' => $s['button'] ? linkValue($s['button']['href'], $s['button']['label']) : null,
        'secondaryCta' => !empty($s['button2']) ? linkValue($s['button2']['href'], $s['button2']['label']) : null])];
}
function matrix(array $blocks): array {
    $out = ['entries' => [], 'sortOrder' => []];
    foreach (array_values($blocks) as $i => $bl) {
        $out['entries']['new' . ($i + 1)] = ['type' => $bl['type'], 'enabled' => true, 'fields' => $bl['fields']];
        $out['sortOrder'][] = 'new' . ($i + 1);
    }
    return $out;
}

/** Live SEO from the cached page <head> */
function liveSeo(string $slug): ?array {
    $page = @file_get_contents(CACHE . "/$slug.html");
    $head = $page ? explode('</head>', $page)[0] : '';
    if (!preg_match('#<title>(.*?)</title>#s', $head, $t) || str_contains($t[1], 'One moment')) return null;   // live's bot check
    $meta = fn($attr, $name) => preg_match('#<meta ' . $attr . '="' . preg_quote($name, '#') . '" content="([^"]*)"#', $head, $m) ? html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5) : null;
    return ['title' => html_entity_decode(trim($t[1]), ENT_QUOTES | ENT_HTML5), 'description' => $meta('name', 'description'),
        'robots' => $meta('name', 'robots'), 'ogImage' => $meta('property', 'og:image'), 'ogImageAlt' => $meta('property', 'og:image:alt')];
}

// ---------------------------------------------------------------------------------------------------------------------
// Hub: order + card summaries + the listing page
// ---------------------------------------------------------------------------------------------------------------------

$children = array_column(json_decode(file_get_contents(CACHE . '/children.json'), true), null, 'slug');
$hub = json_decode(file_get_contents(CACHE . '/hub.json'), true);
$hx = xp($hub['content']['rendered']);
$order = $summary = [];
foreach (q($hx, '//a[contains(@href, "/answers/")]') as $a) {
    $slug = basename(rtrim(parse_url($a->getAttribute('href'), PHP_URL_PATH), '/'));
    if (!isset($children[$slug]) || isset($summary[$slug])) continue;
    $card = $a;   // the card around the link: the nearest element with a description <p>
    while ($card->parentNode && !q($hx, './/p[string-length(normalize-space()) > 40]', $card)) $card = $card->parentNode;
    $p = q($hx, './/p[string-length(normalize-space()) > 40]', $card)[0] ?? null;
    $order[] = $slug;
    $summary[$slug] = txt($p);
}
foreach (array_keys($children) as $slug) if (!in_array($slug, $order, true)) { $order[] = $slug; $GLOBALS['report'][] = "$slug: not linked from the hub (added last, no card summary)"; }
$help = null;
foreach (q($hx, '//section[.//h2 and .//a[contains(@href, "contact")]]') as $s) { $help = ctaOf($hx, $s); break; }

if ($opts['slugs']) $order = array_values(array_intersect($order, $opts['slugs']));

if (!$opts['dry']) {
    $listing = Entry::find()->section('answersPage')->one() ?? exit("no Answers Page: run 01_structure.php\n");
    if (!$listing->sectionHeading) {
        $hubSeo = liveSeo('answers');
        $listing->title = 'Answers';
        $listing->setFieldValues(array_filter([
            'eyebrow' => 'Resources', 'sectionHeading' => 'Arrow Systems [Answers]',   // the design's header (live: the H1 "Arrow Systems Answers")
            'sectionLead' => 'Straight answers to the questions label and packaging teams ask when choosing digital printing and finishing equipment.',
            'caption' => 'Equipment, workflows and applications',
            'subheading' => $help['eyebrow'] ?? null, 'ctaHeading' => $help['title'] ?? null, 'ctaText' => $help['intro'] ?? null,
            'ctaLink' => $help && $help['button'] ? linkValue($help['button']['href'], $help['button']['label']) : null,
            'seoTitle' => $hubSeo && $hubSeo['title'] !== 'Answers - Arrow Systems, Inc.' ? $hubSeo['title'] : null,
            'seoDescription' => $hubSeo['description'] ?? null,
        ]));
        Craft::$app->getElements()->saveElement($listing) || fail('answers page', $listing);
        $listing->uri === 'answers' || exit("the Answers Page got /{$listing->uri}/\n");
        say('  + /answers/ (Answers Page)');
    }
}

// ---------------------------------------------------------------------------------------------------------------------
// Answers
// ---------------------------------------------------------------------------------------------------------------------

$section = Craft::$app->getEntries()->getSectionByHandle('answers') ?? exit("no Answers section: run 01_structure.php\n");
$type = Craft::$app->getEntries()->getEntryTypeByHandle('answer');
$builderId = Craft::$app->getFields()->getFieldByHandle('pageBuilder')->id;
$allowed = array_map(fn($t) => $t->handle, Craft::$app->getFields()->getFieldByHandle('pageBuilder')->getEntryTypes());
$rows = [];
$counts = ['created' => 0, 'updated' => 0, 'skipped' => 0];
foreach ($order as $slug) {
    $live = $children[$slug];
    $a = readAnswer($live['content']['rendered']);
    $kinds = array_count_values(array_column($a['sections'], 'kind'));
    if ($opts['dry']) {
        say(sprintf("%-50s %s | %s", substr($slug, 0, 50), $a['title'], json_encode($kinds)));
        foreach ($a['sections'] as $s) say('    ' . $s['kind'] . ': ' . ($s['eyebrow'] ?? '') . ' | ' . ($s['title'] ?? '') . ' | '
            . mb_substr($s['intro'] ?? '', 0, 50) . ' | ' . match ($s['kind']) {
                'cards', 'fits', 'links' => count($s['cards']) . ' cards: ' . implode(' / ', array_map(fn($c) => ($c['label'] ?? '') . '·' . mb_substr($c['title'], 0, 25) . '·' . (empty($c['img']) ? '' : 'img') . '·' . count($c['tags'] ?? []) . 't·' . mb_strlen(strip_tags($c['text'] ?? '')), $s['cards'])) . (!empty($s['more']) ? ' +more ' . $s['more']['label'] : ''),
                'steps' => count($s['steps']) . ' steps (' . count(array_filter(array_column($s['steps'], 'title'))) . ' titled), ' . count($s['links']) . ' links "' . $s['linksTitle'] . '"',
                'faq' => count($s['items']) . ' questions' . ($s['cta'] ? ' + CTA ' . $s['cta']['title'] . (empty($s['cta']['button2']) ? '' : ' + ' . $s['cta']['button2']['label']) : ''),
                'table' => substr_count($s['table'], '<tr>') . ' rows, caption ' . $s['caption'],
                'cta' => ($s['button']['label'] ?? '-') . ' → ' . ($s['button']['href'] ?? '') . (empty($s['button2']) ? '' : ' + ' . $s['button2']['label']),
                'product' => $s['label'] . ' · ' . $s['name'] . ' · ' . basename((string)$s['img']),
                'short', 'rich' => mb_strlen(strip_tags($s['html'])) . ' chars',
                'list' => implode(' / ', $s['items']),
                default => '',
            });
        say('    audience: ' . mb_substr(implode(' ', $a['audience']), 0, 60) . ' | button: ' . json_encode($a['button']) . ' | card: ' . mb_substr($summary[$slug] ?? '', 0, 50));
        continue;
    }

    $entry = Entry::find()->section('answers')->slug($slug)->status(null)->one();
    if ($entry && !$opts['update']) { $counts['skipped']++; continue; }
    if ($entry && (new Query())->from('{{%drafts}}')->where(['canonicalId' => $entry->id])->exists()) exit("$slug has an open draft: apply or delete it, then run again\n");
    $blocks = blocksFor($a, $slug);
    foreach ($blocks as $bl) in_array($bl['type'], $allowed, true) || exit("the Page Builder doesn't allow {$bl['type']} blocks\n");
    $seo = liveSeo($slug);
    if (!$seo) $GLOBALS['report'][] = "$slug: no live <head> (live sent its bot check): SEO title / description / robots not set";
    $og = $seo && $seo['ogImage'] ? importImage($seo['ogImage'], $seo['ogImageAlt'] ?: $a['title'], 'answers') : null;
    $header = $blocks[0]['fields']['mediaImage'][0] ?? null;

    $entry ??= new Entry(['sectionId' => $section->id, 'typeId' => $type->id]);
    $isNew = !$entry->id;
    $entry->title = $a['title'] ?: html_entity_decode($live['title']['rendered']);
    $entry->slug = $slug;
    $entry->postDate = new DateTime($live['date'], new DateTimeZone('America/New_York'));
    $entry->setFieldValues([
        'cardTagline' => $summary[$slug] ?? null,
        'menuIcon' => ICONS[$slug] ?? null,
        'pageBuilder' => matrix($blocks),
        'seoTitle' => $seo && $seo['title'] !== "{$entry->title} - Arrow Systems, Inc." ? $seo['title'] : null,
        'seoDescription' => $seo['description'] ?? null,
        'seoRobots' => $seo && $seo['robots'] && $seo['robots'] !== SITE_ROBOTS ? $seo['robots'] : null,
        'socialImage' => $og && $og->id !== $header ? [$og->id] : [],
    ]);
    Craft::$app->getElements()->saveElement($entry) || fail("answer $slug", $entry);
    $entry->uri === "answers/$slug" || exit("$slug got /{$entry->uri}/: another entry uses that URL\n");
    $saved = (int)Entry::find()->ownerId($entry->id)->fieldId($builderId)->status(null)->count();
    $saved === count($blocks) || exit("$slug: $saved of " . count($blocks) . " blocks saved\n");
    $counts[$isNew ? 'created' : 'updated']++;
    $rows[] = sprintf('| [%s](/answers/%s/) | %s | %d | %s |', $entry->title, $slug, $isNew ? 'created' : 'updated', count($blocks),
        implode(', ', array_map(fn($k, $n) => "$k ×$n", array_keys($kinds), $kinds)));
    say(sprintf('  %s /answers/%s/ (%d blocks)', $isNew ? '+' : '~', $slug, count($blocks)));
}
if ($opts['dry']) exit(0);

// the Answers structure in the hub's order (= the /answers/ listing order)
$structureId = $section->structureId;
foreach ($order as $slug) if ($e = Entry::find()->section('answers')->slug($slug)->status(null)->one()) Craft::$app->getStructures()->appendToRoot($structureId, $e);

file_put_contents(__DIR__ . '/report.md', "# Answers import report\n\nRun: " . date('Y-m-d H:i') . ' · ' . json_encode($counts) . "\n\n"
    . "| Answer | Result | Blocks | Live sections read |\n|---|---|---|---|\n" . implode("\n", $rows) . "\n\n"
    . ($GLOBALS['report'] ? "## Notes\n\n- " . implode("\n- ", array_unique($GLOBALS['report'])) . "\n" : ''));
say(json_encode($counts) . ' → storage/imports/answers/report.md');
