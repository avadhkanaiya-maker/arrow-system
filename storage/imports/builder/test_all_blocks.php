<?php
/**
 * Builder test pages: one blog post, one product and one industry holding EVERY page-builder block (and each option:
 * heading levels, callout styles, image widths, gallery columns, button styles, pro / con columns, video in the
 * header / page, products picked / from a category …), filled with dummy content. Checked by test_all_blocks.mjs.
 *   /builder-test-post/   /product/builder-test-product/   /builder-test-industry/
 * Run from the project root: /opt/lampp/bin/php storage/imports/builder/test_all_blocks.php [create|delete]
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\elements\Asset;
use craft\elements\Entry;

$elements = Craft::$app->getElements();
$pages = [['blog', 'articleBody', 'builder-test-post', 'Builder Test Post'], ['products', 'productBuilder', 'builder-test-product', 'Builder Test Product'],
    ['industries', 'industryBuilder', 'builder-test-industry', 'Builder Test Industry']];
if (($argv[1] ?? 'create') === 'delete') {
    foreach ($pages as [$sec, , $slug]) if ($e = Entry::find()->section($sec)->slug($slug)->status(null)->one()) { $elements->deleteElement($e, true); say("  - $slug"); }
    exit;
}

$imgs = Asset::find()->volume('images')->kind('image')->folderPath('industries/')->limit(9)->ids();
$pdf = Asset::find()->volume('documents')->one()->id;
$products = Entry::find()->section('products')->slug(['arrowjet-aqua-330r-lite-2', 'arrowjet-eco-330r', 'arrowjet-aqua-800m'])->ids();
$category = Entry::find()->section('productCategories')->slug('digital-label-printers')->one()->id;
$industries = Entry::find()->section('industries')->slug(['chemical-label-printing', 'signs-and-graphics', 'aircraft-interior-placards'])->ids();
assert(count($imgs) === 9 && count($products) === 3 && count($industries) === 3);
$url = fn($label, $u) => ['type' => 'url', 'value' => $u, 'label' => $label];
$rows = fn(...$t) => array_map(fn($x) => ['text' => $x], $t);
$specs = [['term' => 'Print width', 'description' => '12.75 in (324 mm)'], ['term' => 'Speed', 'description' => 'Up to **150 ft/min**']];
$nested = function (string $type, array $items) { $e = []; foreach ($items as $i => $f) $e["new$i"] = ['type' => $type, 'fields' => $f]; return ['entries' => $e, 'sortOrder' => array_keys($e)]; };

// the 20 common blocks, every option
$common = [
    ['heading', ['headingText' => 'Test H2 heading', 'headingLevel' => 'h2', 'answerLead' => 'A short answer lead under the H2.', 'showInContents' => true, 'tocLabel' => 'H2 in contents', 'anchorId' => 'test-h2']],
    ['heading', ['headingText' => 'Test H3 heading', 'headingLevel' => 'h3']],
    ['heading', ['headingText' => 'Test H4 heading', 'headingLevel' => 'h4']],
    ['intro', ['shortRichText' => '<p>Intro block: the opening paragraph, larger text.</p>']],
    ['richText', ['richText' => '<h3>Rich text H3</h3><p>Paragraph with <strong>bold</strong>, <em>italic</em> and a <a href="/industries/">link</a>.</p><ul><li>Bullet one</li><li>Bullet two</li></ul><ol><li>Number one</li><li>Number two</li></ol><h4>Rich text H4</h4><blockquote><p>A quote inside rich text.</p></blockquote>']],
    ['callout', ['shortRichText' => '<p><strong>Callout:</strong> the key point, with a red bar.</p>', 'calloutStyle' => 'callout']],
    ['callout', ['shortRichText' => '<p>Note: small print in the note style.</p>', 'calloutStyle' => 'note']],
    ['quote', ['quoteText' => 'A pull quote for testing the quote block.', 'quoteSource' => 'Test Person, Arrow Systems']],
    ['takeaways', ['headingText' => 'Key takeaways', 'listItems' => $rows('First takeaway', 'Second takeaway with *italic*'), 'anchorId' => 'test-takeaways']],
    ['checklist', ['listItems' => $rows('Checklist item one', 'Checklist item **two**')]],
    ['steps', ['stepItems' => $nested('step', [['headingText' => 'Step one', 'sectionBody' => 'Do the first thing.'], ['headingText' => 'Step two', 'sectionBody' => 'Then the second.'], ['headingText' => 'Step three']])]],
    ['specList', ['specItems' => $specs]],
    ['compare', ['compareColumns' => $nested('compareColumn', [['headingText' => 'Pros column', 'listItems' => $rows('Pro one', 'Pro two'), 'compareStyle' => 'pro'], ['headingText' => 'Cons column', 'listItems' => $rows('Con one', 'Con two'), 'compareStyle' => 'con']])]],
    ['image', ['mediaImage' => [$imgs[0]], 'caption' => 'Image full width', 'imageWidth' => 'full']],
    ['image', ['mediaImage' => [$imgs[1]], 'caption' => 'Image large', 'imageWidth' => 'large']],
    ['image', ['mediaImage' => [$imgs[2]], 'caption' => 'Image medium', 'imageWidth' => 'medium']],
    ['image', ['mediaImage' => [$imgs[3]], 'caption' => 'Image small', 'imageWidth' => 'small']],
    ['gallery', ['galleryImages' => array_slice($imgs, 0, 4), 'galleryColumns' => '2']],
    ['gallery', ['galleryImages' => array_slice($imgs, 0, 3), 'galleryColumns' => '3']],
    ['gallery', ['galleryImages' => array_slice($imgs, 0, 4), 'galleryColumns' => '4']],
    ['video', ['videoUrl' => 'https://youtu.be/PbjRZJS6dbQ', 'caption' => 'Common video block']],
    ['dataTable', ['caption' => 'Data table caption', 'tableText' => '<table><thead><tr><th>Model</th><th>Speed</th></tr></thead><tbody><tr><td>Aqua 330R</td><td>150 ft/min</td></tr><tr><td>Eco 330R</td><td>90 ft/min</td></tr></tbody></table>']],
    ['productList', ['productItems' => $nested('productItem', [
        ['productTag' => 'Printer', 'productName' => 'Product list item (entry link)', 'ctaLink' => ['type' => 'entry', 'value' => $products[0], 'label' => 'View'], 'sectionBody' => 'Linked to a product entry.'],
        ['productName' => 'Product list item (URL)', 'ctaLink' => $url('View', '/industries/'), 'sectionBody' => 'Linked to a URL.']])]],
    ['button', ['ctaLink' => $url('Primary button', '/industries/'), 'buttonStyle' => 'primary']],
    ['button', ['ctaLink' => $url('Outline button', '/industries/'), 'buttonStyle' => 'outline']],
    ['button', ['ctaLink' => $url('Link-style button', '/industries/'), 'buttonStyle' => 'link']],
    ['cta', ['ctaHeading' => 'Call to action heading', 'ctaText' => 'Call to action text.', 'ctaLink' => $url('Contact us', '/industries/')]],
    ['faq', ['faqItems' => $nested('faqItem', [['headingText' => 'FAQ question one?', 'richText' => '<p>Answer one.</p>'], ['headingText' => 'FAQ question two?', 'richText' => '<p>Answer two.</p>']])]],
    ['divider', []],
    ['html', ['htmlCode' => '<div class="test-html-block"><strong>Raw HTML block</strong></div>']],
];

$own = [
    'products' => [
        ['productVideo', ['videoUrl' => 'https://youtu.be/PbjRZJS6dbQ', 'videoCaption' => 'Header video', 'placement' => 'header']],
        ['tabsBlock', ['productTabs' => ['entries' => [
            'new1' => ['type' => 'textTab', 'fields' => ['headingText' => 'Overview', 'richText' => '<h4>Tab heading</h4><p>Text tab content.</p><ul><li>Tab bullet</li></ul>']],
            'new2' => ['type' => 'specsTab', 'fields' => ['headingText' => 'Features', 'specItems' => $specs]],
            'new3' => ['type' => 'downloadsTab', 'fields' => ['headingText' => 'Brochure', 'downloadItems' => $nested('downloadItem', [['headingText' => 'Test brochure', 'downloadFile' => [$pdf], 'caption' => 'English'], ['headingText' => 'Test brochure ES', 'downloadFile' => [$pdf], 'caption' => 'Español']])]],
        ], 'sortOrder' => ['new1', 'new2', 'new3']]]],
        ['specsBlock', ['headingText' => 'Specifications block', 'specItems' => $specs]],
        ['downloadsBlock', ['headingText' => 'Downloads block', 'downloadItems' => $nested('downloadItem', [['headingText' => 'Block download', 'downloadFile' => [$pdf], 'caption' => 'English']])]],
        ['productVideo', ['videoUrl' => 'https://youtu.be/wmVK2QAuw9c', 'videoCaption' => 'Page video', 'placement' => 'page']],
    ],
    'industries' => [
        ['industryHeader', ['eyebrow' => 'Test industry', 'sectionHeading' => 'Builder Test [Industry]', 'sectionLead' => "First intro paragraph.\n\nSecond intro paragraph.", 'listItems' => $rows('Tag one', 'Tag two'), 'mediaImage' => [$imgs[4]], 'primaryCta' => $url('Talk to a Specialist', '#contact'), 'secondaryCta' => $url('Recommended Equipment', '#equipment')]],
        ['productShowcase', ['eyebrow' => 'Recommended equipment', 'sectionHeading' => 'Picked [products]', 'sectionLead' => 'Three picked products.', 'productSource' => 'picked', 'selectedProducts' => $products, 'ctaLink' => $url('All products', '/industries/'), 'anchorId' => 'equipment']],
        ['productShowcase', ['sectionHeading' => 'Products from a [category]', 'productSource' => 'category', 'selectedCategories' => [$category], 'productLimit' => 4]],
        ['productShowcase', ['sectionHeading' => 'One [product]', 'productSource' => 'picked', 'selectedProducts' => [$products[1]]]],
        ['contentBlock', ['eyebrow' => 'With image', 'headingText' => 'Content block with image', 'richText' => '<p>Text beside an image.</p><ul><li>List item</li></ul>', 'mediaImage' => [$imgs[5]], 'ctaLink' => $url('Content link', '#contact'), 'anchorId' => 'cb-image']],
        ['contentBlock', ['headingText' => 'Content block with video', 'richText' => '<p>Text beside a video.</p>', 'videoUrl' => 'https://youtu.be/R19vmaghzUU']],
        ['contentBlock', ['eyebrow' => 'Text only', 'headingText' => 'Content block without media', 'richText' => '<p>Heading left, text right.</p>']],
        ['contentBlock', ['richText' => '<p>Plain text block: no heading, no media, two columns of text. ' . str_repeat('Filler sentence for the columns. ', 25) . '</p>']],
        ['photoGallery', ['eyebrow' => 'Gallery', 'sectionHeading' => 'Photo [gallery]', 'galleryImages' => $imgs]],
        ['photoGallery', ['galleryImages' => array_slice($imgs, 0, 2)]],
        ['ctaBand', ['sectionHeading' => 'Not sure which [equipment] fits?', 'sectionLead' => 'CTA band text.', 'ctaLink' => $url('Request a Quote', '/industries/')]],
        ['contactBlock', ['eyebrow' => 'Test industry', 'sectionHeading' => 'Contact With Our Team Now', 'sectionLead' => 'Contact block text.', 'anchorId' => 'contact']],
        ['otherIndustries', ['eyebrow' => 'Keep exploring', 'sectionHeading' => 'Other [Industries]', 'relatedIndustries' => $industries, 'ctaLink' => $url('View all industries', '/industries/')]],
    ],
];

foreach ($pages as [$sec, $field, $slug, $title]) {
    $blocks = $sec === 'products' ? array_merge(array_slice($own[$sec], 0, 2), $common, array_slice($own[$sec], 2))
        : ($sec === 'industries' ? array_merge(array_slice($own[$sec], 0, 8), $common, array_slice($own[$sec], 8)) : $common);
    $s = Craft::$app->getEntries()->getSectionByHandle($sec);
    $e = Entry::find()->section($sec)->slug($slug)->status(null)->one()
        ?? new Entry(['sectionId' => $s->id, 'typeId' => $s->getEntryTypes()[0]->id, 'slug' => $slug]);
    $e->title = $title;
    $value = ['entries' => [], 'sortOrder' => []];
    foreach ($blocks as $i => [$type, $fields]) { $value['entries']["new$i"] = ['type' => $type, 'fields' => $fields]; $value['sortOrder'][] = "new$i"; }
    $e->setFieldValue($field, $value);
    if ($sec === 'industries') $e->setFieldValues(['featuredImage' => [$imgs[6]], 'cardTagline' => 'Test summary', 'menuIcon' => 'tag', 'showOnListing' => false]);
    if (!$elements->saveElement($e)) fail("page $slug", $e);
    $saved = (int)Entry::find()->ownerId($e->id)->fieldId(Craft::$app->getFields()->getFieldByHandle($field)->id)->status(null)->count();
    assert($saved === count($blocks), "$slug: $saved of " . count($blocks) . " blocks saved");   // Craft ignores keys not starting with "new"
    say("  + $slug: " . count($blocks) . ' blocks → ' . $e->url);
}
