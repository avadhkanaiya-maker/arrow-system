<?php
/**
 * Elementor post HTML (WordPress REST "content.rendered") → Article Body blocks.
 *
 *   Article content only: sidebar columns (Recent / Trending posts lists), the Table of Contents widget (made
 *   automatically from the Heading blocks), the contact form + its "Contact With Our Team Now" heading (replaced by the
 *   closing Call to Action, D4), the banner image at the top (it is the cover image) and the repeated title are left out.
 *
 *   Elementor widget        → block
 *   heading (h2 / h3 / h4+) → Heading (H2 / H3 / H4)
 *   text-editor             → Rich Text, split into: Heading (h2–h6 inside the text), Spec List (<dl> term lists),
 *                             Image (<img>), Video (YouTube <iframe>); tables stay in the Rich Text
 *   image                   → Image (+ caption; the width from the live image size)
 *   video                   → Video (YouTube)
 *   accordion               → FAQ → FAQ Items
 *   gallery / image-gallery / media-carousel / image-carousel → Gallery
 *   button                  → Button
 *   divider                 → Divider
 *   html                    → HTML
 * Anything else is listed in the report.
 */


class ElementorConverter
{
    /** @var array blocks (see nb()) */
    public array $blocks = [];
    /** @var string[] notes for the report */
    public array $notes = [];
    public array $stats = ['headings' => 0, 'images' => 0, 'liveHeadings' => 0, 'liveImages' => 0];
    /** the top banner image left out of the article (used as the cover when the post has no featured image) */
    public ?string $bannerUrl = null;

    private array $rich = [];
    private DOMXPath $x;
    private string $title;
    private ?string $coverFile;
    /** @var array<string,bool> live post slugs that exist in Craft (links to them become root-relative) */
    private array $localSlugs;

    public function __construct(string $title, ?string $coverUrl, array $localSlugs)
    {
        $this->title = $title;
        $this->coverFile = $coverUrl ? self::baseFile($coverUrl) : null;
        $this->localSlugs = $localSlugs;
    }

    /** "…/media/Foo-1024x576.webp" → "foo" (for comparing images across sizes) */
    public static function baseFile(string $url): string
    {
        $name = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_FILENAME));
        return preg_replace('/-(\d+x\d+|scaled|e\d{10,})$/', '', $name);
    }

    public function convert(string $html): array
    {
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="__root">' . $html . '</div>');
        libxml_clear_errors();
        $this->x = new DOMXPath($doc);

        $widgets = iterator_to_array($this->x->query('//*[@data-element_type="widget"]'));
        // live counts (all article widgets), for the report
        $skipCols = $this->sidebarColumns();
        $sequence = [];
        foreach ($widgets as $w) {
            if ($this->inSkippedColumn($w, $skipCols)) continue;
            // a widget inside another widget (nested Elementor templates) is handled by its parent
            $sequence[] = $w;
        }
        // the heading just before each form ("Contact With Our Team Now") goes with the form
        $skip = new SplObjectStorage();
        foreach ($sequence as $i => $w) {
            if ($this->type($w) === 'form' && $i > 0 && $this->type($sequence[$i - 1]) === 'heading') $skip->attach($sequence[$i - 1]);
        }

        $first = true;
        foreach ($sequence as $w) {
            if ($skip->contains($w)) continue;
            $type = $this->type($w);
            if ($first && $type === 'image') {
                // banner at the top of the post = the cover image
                $img = $this->x->query('.//img', $w)->item(0);
                $this->bannerUrl = $img ? $this->imageUrl($img) : null;
                $file = $img ? self::baseFile($this->bannerUrl) : null;
                if ($file && $this->coverFile && $file !== $this->coverFile) $this->notes[] = "top banner image $file is not the featured image ({$this->coverFile}); left out (the featured image is the cover)";
                $first = false;
                continue;
            }
            switch ($type) {
                case 'heading': $this->heading($w, !$this->blocks && !$this->rich); break;
                case 'text-editor': $this->richWidget($w); break;
                case 'image': $this->image($w); break;
                case 'video': $this->video($w); break;
                case 'accordion': case 'toggle': $this->accordion($w); break;
                case 'gallery': case 'image-gallery': case 'media-carousel': case 'image-carousel': $this->gallery($w); break;
                case 'button': $this->button($w); break;
                case 'divider': $this->flush(); $this->blocks[] = nb('divider'); break;
                case 'html':
                    $this->flush();
                    $this->blocks[] = nb('html', ['htmlCode' => trim($this->inner($this->container($w)))]);
                    $this->notes[] = 'HTML widget kept as an HTML block (check it renders)';
                    break;
                case 'table-of-contents': case 'posts': case 'form': case 'spacer': break;
                default: $this->notes[] = "widget '$type' not converted";
            }
            if (!in_array($type, ['table-of-contents', 'posts', 'form', 'spacer'], true)) $first = false;
        }
        $this->flush();
        return $this->blocks;
    }

    // ---------------------------------------------------------------------------------------------------------------

    private function type(DOMElement $w): string
    {
        return explode('.', $w->getAttribute('data-widget_type'))[0];
    }

    private function container(DOMElement $w): DOMElement
    {
        return $this->x->query('./*[contains(@class, "elementor-widget-container")]', $w)->item(0) ?? $w;
    }

    /** Columns that are sidebars: they hold a Posts widget and (almost) no text, or only a Table of Contents */
    private function sidebarColumns(): SplObjectStorage
    {
        $out = new SplObjectStorage();
        foreach ($this->x->query('//*[@data-element_type="column"]') as $col) {
            $types = [];
            foreach ($this->x->query('.//*[@data-element_type="widget"]', $col) as $w) $types[] = $this->type($w);
            $text = count(array_filter($types, fn($t) => $t === 'text-editor'));
            if ((in_array('posts', $types, true) && $text <= 2) || ($types && !array_diff($types, ['table-of-contents']))) $out->attach($col);
        }
        return $out;
    }

    private function inSkippedColumn(DOMElement $w, SplObjectStorage $cols): bool
    {
        for ($n = $w->parentNode; $n instanceof DOMElement; $n = $n->parentNode) {
            if ($cols->contains($n)) return true;
        }
        return false;
    }

    private function flush(): void
    {
        $html = trim(implode('', $this->rich));
        $this->rich = [];
        if ($html !== '' && !self::isBlank($html)) $this->blocks[] = nb('richText', ['richText' => $html]);
    }

    private static function isBlank(string $html): bool
    {
        return trim(html_entity_decode(strip_tags($html, '<img><table><iframe>')), " \t\n\r\0\x0B\xc2\xa0") === '';
    }

    private static function text(DOMNode $n): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode($n->textContent, ENT_QUOTES | ENT_HTML5)), " \xc2\xa0");
    }

    private function inner(DOMNode $n): string
    {
        $h = '';
        foreach ($n->childNodes as $c) $h .= $n->ownerDocument->saveHTML($c);
        return $h;
    }

    private static function norm(string $s): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '', html_entity_decode($s, ENT_QUOTES | ENT_HTML5)));
    }

    private function addHeading(string $text, string $level, bool $allowTitleSkip = false): void
    {
        $text = trim($text);
        if ($text === '') return;
        $this->stats['liveHeadings']++;
        if ($allowTitleSkip && self::norm($text) === self::norm($this->title)) return; // the repeated post title (the H1 is the template's)
        $this->flush();
        $level = match ($level) { 'h1', 'h2' => 'h2', 'h3' => 'h3', default => 'h4' };
        $this->blocks[] = nb('heading', ['headingText' => $text, 'headingLevel' => $level, 'showInContents' => $level === 'h2']);
        $this->stats['headings']++;
    }

    private function heading(DOMElement $w, bool $atStart): void
    {
        $h = $this->x->query('.//h1|.//h2|.//h3|.//h4|.//h5|.//h6|.//p|.//div[contains(@class, "elementor-heading-title")]', $w)->item(0);
        if (!$h) return;
        $level = in_array($h->tagName, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true) ? $h->tagName : 'h4';
        if ($this->x->query('.//a[@href]', $h)->length) $this->notes[] = 'link on heading "' . mb_substr(self::text($h), 0, 40) . '" dropped';
        $isH1 = $level === 'h1';
        $text = self::text($h);
        if ($isH1 && $atStart) {
            // the live page's own H1 (a variant of the title): the template's H1 is the post title
            if (self::norm($text) !== self::norm($this->title)) $this->notes[] = 'live H1 "' . mb_substr($text, 0, 60) . '" differs from the title (the title is the H1 now)';
            $this->stats['liveHeadings']++;
            return;
        }
        if ($isH1) $this->notes[] = 'H1 "' . mb_substr($text, 0, 40) . '" inside the article kept as an H2';
        $this->addHeading($text, $level, $atStart);
    }

    private function richWidget(DOMElement $w): void
    {
        $this->walk($this->container($w));
    }

    /** Walk the children of a text container: block-level elements become blocks or Rich Text */
    private function walk(DOMNode $parent): void
    {
        $inline = '';
        $flushInline = function () use (&$inline) {
            if (trim(strip_tags($inline, '<img>'), " \t\n\r\xc2\xa0") !== '') $this->rich[] = '<p>' . $this->clean($inline) . '</p>';
            $inline = '';
        };
        foreach (iterator_to_array($parent->childNodes) as $n) {
            if ($n instanceof DOMText) { $inline .= htmlspecialchars($n->nodeValue, ENT_NOQUOTES); continue; }
            if (!$n instanceof DOMElement) continue;
            $tag = strtolower($n->tagName);
            if (in_array($tag, ['a', 'span', 'strong', 'b', 'em', 'i', 'u', 'br', 'sup', 'sub', 'code', 'small', 'mark'], true)
                && !$this->x->query('.//img|.//iframe|.//table', $n)->length) {
                $inline .= $n->ownerDocument->saveHTML($n);
                continue;
            }
            $flushInline();
            switch ($tag) {
                case 'h1': case 'h2': case 'h3': case 'h4': case 'h5': case 'h6':
                    $this->addHeading(self::text($n), $tag);
                    break;
                case 'p':
                    if ($this->x->query('.//img|.//iframe', $n)->length) { $this->walkMixed($n); break; }
                    $html = $this->clean($this->inner($n));
                    if (!self::isBlank($html)) $this->rich[] = "<p>$html</p>";
                    break;
                case 'ul': case 'ol':
                    if ($this->x->query('.//img|.//iframe', $n)->length) $this->notes[] = 'image inside a list kept as a link-less list (image dropped)';
                    $this->rich[] = "<$tag>" . $this->cleanList($n) . "</$tag>";
                    break;
                case 'blockquote':
                    $this->rich[] = '<blockquote>' . $this->cleanBlock($n) . '</blockquote>';
                    break;
                case 'table':
                    $this->rich[] = '<figure class="table"><table>' . $this->cleanTable($n) . '</table></figure>';
                    break;
                case 'dl':
                    $this->definitionList($n);
                    break;
                case 'img':
                    $this->imageBlock($n, null);
                    break;
                case 'iframe':
                    $this->iframe($n);
                    break;
                case 'figure':
                    $img = $this->x->query('.//img', $n)->item(0);
                    $table = $this->x->query('.//table', $n)->item(0);
                    if ($table) { $this->rich[] = '<figure class="table"><table>' . $this->cleanTable($table) . '</table></figure>'; break; }
                    if ($img) { $cap = $this->x->query('.//figcaption', $n)->item(0); $this->imageBlock($img, $cap ? self::text($cap) : null); break; }
                    $this->walk($n);
                    break;
                case 'hr':
                    $this->flush();
                    $this->blocks[] = nb('divider');
                    break;
                case 'script': case 'style': case 'noscript': case 'form': case 'button': case 'input':
                    break;
                default: // div, section, article, dd, center, …: look inside
                    $this->walk($n);
            }
        }
        $flushInline();
    }

    /** A paragraph holding images / iframes: text parts stay Rich Text, images and videos become blocks */
    private function walkMixed(DOMElement $p): void
    {
        $buf = '';
        $emit = function () use (&$buf) {
            $html = $this->clean($buf);
            if (!self::isBlank($html)) $this->rich[] = "<p>$html</p>";
            $buf = '';
        };
        foreach (iterator_to_array($p->childNodes) as $n) {
            $img = $n instanceof DOMElement ? ($n->tagName === 'img' ? $n : $this->x->query('.//img', $n)->item(0)) : null;
            $iframe = $n instanceof DOMElement ? ($n->tagName === 'iframe' ? $n : $this->x->query('.//iframe', $n)->item(0)) : null;
            if ($img) { $emit(); $this->imageBlock($img, null); continue; }
            if ($iframe) { $emit(); $this->iframe($iframe); continue; }
            $buf .= $n->ownerDocument->saveHTML($n);
        }
        $emit();
    }

    private function definitionList(DOMElement $dl): void
    {
        $rows = [];
        $term = null;
        foreach ($dl->childNodes as $c) {
            if (!$c instanceof DOMElement) continue;
            if ($c->tagName === 'dt') { if ($term !== null) $rows[] = ['col1' => $term, 'col2' => '']; $term = self::text($c); }
            elseif ($c->tagName === 'dd') {
                if ($term === null) { $this->walk($c); continue; } // a lone <dd> wrapper (pasted text): its content
                $rows[] = ['col1' => $term, 'col2' => $this->markdown($c)];
                $term = null;
            } elseif ($c->tagName === 'div') {
                $dt = $this->x->query('./dt', $c)->item(0);
                $dd = $this->x->query('./dd', $c)->item(0);
                if ($dt) $rows[] = ['col1' => self::text($dt), 'col2' => $dd ? $this->markdown($dd) : ''];
                else $this->walk($c);
            }
        }
        if ($term !== null) $rows[] = ['col1' => $term, 'col2' => ''];
        if ($rows) {
            $this->flush();
            $this->blocks[] = nb('specList', ['specItems' => $rows]);
        }
    }

    /** Inline HTML → *italic* / **bold** / [link](url) for the Table fields */
    private function markdown(DOMElement $n): string
    {
        $h = $this->clean($this->inner($n));
        $h = preg_replace('#</p>\s*<p>#', ' ', $h);
        $h = preg_replace('#<(em)>(.*?)</\1>#s', '*$2*', $h);
        $h = preg_replace('#<(strong)>(.*?)</\1>#s', '**$2**', $h);
        $h = preg_replace('#<a [^>]*href="([^"]+)"[^>]*>(.*?)</a>#s', '[$2]($1)', $h);
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($h), ENT_QUOTES | ENT_HTML5)));
    }

    // ---- HTML cleaning (CKEditor-friendly) ------------------------------------------------------------------------

    /** Clean inline HTML: only a / strong / em / br / sup / sub / code, no attributes but href (+ target) */
    private function clean(string $html): string
    {
        $d = new DOMDocument();
        libxml_use_internal_errors(true);
        $d->loadHTML('<?xml encoding="utf-8"?><div id="__c">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $x = new DOMXPath($d);
        $root = $x->query('//*[@id="__c"]')->item(0);
        $allowed = ['a', 'strong', 'em', 'br', 'sup', 'sub', 'code'];
        $rename = ['b' => 'strong', 'i' => 'em'];
        // deepest first, so unwrapping a parent never invalidates a child
        $all = array_reverse(iterator_to_array($x->query('.//*', $root)));
        foreach ($all as $el) {
            $tag = strtolower($el->tagName);
            if (isset($rename[$tag])) {
                $new = $d->createElement($rename[$tag]);
                while ($el->firstChild) $new->appendChild($el->firstChild);
                $el->parentNode->replaceChild($new, $el);
                $el = $new;
                $tag = $rename[$tag];
            }
            if (in_array($tag, ['script', 'style', 'img', 'iframe'], true)) { $el->parentNode->removeChild($el); continue; }
            if (!in_array($tag, $allowed, true)) {
                // unwrap; span with bold / italic style → strong / em
                $style = $el->getAttribute('style');
                $wrapTag = preg_match('/font-weight:\s*(bold|[6-9]00)/', $style) ? 'strong' : (preg_match('/font-style:\s*italic/', $style) ? 'em' : null);
                if ($wrapTag && trim($el->textContent) !== '') {
                    $new = $d->createElement($wrapTag);
                    while ($el->firstChild) $new->appendChild($el->firstChild);
                    $el->parentNode->replaceChild($new, $el);
                } else {
                    while ($el->firstChild) $el->parentNode->insertBefore($el->firstChild, $el);
                    $el->parentNode->removeChild($el);
                }
                continue;
            }
            foreach (iterator_to_array($el->attributes) as $a) {
                if (!($tag === 'a' && in_array($a->name, ['href', 'target'], true))) $el->removeAttribute($a->name);
            }
            if ($tag === 'a') {
                $href = $this->href($el->getAttribute('href'));
                if ($href === '') {
                    while ($el->firstChild) $el->parentNode->insertBefore($el->firstChild, $el);
                    $el->parentNode->removeChild($el);
                    continue;
                }
                $el->setAttribute('href', $href);
                if ($el->getAttribute('target') !== '_blank') $el->removeAttribute('target');
                else $el->setAttribute('rel', 'noopener');
            }
        }
        // empty strong / em
        foreach (array_reverse(iterator_to_array($x->query('.//strong|.//em', $root))) as $el) {
            if (trim($el->textContent, " \xc2\xa0") === '') {
                while ($el->firstChild) $el->parentNode->insertBefore($el->firstChild, $el);
                $el->parentNode->removeChild($el);
            }
        }
        $out = '';
        foreach ($root->childNodes as $c) $out .= $d->saveHTML($c);
        $out = preg_replace('/\s+/u', ' ', $out);
        $out = str_replace("\xc2\xa0", ' ', $out);
        return trim($out);
    }

    private function cleanList(DOMElement $list): string
    {
        $out = '';
        foreach ($list->childNodes as $li) {
            if (!$li instanceof DOMElement || $li->tagName !== 'li') continue;
            $nested = '';
            foreach (iterator_to_array($li->childNodes) as $c) {
                if ($c instanceof DOMElement && in_array($c->tagName, ['ul', 'ol'], true)) {
                    $nested .= "<{$c->tagName}>" . $this->cleanList($c) . "</{$c->tagName}>";
                    $li->removeChild($c);
                }
            }
            $html = preg_replace('#</?p>#', ' ', $this->inner($li));
            $out .= '<li>' . $this->clean($html) . $nested . '</li>';
        }
        return $out;
    }

    private function cleanBlock(DOMElement $n): string
    {
        $out = '';
        foreach ($n->childNodes as $c) {
            if ($c instanceof DOMElement && $c->tagName === 'p') $out .= '<p>' . $this->clean($this->inner($c)) . '</p>';
            else $out .= $this->clean($n->ownerDocument->saveHTML($c));
        }
        return $out;
    }

    /** A table with only thead / tbody / tr / th / td (+ colspan / rowspan) and clean cell content */
    private function cleanTable(DOMElement $table): string
    {
        $rows = iterator_to_array($this->x->query('.//tr', $table));
        $head = '';
        $body = '';
        foreach ($rows as $i => $tr) {
            $cells = '';
            $allTh = true;
            foreach ($tr->childNodes as $cell) {
                if (!$cell instanceof DOMElement || !in_array($cell->tagName, ['td', 'th'], true)) continue;
                if ($cell->tagName !== 'th') $allTh = false;
                $attrs = '';
                foreach (['colspan', 'rowspan'] as $a) if ($cell->getAttribute($a) && $cell->getAttribute($a) !== '1') $attrs .= " $a=\"" . (int)$cell->getAttribute($a) . '"';
                $html = preg_replace('#</p>\s*<p[^>]*>#', '<br>', $this->inner($cell));
                $cells .= "<{$cell->tagName}$attrs>" . $this->clean($html) . "</{$cell->tagName}>";
            }
            $inThead = $tr->parentNode instanceof DOMElement && $tr->parentNode->tagName === 'thead';
            if (($inThead || ($i === 0 && $allTh)) && $body === '') $head .= "<tr>$cells</tr>";
            else $body .= "<tr>$cells</tr>";
        }
        return ($head ? "<thead>$head</thead>" : '') . "<tbody>$body</tbody>";
    }

    /** Live links: migrated posts / categories / tags / the blog → root-relative; everything else stays as it is */
    private function href(string $href): string
    {
        $href = trim(html_entity_decode($href));
        if ($href === '' || $href === '#') return '';
        if (preg_match('#^https?://(www\.)?arrsys\.com(/[^?\#]*)?(.*)$#i', $href, $m)) {
            $path = $m[2] ?? '/';
            $first = explode('/', trim($path, '/'))[0] ?? '';
            if (in_array($first, ['category', 'tag', 'blog'], true) || isset($this->localSlugs[$first]) && substr_count(trim($path, '/'), '/') === 0) {
                return rtrim($path, '/') . '/' . ($m[3] ?? '');
            }
        }
        return $href;
    }

    // ---- media ----------------------------------------------------------------------------------------------------

    private function imageUrl(DOMElement $img): string
    {
        $best = $img->getAttribute('data-src') ?: $img->getAttribute('src');
        $bestW = 0;
        foreach (array_filter(array_map('trim', explode(',', $img->getAttribute('data-srcset') ?: $img->getAttribute('srcset')))) as $cand) {
            [$u, $w] = array_pad(preg_split('/\s+/', $cand), 2, '0w');
            if ((int)$w > $bestW) { $best = $u; $bestW = (int)$w; }
        }
        return $best;
    }

    private function imageBlock(DOMElement $img, ?string $caption): void
    {
        $url = $this->imageUrl($img);
        if (!$url || str_starts_with($url, 'data:')) return;
        $this->stats['liveImages']++;
        $asset = importImage($url, $img->getAttribute('alt'));
        if (!$asset) { $this->notes[] = "image not imported: $url"; return; }
        $w = (int)$img->getAttribute('width');
        $width = $w && $w < 300 ? 'small' : ($w && $w < 500 ? 'medium' : ($w && $w < 700 ? 'large' : 'full'));
        $link = $img->parentNode instanceof DOMElement && $img->parentNode->tagName === 'a' ? $img->parentNode->getAttribute('href') : null;
        if ($link && self::baseFile($link) !== self::baseFile($url)) $this->notes[] = "link on image {$asset->filename} dropped ($link)";
        $this->flush();
        $this->blocks[] = nb('image', array_filter(['mediaImage' => [$asset->id], 'caption' => $caption, 'imageWidth' => $width]));
        $this->stats['images']++;
    }

    private function image(DOMElement $w): void
    {
        $img = $this->x->query('.//img', $w)->item(0);
        if (!$img) return;
        $cap = $this->x->query('.//figcaption', $w)->item(0);
        $this->imageBlock($img, $cap ? self::text($cap) : null);
    }

    private function youtubeId(string $url): ?string
    {
        return preg_match('#(?:youtu\.be/|[?&]v=|embed/|shorts/|live/)([\w-]{11})#', $url, $m) ? $m[1] : null;
    }

    private function iframe(DOMElement $f): void
    {
        $src = $f->getAttribute('data-src') ?: $f->getAttribute('src');
        $id = $this->youtubeId($src);
        if (!$id) { $this->notes[] = "iframe not converted: $src"; return; }
        $this->flush();
        $this->blocks[] = nb('video', ['videoUrl' => "https://www.youtube.com/watch?v=$id"]);
    }

    private function video(DOMElement $w): void
    {
        $s = json_decode(html_entity_decode($w->getAttribute('data-settings')), true) ?: [];
        $url = $s['youtube_url'] ?? $s['vimeo_url'] ?? $s['hosted_url']['url'] ?? $s['external_url']['url'] ?? '';
        $id = $this->youtubeId($url);
        if (!$id) {
            $iframe = $this->x->query('.//iframe', $w)->item(0);
            if ($iframe) { $this->iframe($iframe); return; }
            $this->notes[] = 'video not converted (' . ($s['video_type'] ?? '?') . "): $url";
            return;
        }
        $this->flush();
        $this->blocks[] = nb('video', ['videoUrl' => "https://www.youtube.com/watch?v=$id"]);
    }

    private function accordion(DOMElement $w): void
    {
        $items = [];
        foreach ($this->x->query('.//*[contains(@class, "elementor-accordion-item") or contains(@class, "elementor-toggle-item")]', $w) as $item) {
            $q = $this->x->query('.//*[contains(@class, "elementor-accordion-title") or contains(@class, "elementor-toggle-title")]', $item)->item(0);
            $a = $this->x->query('.//*[contains(@class, "elementor-tab-content")]', $item)->item(0);
            if (!$q || !$a) continue;
            // the answer: same rules as a text widget, collected into one rich text
            $saved = [$this->blocks, $this->rich];
            $this->blocks = []; $this->rich = [];
            $this->walk($a);
            $this->flush();
            $answer = '';
            foreach ($this->blocks as $b) {
                if ($b['type'] === 'richText') $answer .= $b['fields']['richText'];
                elseif ($b['type'] === 'heading') $answer .= '<h4>' . htmlspecialchars($b['fields']['headingText']) . '</h4>';
                elseif ($b['type'] === 'specList') foreach ($b['fields']['specItems'] as $r) $answer .= '<p><strong>' . htmlspecialchars($r['col1']) . '</strong> ' . htmlspecialchars($r['col2']) . '</p>';
                else $this->notes[] = "{$b['type']} inside an FAQ answer dropped";
            }
            [$this->blocks, $this->rich] = $saved;
            $items[] = nb('faqItem', ['headingText' => self::text($q), 'richText' => $answer]);
        }
        if ($items) {
            $this->flush();
            $this->blocks[] = nb('faq', [], $items);
        }
    }

    private function gallery(DOMElement $w): void
    {
        $urls = [];
        $alts = [];
        foreach ($this->x->query('.//a[@href]', $w) as $a) {
            $href = $a->getAttribute('href');
            if (preg_match('/\.(jpe?g|png|webp|gif)$/i', parse_url($href, PHP_URL_PATH) ?? '')) {
                $urls[] = $href;
                $img = $this->x->query('.//img', $a)->item(0);
                $alts[$href] = $img ? $img->getAttribute('alt') : $a->getAttribute('data-elementor-lightbox-title');
            }
        }
        if (!$urls) {
            foreach ($this->x->query('.//img', $w) as $img) { $u = $this->imageUrl($img); $urls[] = $u; $alts[$u] = $img->getAttribute('alt'); }
            foreach ($this->x->query('.//*[contains(@style, "background-image")]', $w) as $el) {
                if (preg_match('/url\(\s*[\'"]?([^\'")]+)/', html_entity_decode($el->getAttribute('style')), $m)) { $urls[] = $m[1]; $alts[$m[1]] = $el->getAttribute('aria-label'); }
            }
        }
        $ids = [];
        foreach (array_unique($urls) as $u) {
            $this->stats['liveImages']++;
            $asset = importImage($u, $alts[$u] ?? null);
            if ($asset) $ids[] = $asset->id; else $this->notes[] = "gallery image not imported: $u";
        }
        $ids = array_values(array_unique($ids));
        if (!$ids) return;
        $s = json_decode(html_entity_decode($w->getAttribute('data-settings')), true) ?: [];
        $cols = (int)($s['columns'] ?? $s['slides_to_show'] ?? $s['slides_per_view'] ?? 3);
        if (preg_match('/gallery-columns-(\d)/', $this->inner($w), $m)) $cols = (int)$m[1];
        $this->flush();
        $this->blocks[] = nb('gallery', ['galleryImages' => $ids, 'galleryColumns' => (string)max(2, min(4, $cols ?: 3))]);
        $this->stats['images'] += count($ids);
    }

    private function button(DOMElement $w): void
    {
        $a = $this->x->query('.//a[contains(@class, "elementor-button")]', $w)->item(0);
        $label = $a ? self::text($this->x->query('.//*[contains(@class, "elementor-button-text")]', $a)->item(0) ?? $a) : '';
        $href = $a ? $this->href($a->getAttribute('href')) : '';
        if ($href === '' || $label === '') { $this->notes[] = "button \"$label\" without a link left out"; return; }
        $this->flush();
        $type = str_starts_with($href, 'tel:') ? 'tel' : (str_starts_with($href, 'mailto:') ? 'email' : 'url');
        if ($type !== 'url') { $this->notes[] = "button \"$label\" ($href) is a $type link; Button links take web addresses, kept as a text link"; $this->rich[] = '<p><a href="' . htmlspecialchars($href) . '">' . htmlspecialchars($label) . '</a></p>'; return; }
        $this->blocks[] = nb('button', ['ctaLink' => ['type' => 'url', 'value' => $href, 'label' => $label], 'buttonStyle' => 'primary']);
    }
}
