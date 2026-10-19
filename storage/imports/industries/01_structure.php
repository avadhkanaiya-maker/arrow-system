<?php
/**
 * Industries, structure (INDUSTRIES_MIGRATION_PLAN.md, with the page builder asked for on 2026-10-14).
 * Design: Arrowsystem_local/industries/{index,industry-detail}.html and homepage section E (index.html).
 *
 *   Industries (industries)          Structure, 1 level, URL {slug} (the live root URLs), industries/_entry
 *   Industry:      Name, Slug · Card: Card Photo, Card Summary, Icon, Show on Listing · Page Builder · SEO
 *   Page Builder (industryBuilder), Matrix in Blocks view, icon-grid menu (modules/builderui):
 *     Sections: Industry Header (breadcrumb, small heading, H1 with [red] words, intro, applications, image, 2 buttons)
 *               Recommended Products (DYNAMIC: pick products, or show products from product categories; how many;
 *               "View all" link) · Content Block (image + small heading, heading, text, link; consecutive blocks
 *               alternate the image side) · Contact Form (panel + form; phones / email / address from Site Settings)
 *               Other Industries (picked, else the next ones in the listing order) · CTA Band (maroon help band)
 *     Media:    Photo Gallery (heading + photos, lightbox) · Video (shared with the blog)
 *     Text:     Rich Text (shared with the blog)
 *   Industries Page (industriesPage)  Single, URL industries, industries/_index: small heading, heading, intro,
 *                                     help band heading + text (buttons from Site Settings), SEO
 *   Homepage block "Industries Carousel" (industryCarousel), section E: small heading, heading, View All link,
 *     tiles (Industry Tile: Link to an industry or any URL; Name / Photo empty = the linked industry's own)
 *   primaryCta / secondaryCta (shared button fields) also accept #anchors and /root-relative URLs now.
 *
 * Idempotent (existing items are kept). Back up first. Run from the project root:
 *   /opt/lampp/bin/php storage/imports/industries/01_structure.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';
require dirname(__DIR__) . '/products/_helpers.php';

use craft\fieldlayoutelements\CustomField;
use craft\fields\Dropdown;
use craft\fields\Entries;
use craft\fields\Lightswitch;
use craft\fields\Matrix;
use craft\fields\Number;

$fields = Craft::$app->getFields();
$entries = Craft::$app->getEntries();
$f = fn(string $handle, array $opts = []) => new CustomField($fields->getFieldByHandle($handle) ?? throw new Exception("No field $handle"), $opts);
$type = fn(string $h) => $entries->getEntryTypeByHandle($h) ?? throw new Exception("No entry type $h");

/** A Matrix field (Blocks view) with [group, entry type] rows, or plain entry types */
function matrixField(string $name, string $handle, array $types, array $opts = []) {
    $fields = Craft::$app->getFields();
    if ($m = $fields->getFieldByHandle($handle)) return $m;
    $m = new Matrix(array_merge(['name' => $name, 'handle' => $handle, 'viewMode' => Matrix::VIEW_MODE_BLOCKS], $opts));
    $m->setEntryTypes(array_map(fn($t) => is_array($t) ? ['uid' => $t[1]->uid, 'group' => $t[0]] : $t, $types));
    $fields->saveField($m) || fail("field $handle", $m);
    say("  + field $handle");
    return $m;
}

// ---- Shared button fields: allow #anchors and /root-relative URLs (the header's "#contact" / "#equipment") ----------
say('Button fields');
foreach (['primaryCta', 'secondaryCta'] as $h) {
    $link = $fields->getFieldByHandle($h);
    $ts = $link->typeSettings ?? [];
    if (($ts['url']['allowAnchors'] ?? false) && ($ts['url']['allowRootRelativeUrls'] ?? false)) continue;
    $ts['url'] = array_merge($ts['url'] ?? [], ['allowAnchors' => true, 'allowRootRelativeUrls' => true]);
    $link->typeSettings = $ts;
    $fields->saveField($link) || fail("field $h", $link);
    say("  ~ $h: #anchors + /root-relative URLs allowed");
}

// ---- Section first (Other Industries picks from it) -----------------------------------------------------------------
say('Industries');
field(Lightswitch::class, ['name' => 'Show on Listing', 'handle' => 'showOnListing', 'default' => true,
    'instructions' => 'Off = the page works, but it isn\'t a card on /industries/ (e.g. Corrugation Printing, as live).']);
$industry = entryType('Industry', 'industry', [
    'Card' => [
        $f('featuredImage', ['label' => 'Card Photo', 'instructions' => 'The photo on industry cards (4:3, e.g. 800 × 600).']),
        $f('cardTagline', ['label' => 'Card Summary', 'instructions' => 'One or two sentences on the industry cards.']),
        $f('menuIcon', ['label' => 'Icon', 'instructions' => 'The small icon on the industry cards and the listing header.']),
        $f('showOnListing'),
    ],
], ['icon' => 'industry', 'showSlug' => true, 'titleLabel' => 'Industry Name',
    'description' => 'An industry page at /slug/ (keep the slug as on the live site, or the old URL is lost).']);
$section = section('Industries', 'industries', 'structure', [$industry], '{slug}', 'industries/_entry', ['maxLevels' => 1]);

// ---- Blocks -----------------------------------------------------------------------------------------------------------
say('Blocks');
field(Entries::class, ['name' => 'Products', 'handle' => 'selectedProducts', 'maxRelations' => 12,
    'instructions' => 'Pick the products and drag them into order (used when “Show” = Picked products).',
    'sources' => ['section:' . $entries->getSectionByHandle('products')->uid], 'showSearchInput' => true]);
field(Dropdown::class, ['name' => 'Show', 'handle' => 'productSource', 'options' => options([
    'picked' => 'Picked products', 'category' => 'Products from the categories below'], 'picked'),
    'instructions' => 'Picked = exactly the products you choose. Categories = products from the chosen product categories, filled in automatically as products are added.']);
field(Number::class, ['name' => 'How Many', 'handle' => 'productLimit', 'min' => 1, 'max' => 24, 'decimals' => 0, 'defaultValue' => 3,
    'instructions' => 'For “Products from the categories”: how many cards (3 per row).']);
field(Entries::class, ['name' => 'Other Industries', 'handle' => 'relatedIndustries', 'maxRelations' => 3,
    'instructions' => 'Optional: up to 3 industries. Empty = the next industries in the listing order (never this page).',
    'sources' => ["section:{$section->uid}"], 'showSearchInput' => true]);

$header = blockType('Industry Header', 'industryHeader', 'heading', [
    $f('eyebrow', ['instructions' => 'e.g. “Flexible Packaging”.']),
    $f('sectionHeading', ['label' => 'Heading (H1)', 'required' => true, 'instructions' => 'Put [brackets] around the red words, e.g. “Flexible Solutions for Your [Flexible Packaging]”.']),
    $f('sectionLead', ['label' => 'Intro']),
    $f('listItems', ['label' => 'Applications', 'instructions' => 'Short tags under the intro, e.g. “Coffee bags”.']),
    $f('mediaImage', ['label' => 'Image', 'instructions' => '16:9 photo (e.g. 1280 × 720). Set its alt text.']),
    $f('primaryCta', ['label' => 'Button 1', 'instructions' => 'e.g. “Talk to a Specialist” → #contact.']),
    $f('secondaryCta', ['label' => 'Button 2', 'instructions' => 'e.g. “Recommended Equipment” → #equipment.']),
], '{sectionHeading}', 'The top of the page: breadcrumb, title (H1), intro, applications, image, buttons. Use once, first.');
$products = blockType('Recommended Products', 'productShowcase', 'print', [
    $f('eyebrow', ['instructions' => 'e.g. “Recommended equipment”.']),
    $f('sectionHeading', ['label' => 'Heading', 'instructions' => '[brackets] = red, e.g. “Printers for [Flexible Packaging]”.']),
    $f('sectionLead', ['label' => 'Intro']),
    $f('productSource'),
    $f('selectedProducts'),
    $f('selectedCategories', ['label' => 'Categories', 'instructions' => 'For “Products from the categories”: the product categories to take products from.']),
    $f('productLimit'),
    $f('ctaLink', ['label' => 'View All Link', 'instructions' => 'e.g. “All flexible packaging printers”.']),
    $f('anchorId', ['instructions' => 'e.g. “equipment”, so a button can link to #equipment.']),
], '{sectionHeading}', 'Product cards: picked products, or products from categories (dynamic).');
$content = blockType('Content Block', 'contentBlock', 'image', [
    $f('eyebrow'),
    $f('headingText', ['label' => 'Heading', 'required' => true]),
    $f('richText', ['label' => 'Text']),
    $f('mediaImage', ['label' => 'Image']),
    $f('ctaLink', ['label' => 'Link', 'instructions' => 'Optional, e.g. “Talk to our team about bag making” → #contact.']),
    $f('anchorId'),
], '{headingText}', 'Image + heading + text. Blocks in a row alternate the image side (left, right, left …).');
$gallery = blockType('Photo Gallery', 'photoGallery', 'images', [
    $f('eyebrow'),
    $f('sectionHeading', ['label' => 'Heading', 'instructions' => '[brackets] = red.']),
    $f('galleryImages', ['label' => 'Photos', 'required' => true]),
    $f('anchorId'),
], '{sectionHeading}', 'A photo grid that opens in a lightbox.');
$contact = blockType('Contact Form', 'contactBlock', 'envelope', [
    $f('eyebrow', ['instructions' => 'e.g. “Flexible packaging”.']),
    $f('sectionHeading', ['label' => 'Heading', 'instructions' => 'e.g. “Contact With Our Team Now”.']),
    $f('sectionLead', ['label' => 'Text']),
    $f('anchorId', ['instructions' => 'e.g. “contact”, so a button can link to #contact.']),
], '{sectionHeading}', 'The maroon contact panel (phones, email, address from Site Settings) and the contact form.');
$other = blockType('Other Industries', 'otherIndustries', 'grip', [
    $f('eyebrow', ['instructions' => 'e.g. “Keep exploring”.']),
    $f('sectionHeading', ['label' => 'Heading', 'instructions' => 'e.g. “Other [Industries]”.']),
    $f('relatedIndustries'),
    $f('ctaLink', ['label' => 'View All Link']),
], '{sectionHeading}', 'Three industry cards: picked, else the next ones in the listing.');

// Content Block: heading optional (live rows often continue the text above) + a YouTube video in place of the image
$cb = $type('contentBlock');
$layout = $cb->getFieldLayout();
$changed = false;
foreach ($layout->getTabs() as $tab) {
    $els = [];
    foreach ($tab->getElements() as $el) {
        if ($el instanceof CustomField && $el->attribute() === 'headingText' && $el->required) { $el->required = false; $changed = true; }
        $els[] = $el;
        if ($el instanceof CustomField && $el->attribute() === 'mediaImage' && !$layout->getFieldByHandle('videoUrl')) {
            $els[] = $f('videoUrl', ['instructions' => 'Optional: a YouTube link shown instead of the image (click to play).']);
            $changed = true;
        }
    }
    $tab->setElements($els);
}
if ($changed) { $cb->setFieldLayout($layout); $entries->saveEntryType($cb) || fail('entry type contentBlock', $cb); say('  ~ contentBlock: heading optional, + Video URL'); }

say('Page Builder');
$band = $type('ctaBand');
if (!$band->icon) { $band->icon = 'bullhorn'; $entries->saveEntryType($band) || fail('entry type ctaBand', $band); say('  ~ ctaBand: icon'); }   // shared with the homepage; had none
$builder = matrixField('Page Builder', 'industryBuilder', [
    ['Sections', $header], ['Sections', $products], ['Sections', $content], ['Sections', $contact], ['Sections', $other], ['Sections', $type('ctaBand')],
    ['Media', $gallery], ['Media', $type('video')],
    ['Text', $type('richText')],
], ['createButtonLabel' => 'Add block', 'instructions' => 'The page, top to bottom. Add blocks and drag them into order.']);
addTab($industry, 'Page Builder', [$builder]);
$seo = array_map(fn($h) => $fields->getFieldByHandle($h), ['seoTitle', 'seoDescription', 'socialImage', 'seoNoIndex', 'seoCanonical']);
addTab($entries->getEntryTypeByHandle('industry'), 'SEO', $seo);

// ---- Industries listing page ----------------------------------------------------------------------------------------
say('Industries Page');
$page = entryType('Industries Page', 'industriesPage', ['Content' => [
    $f('eyebrow'),
    $f('sectionHeading', ['label' => 'Heading', 'instructions' => '[brackets] = red, e.g. “Industries [We Serve]”.']),
    $f('sectionLead', ['label' => 'Intro']),
    $f('ctaHeading', ['label' => 'Help Band Heading', 'instructions' => 'The maroon band under the cards. Its buttons use the phone and quote link from Site Settings.']),
    $f('ctaText', ['label' => 'Help Band Text']),
]], ['icon' => 'industry', 'titleLabel' => 'Page Title']);
$pageType = $entries->getEntryTypeByHandle('industriesPage');
if (!$pageType->getFieldLayout()->getFieldByHandle('caption')) {   // the text after "10 industries" in the header
    $layout = $pageType->getFieldLayout();
    $tab = $layout->getTabs()[0];
    $els = [];
    foreach ($tab->getElements() as $el) {
        $els[] = $el;
        if ($el instanceof CustomField && $el->attribute() === 'sectionLead') $els[] = $f('caption', ['label' => 'Count Line', 'instructions' => 'Shown after “10 industries”, e.g. “Labels, packaging and industrial printing”.']);
    }
    $tab->setElements($els);
    $pageType->setFieldLayout($layout);
    $entries->saveEntryType($pageType) || fail('entry type industriesPage', $pageType);
    say('  ~ industriesPage: + Count Line');
}
addTab($entries->getEntryTypeByHandle('industriesPage'), 'SEO', array_map(fn($h) => $fields->getFieldByHandle($h), ['seoTitle', 'seoDescription', 'socialImage', 'seoNoIndex']));
section('Industries Page', 'industriesPage', 'single', [$page], 'industries', 'industries/_index');

// ---- Homepage section E ---------------------------------------------------------------------------------------------
say('Homepage block: Industries Carousel (section E)');
$tile = blockType('Industry Tile', 'industryTile', 'image', [
    $f('ctaLink', ['label' => 'Link', 'required' => true, 'instructions' => 'An industry (pick an entry) or any URL.']),
    $f('headingText', ['label' => 'Name', 'instructions' => 'Empty = the linked industry\'s name.']),
    $f('mediaImage', ['label' => 'Photo', 'instructions' => '600 × 390 works best. Empty = the linked industry\'s card photo.']),
], '{headingText}');
$tiles = matrixField('Tiles', 'industryTiles', [$tile], ['createButtonLabel' => 'Add tile', 'minEntries' => 1,
    'instructions' => 'The carousel tiles, in order (the design has 6).']);
$carousel = entryType('Industries Carousel', 'industryCarousel', ['Content' => [
    $f('eyebrow'),
    $f('sectionHeading', ['required' => true]),
    $f('industryTiles'),
    $f('ctaLink', ['label' => 'View All Link', 'instructions' => 'e.g. “View all industries” → the Industries Page.']),
    $f('anchorId'),
]], ['icon' => 'industry', 'titleLabel' => 'Name in the list',
    'description' => 'Section E: a carousel of industry tiles (photo + name) with chips and arrows.']);
$home = $fields->getFieldByHandle('homepageSections');
$homeTypes = $home->getEntryTypes();
if (!in_array('industryCarousel', array_map(fn($t) => $t->handle, $homeTypes), true)) {
    $homeTypes[] = $carousel;
    $home->setEntryTypes($homeTypes);
    $fields->saveField($home) || fail('homepageSections', $home);
    say('  ~ homepageSections: + Industries Carousel');
}

saveProjectConfig();
