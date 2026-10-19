<?php
/**
 * Reads a cached live product page (cache/<slug>.html) into plain data for the import, no Craft needed:
 *   title, image {url, alt}, headline, intro (HTML), highlights [text], tabs [{title, html, from}], video, boxes [{title, text}],
 *   after [HTML after the tabs], notes [unhandled widgets]
 * Only the product's own Elementor area (data-elementor-type="product-post") is read, not the header / footer.
 * HTML cleaning (attributes, spans, empty paragraphs) follows the practice project's _elementor.php cleanHtml().
 * Pages without that area (6 non-Elementor products) return null: the import uses the WooCommerce description.
 */

function exDoc(string $html): DOMDocument {
    $d = new DOMDocument();
    @$d->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_NOERROR);
    return $d;
}

function exInner(DOMNode $n): string {
    $h = '';
    foreach ($n->childNodes as $c) $h .= $n->ownerDocument->saveHTML($c);
    return $h;
}

function exText(DOMNode $n): string {
    return trim(preg_replace('/\s+/u', ' ', str_replace("\xc2\xa0", ' ', $n->textContent)));
}

/** Clean pasted Elementor HTML for CKEditor: keep only meaningful attributes, unwrap spans/fonts, drop empty paragraphs and PDF icons. */
function exClean(string $html): string {
    $d = exDoc('<div id="__root">' . $html . '</div>');
    $x = new DOMXPath($d);
    foreach (iterator_to_array($x->query('//img[contains(@src, "icons8-pdf") or contains(@src, "pdf-icon")]')) as $img) $img->parentNode->removeChild($img);
    foreach (iterator_to_array($x->query('//script|//style|//noscript')) as $n) $n->parentNode->removeChild($n);
    foreach (iterator_to_array($x->query('//div[contains(@class,"spec-title-row")]')) as $n) {   // Limitronic spec group titles
        $h = $d->createElement('h4', htmlspecialchars(exText($n)));
        $n->parentNode->replaceChild($h, $n);
    }
    foreach (iterator_to_array($x->query('//*[@*]')) as $el) {
        foreach (iterator_to_array($el->attributes) as $a) {
            if (!in_array($a->name, ['href', 'src', 'alt', 'colspan', 'rowspan', 'target'], true)) $el->removeAttribute($a->name);
        }
    }
    foreach (iterator_to_array($x->query('//span[not(@*)]|//font|//section|//article|//header|//div[not(@*)][not(ancestor::table)]')) as $s) {
        if ($s->getAttribute('id') === '__root') continue;
        while ($s->firstChild) $s->parentNode->insertBefore($s->firstChild, $s);
        $s->parentNode->removeChild($s);
    }
    $root = $x->query('//*[@id="__root"]')->item(0) ?? $x->query('//body')->item(0);
    $out = exInner($root);
    $out = preg_replace('/\s+/u', ' ', $out);
    $out = str_replace(['<b>', '</b>', '<i>', '</i>'], ['<strong>', '</strong>', '<em>', '</em>'], $out);
    $out = preg_replace('#</strong>\s*<strong>#', '', $out);                       // <b>C</b><b>M</b> → one
    $out = preg_replace('#<(p|h\d|li)>\s*(&nbsp;|\xc2\xa0|<br>)*\s*</\1>#u', '', $out);
    $out = preg_replace('#\s*(<(/?)(p|ul|ol|li|h\d|table|thead|tbody|tr|td|th)>)\s*#', '$1', $out);
    return trim($out);
}

function exBlank(string $html): bool {
    return trim(html_entity_decode(strip_tags($html, '<img><table><iframe>')), " \t\n\r\0\x0B\xc2\xa0") === '';
}

/** Largest image in srcset (else src) */
function exImageUrl(DOMElement $img): string {
    $best = $img->getAttribute('data-src') ?: $img->getAttribute('src'); $bestW = 0;
    foreach (array_filter(array_map('trim', explode(',', $img->getAttribute('data-srcset') ?: $img->getAttribute('srcset')))) as $cand) {
        [$u, $w] = array_pad(preg_split('/\s+/', $cand), 2, '0w');
        if ((int)$w > $bestW) { $best = $u; $bestW = (int)$w; }
    }
    return html_entity_decode($best);
}

function extractProduct(string $html): ?array {
    $d = exDoc($html);
    $x = new DOMXPath($d);
    $root = $x->query('//*[@data-elementor-type="product-post"]')->item(0);
    if (!$root) return null;
    $out = ['title' => null, 'image' => null, 'headline' => null, 'intro' => '', 'highlights' => [], 'tabs' => [], 'video' => null, 'boxes' => [], 'after' => [], 'notes' => []];
    foreach ($x->query('.//*[@data-widget_type]', $root) as $w) {
        $type = explode('.', $w->getAttribute('data-widget_type'))[0];
        if ($x->query('ancestor::*[@data-widget_type]', $w)->length) continue;   // nested (inside a widget)
        $body = $x->query('.//*[contains(@class, "elementor-widget-container")]', $w)->item(0) ?? $w;
        switch ($type) {
            case 'heading':
                $h = $x->query('.//h1|.//h2|.//h3|.//h4|.//h5|.//h6|.//p|.//span|.//div[contains(@class,"elementor-heading-title")]', $body)->item(0);
                $text = $h ? exText($h) : exText($body);
                if ($text === '') break;
                if (!$out['title'] && $h && $h->nodeName === 'h1') $out['title'] = $text;
                elseif (!$out['title'] && !$out['headline']) $out['title'] = $text;
                elseif (!$out['headline']) $out['headline'] = $text;
                else $out['intro'] .= '<h3>' . htmlspecialchars($text) . '</h3>';
                break;
            case 'image':
                $img = $x->query('.//img', $body)->item(0);
                if ($img && !$out['image']) $out['image'] = ['url' => exImageUrl($img), 'alt' => trim($img->getAttribute('alt'))];
                elseif ($img) $out['notes'][] = 'extra image ' . basename(parse_url(exImageUrl($img), PHP_URL_PATH));
                break;
            case 'text-editor':
                $clean = exClean(exInner($body));
                if (exBlank($clean)) break;
                if ($out['tabs']) { $out['after'][] = $clean; break; }
                // the first bullet list (before any tabs) = highlights, the rest = intro
                if (!$out['highlights'] && preg_match('#<ul>(.*?)</ul>#s', $clean, $m)) {
                    preg_match_all('#<li>(.*?)</li>#s', $m[1], $li);
                    $out['highlights'] = array_values(array_filter(array_map(fn($t) => trim(html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5)), $li[1])));
                    $clean = str_replace($m[0], '', $clean);
                }
                $out['intro'] .= $clean;
                break;
            case 'tabs':
            case 'accordion':
            case 'toggle':
            case 'nested-tabs':
                $titles = $x->query('.//*[contains(@class,"elementor-tab-desktop-title")]|.//*[contains(@class,"elementor-tab-title") and not(contains(@class,"elementor-tab-mobile-title"))]//a|.//*[contains(@class,"e-n-tab-title-text")]', $w);
                $panels = $x->query('.//*[contains(@class,"elementor-tab-content")]|.//*[contains(@class,"e-con") and @role="tabpanel"]', $w);
                $names = [];
                foreach ($titles as $t) $names[] = exText($t);
                if ($type !== 'tabs' && !$names) foreach ($x->query('.//*[contains(@class,"elementor-tab-title")]', $w) as $t) $names[] = exText($t);
                foreach ($panels as $i => $p) {
                    $out['tabs'][] = ['title' => $names[$i] ?? ('Tab ' . ($i + 1)), 'html' => exClean(exInner($p)), 'raw' => exInner($p), 'from' => $type];
                }
                break;
            case 'video':
                $s = json_decode(html_entity_decode($w->getAttribute('data-settings')), true) ?: [];
                $url = $s['youtube_url'] ?? ($s['vimeo_url'] ?? null);
                if (!$url) { $f = $x->query('.//iframe', $body)->item(0); $url = $f ? ($f->getAttribute('data-src') ?: $f->getAttribute('src')) : null; }
                if ($url && !$out['video']) $out['video'] = $url; elseif ($url) $out['notes'][] = "extra video $url";
                break;
            case 'image-box':   // feature / key-figure boxes (Anytron, EZCut): title + description
                $t = $x->query('.//*[contains(@class,"elementor-image-box-title")]', $body)->item(0);
                $dsc = $x->query('.//*[contains(@class,"elementor-image-box-description")]', $body)->item(0);
                if ($t || $dsc) $out['boxes'][] = ['title' => $t ? exText($t) : '', 'text' => $dsc ? exText($dsc) : '', 'afterTabs' => (bool)$out['tabs'], 'at' => strlen($out['intro'])];
                break;
            case 'form':
            case 'button':
            case 'spacer':
            case 'divider':
            case 'menu-anchor':
                break;
            default:
                $out['notes'][] = "$type: " . mb_substr(exText($body), 0, 60);
        }
    }
    return $out;
}
