<?php
/**
 * Blog migration, Phase 4: the pilot post "Roll-to-Roll vs. Sheet-Fed Label Printing: Throughput & Cost", built with the
 * design's semantic blocks (Intro, Key Takeaways, Heading + answer lead, Steps, Spec List, Callout / Note, Comparison,
 * Data Table, Product List, FAQ, Checklist, Call to Action) from the design's article markup
 * (Arrowsystem_local/blogs/blog-detail.html, which is the live article restructured), so every block template can be
 * checked against the design. Date, categories, tags and SEO come from the live post; images from the design.
 * The import (05_import.php) skips posts that already exist, so this hand-built version stays.
 * Re-run with --force to rebuild the post's blocks.
 * Run from the project root: /opt/lampp/bin/php storage/imports/blog/04_pilot.php
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_helpers.php';

use craft\elements\Entry;

const DESIGN = '/home/avadh/Desktop/Arrowsystem_local/blogs/blog-detail.html';
const DESIGN_IMAGES = '/home/avadh/Desktop/Arrowsystem_local/assets/blog/web/';
const SLUG = 'roll-to-roll-vs-sheet-fed-label-printing-throughput-cost';

$force = in_array('--force', $argv, true);
$existing = Entry::find()->section('blog')->slug(SLUG)->status(null)->one();
if ($existing && !$force) {
    say('Pilot post exists (id ' . $existing->id . '); use --force to rebuild it.');
    exit;
}

$doc = new DOMDocument();
libxml_use_internal_errors(true);
$doc->loadHTML('<?xml encoding="utf-8"?>' . file_get_contents(DESIGN));
$x = new DOMXPath($doc);
$q = fn(string $path, ?DOMNode $ctx = null) => $x->query($path, $ctx);
$one = fn(string $path, ?DOMNode $ctx = null) => $x->query($path, $ctx)->item(0);
$text = fn(?DOMNode $n) => $n ? trim(preg_replace('/\s+/u', ' ', $n->textContent)) : '';
$cls = fn(DOMElement $n) => $n->getAttribute('class');

/** Inner HTML without class / id / style attributes (CKEditor content) */
function inner(DOMElement $n): string {
    $h = '';
    foreach ($n->childNodes as $c) $h .= $n->ownerDocument->saveHTML($c);
    $h = preg_replace('/\s(class|id|style|scope|role|tabindex|aria-[a-z]+)="[^"]*"/', '', $h);
    return trim(preg_replace('/\s+/u', ' ', $h));
}
/** Inline HTML → the Table fields' markdown (*italic*, **bold**, [link](url)) */
function md(DOMElement $n): string {
    $h = inner($n);
    $h = preg_replace('#<(em|i)>(.*?)</\1>#s', '*$2*', $h);
    $h = preg_replace('#<(strong|b)>(.*?)</\1>#s', '**$2**', $h);
    $h = preg_replace('#<a [^>]*href="([^"]+)"[^>]*>(.*?)</a>#s', '[$2]($1)', $h);
    return trim(html_entity_decode(strip_tags($h), ENT_QUOTES | ENT_HTML5));
}
$plain = fn(DOMElement $n) => trim(html_entity_decode(preg_replace('/\s+/u', ' ', $n->textContent), ENT_QUOTES | ENT_HTML5));

$article = $one('//article[@data-article]');
// The design's short "On this page" labels, by anchor
$tocLabels = [];
foreach ($q('//details[@data-toc]//a') as $a) $tocLabels[ltrim($a->getAttribute('href'), '#')] = trim($a->textContent);
$blocks = [];
$rich = [];   // plain paragraphs / lists collected into one Rich Text block
$flush = function () use (&$rich, &$blocks) {
    if ($rich) $blocks[] = nb('richText', ['richText' => implode('', $rich)]);
    $rich = [];
};
$images = 0;

foreach ($article->childNodes as $n) {
    if (!$n instanceof DOMElement) continue;
    $tag = $n->tagName;
    $c = $cls($n);

    if ($tag === 'p' && $c === '') { $rich[] = '<p>' . inner($n) . '</p>'; continue; }
    if ($tag === 'ul' && $c === '') { $rich[] = '<ul>' . inner($n) . '</ul>'; continue; }
    if ($tag === 'p' && $c === 'answer') {
        // the lead under the previous Heading block
        $last = array_key_last($blocks);
        $blocks[$last]['fields']['answerLead'] = $plain($n);
        continue;
    }
    $flush();

    if ($tag === 'p' && $c === 'post-intro') {
        $blocks[] = nb('intro', ['shortRichText' => '<p>' . inner($n) . '</p>']);
    } elseif ($tag === 'section' && str_contains($c, 'takeaways')) {
        $h2 = $one('.//h2', $n);
        $blocks[] = nb('takeaways', [
            'headingText' => $plain($h2), 'anchorId' => $h2->getAttribute('id'),
            'listItems' => array_map(fn($li) => ['col1' => md($li)], iterator_to_array($q('.//li', $n))),
        ]);
    } elseif (in_array($tag, ['h2', 'h3', 'h4'], true)) {
        $blocks[] = nb('heading', array_filter([
            'headingText' => $plain($n), 'headingLevel' => $tag,
            'anchorId' => $n->getAttribute('id') ?: null, 'showInContents' => $tag === 'h2',
            'tocLabel' => ($label = $tocLabels[$n->getAttribute('id')] ?? null) && $label !== $plain($n) ? $label : null,
        ], fn($v) => $v !== null));
    } elseif ($tag === 'ol' && $c === 'steps') {
        $steps = [];
        foreach ($q('./li', $n) as $li) {
            $steps[] = nb('step', ['headingText' => $plain($one('./strong', $li)), 'sectionBody' => $plain($one('./span', $li))]);
        }
        $blocks[] = nb('steps', [], $steps);
    } elseif ($tag === 'figure' && str_contains($c, 'prose-figure')) {
        $img = $one('.//img', $n);
        $asset = importImage(DESIGN_IMAGES . basename($img->getAttribute('src')), $img->getAttribute('alt'));
        $images++;
        $blocks[] = nb('image', ['mediaImage' => $asset ? [$asset->id] : [], 'caption' => $plain($one('.//figcaption', $n))]);
    } elseif ($tag === 'dl' && str_contains($c, 'spec-list')) {
        $rows = [];
        foreach ($q('./div', $n) as $row) $rows[] = ['col1' => $plain($one('./dt', $row)), 'col2' => md($one('./dd', $row))];
        $blocks[] = nb('specList', ['specItems' => $rows]);
    } elseif ($tag === 'p' && in_array($c, ['callout', 'note'], true)) {
        $blocks[] = nb('callout', ['shortRichText' => '<p>' . inner($n) . '</p>', 'calloutStyle' => $c]);
    } elseif ($tag === 'div' && $c === 'compare') {
        $cols = [];
        foreach ($q('./div', $n) as $col) {
            $cols[] = nb('compareColumn', [
                'headingText' => $plain($one('./h3', $col)),
                'listItems' => array_map(fn($li) => ['col1' => md($li)], iterator_to_array($q('.//li', $col))),
                'compareStyle' => str_contains($cls($col), '--con') ? 'con' : 'pro',
            ]);
        }
        $blocks[] = nb('compare', [], $cols);
    } elseif ($tag === 'div' && $c === 'table-wrap') {
        $table = $one('.//table', $n);
        $caption = $one('./caption', $table);
        $captionText = $caption ? $plain($caption) : '';
        if ($caption) $table->removeChild($caption);
        $blocks[] = nb('dataTable', ['caption' => $captionText, 'tableText' => '<figure class="table"><table>' . inner($table) . '</table></figure>']);
    } elseif ($tag === 'ul' && $c === 'product-list') {
        $items = [];
        foreach ($q('./li', $n) as $li) {
            $a = $one('.//h4//a', $li);
            $items[] = nb('productItem', [
                'productTag' => $plain($one('./p[contains(@class, "product-item__tag")]', $li)),
                'productName' => $plain($one('./h4', $li)),
                'ctaLink' => $a ? ['type' => 'url', 'value' => $a->getAttribute('href'), 'label' => $plain($a)] : null,
                'sectionBody' => $plain($one('./p[not(@class)]', $li)),
            ]);
        }
        $blocks[] = nb('productList', [], $items);
    } elseif ($tag === 'div' && $c === 'faq') {
        $items = [];
        foreach ($q('./details', $n) as $d) {
            $items[] = nb('faqItem', ['headingText' => $plain($one('./summary', $d)), 'richText' => inner($one('./div', $d))]);
        }
        $blocks[] = nb('faq', [], $items);
    } elseif ($tag === 'ul' && $c === 'checklist') {
        $blocks[] = nb('checklist', ['listItems' => array_map(fn($li) => ['col1' => md($li)], iterator_to_array($q('./li', $n)))]);
    } elseif ($tag === 'aside' && str_contains($c, 'post-cta')) {
        $a = $one('.//a', $n);
        $blocks[] = nb('cta', [
            'ctaHeading' => $plain($one('.//h2', $n)),
            'ctaText' => implode("\n\n", array_map($plain, iterator_to_array($q('./p', $n)))),
            'ctaLink' => ['type' => 'url', 'value' => $a->getAttribute('href'), 'label' => $plain($a)],
        ]);
    } elseif ($tag === 'footer') {
        // post foot: made by the template
    } else {
        say("  ? not converted: <$tag class=\"$c\">");
    }
}
$flush();

// ---- The entry ----
$live = liveData()['posts'][SLUG] ?? throw new Exception('Live post not in data/');
$h1 = $one('//h1[@id="page-title"]');
$title = trim(preg_replace('#<span>(.*?)</span>#', '[$1]', html_entity_decode(inner($h1), ENT_QUOTES | ENT_HTML5)));
$coverImg = $one('//figure[contains(@class, "post-cover")]//img');
$cover = importImage(DESIGN_IMAGES . basename($coverImg->getAttribute('src')), $coverImg->getAttribute('alt'));

$entry = $existing ?? new Entry([
    'sectionId' => Craft::$app->getEntries()->getSectionByHandle('blog')->id,
    'typeId' => Craft::$app->getEntries()->getEntryTypeByHandle('blogPost')->id,
]);
$entry->title = $title;
$entry->slug = SLUG;
$entry->postDate = postDate($live);
$entry->setAuthorIds([1]);
$entry->setFieldValues(array_merge([
    'sectionLead' => $plain($one('//p[contains(@class, "post-hero__lead")]')),
    'featuredImage' => $cover ? [$cover->id] : [],
    'articleBody' => builderValue($blocks),
    'blogCategories' => postCategories($live),
    'blogTags' => postTags($live),
], postSeo($live, html_entity_decode($live['title']['rendered'], ENT_QUOTES | ENT_HTML5))));
Craft::$app->getElements()->saveElement($entry) || fail('pilot post', $entry);

say(sprintf('Pilot post saved: id %d, /%s/ — %d blocks (+ child blocks), %d images, %d categories, %d tags',
    $entry->id, $entry->uri, count($blocks), $images + 1, count(postCategories($live)), count(postTags($live))));
foreach ($GLOBALS['report'] ?? [] as $line) say("  ! $line");
