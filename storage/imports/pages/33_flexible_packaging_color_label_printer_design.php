<?php
/**
 * Pages: /flexible-packaging/ and /color-label-printer/ rebuilt on the "standard information page" design
 * (demo.theglobalwebdev.com/Arrowsystem/flexible-packaging/, read 2026-10-28), with the sections the live pages have.
 * General blocks only (32_information_page_blocks.php):
 *   /flexible-packaging/ (the design, its text: live's typos fixed there)
 *     Page Header        "Flexible [Packaging]", the 3 highlights, the design's pouches (cut-out)
 *     Product Spotlight  "Print Beyond [Limits]": 4 points | ArrowJet Aqua 800M | 4 specs, Request Quote · View Printer
 *     Content Block      "Take Control of Everything [In Your Hands]": 3 points, the Nestlé-compliant pouch
 *     Card Grid          Image tiles "Industries That We [Serve]" (5) · View all industries
 *     Recommended Products "Latest Printing Technology for [Flexible Packaging]" (Hybrid Pro M, Nova 250R, Aqua 800M)
 *     Card Grid          "Media Types for [Flexible Packaging]" (3, with Application / Properties)
 *     Card Grid          Image cards "Application [Possibilities]" (5 pouch formats)
 *     CTA Band           "Ready to print flexible packaging in-house?": Talk to a Specialist · Request a Quote
 *   /color-label-printer/ (no design: live's sections and text, on the same parts)
 *     Page Header        "Color Label [Printer]", live's 3 hero points, the design's Aqua 330R cut-out
 *     Card Grid          the 3 ink / run / variable data boxes
 *     Product Spotlight  "Cut Costs, Boost [Profits]" + live's intro: 4 points | ArrowJet Aqua 330R | 4 specs
 *     Card Grid          Image tiles "Industries That We [Serve]" (6)
 *     Recommended Products "Options That You Can [Explore]" (Hybrid Pro M, EZCut 350R, Aqua 330R) · All digital label printers
 *     Content Block × 6  ArrowJet For Inhouse Labels and Converters … Water based Inks vs UV? (live's images)
 *     CTA Band           "Looking To Upgrade Your [Workflow]?" → Contact Us
 *   Newsletter last on both. Custom CSS (the page's tab): the design's page layer, tighter spacing and its black accents.
 * Replaces the pages' blocks (the old ones stay in their revisions); SEO fields are kept. Skips a page that is on the
 * design already (it has a Product Spotlight); --update rebuilds it. Stops under an open draft. Back up first.
 * Run from the project root, after 29_flexible_packaging_color_label_printer.php and 32_information_page_blocks.php:
 *   /opt/lampp/bin/php storage/imports/pages/33_flexible_packaging_color_label_printer_design.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';
require __DIR__ . '/_helpers.php';

use craft\db\Query;
use craft\elements\Entry;

const DESIGN = 'https://demo.theglobalwebdev.com/Arrowsystem/assets/';
const LIVE = 'https://arrsys.com/media/';

Craft::$app->getEntries()->getEntryTypeByHandle('productSpotlight') ?? exit("no Product Spotlight: run 32_information_page_blocks.php first\n");

$p = fn(string ...$paras) => implode('', array_map(fn($t) => '<p>' . htmlspecialchars($t) . '</p>', $paras));
$rows = fn(array $items) => array_map(fn($r) => ['icon' => $r[0], 'title' => $r[1], 'text' => $r[2] ?? ''], $items);
$product = fn(string $slug) => Entry::find()->section('products')->slug($slug)->status(null)->ids()[0] ?? exit("no product $slug\n");
$link = fn(string $uri, string $label) => designLink("https://arrsys.com/$uri/", $label);
$tiles = fn(array $items) => array_map(fn($t) => ['card', ['headingText' => $t[0], 'mediaImage' => [pageImage(DESIGN . "industries/web/$t[1]", $t[2])],
    'ctaLink' => $link($t[3], $t[0])]], $items);
$spotlight = fn(array $f) => ['productSpotlight', $f + ['sectionBackground' => 'surface']];
$points = [['check', 'Print Quality', 'High-quality prints with vibrant and accurate colors, meeting the aesthetic requirements.'],
    ['shield', 'Health and Safety', 'Nestle food compliant inks makes it a better option for Pharma, Food and Beverages.'],
    ['layers', 'Adhesion to Various Substrates', 'Water-based inks offer versatile adhesion to various substrates, such as paper, films, and more.'],
    ['leaf', 'Environmental Friendly', 'Water-based inks are considered more environmentally friendly than solvent-based inks.']];
$highlights = [['sparkles', '100% Color Vibrancy and Quality', 'Tailor every detail to fit your brand’s unique vision'],
    ['layers', 'Print on Various Substrates', 'Coated paper, PP, PET, PVC, BOPP, Mylar, or Tyvek'],
    ['refresh', 'Variable Printing', 'Customize each piece in a print run with unique text, images, or codes']];
$specs = fn(string $speed) => $rows([['gauge', $speed], ['droplet', 'CMYK'], ['thermometer', 'NIR Drying Tech'], ['scan', '1600 × 1600 dpi resolution']]);
$industries = ['Retail Labels' => ['industry-retail-labels.webp', 'Household cleaning product bottles with printed labels on a store shelf', 'label-production-and-in-house-labeling'],
    'Food And Beverages Labels' => ['industry-food-beverage.webp', 'Food and beverage products with printed labels', 'digital-printing-for-beverage-labels'],
    'Wines & Spirits' => ['industry-wines-spirits.webp', 'Wine and spirit bottles with printed labels', 'digital-printing-for-beverage-labels'],
    'Pharma & Nutraceuticals' => ['industry-pharma-nutraceuticals.webp', 'Pharmaceutical and supplement packaging with printed labels', 'pharmaceutical-labeling'],
    'Industrial & Chemical Labels' => ['industry-industrial-chemical.webp', 'Industrial and chemical containers with warning labels', 'manufacturing-and-industrial-labels'],
    'Aviation' => ['industry-aviation.webp', 'Aircraft interior with printed placards', 'aircraft-interior-placards']];
$industryTiles = fn(string ...$names) => $tiles(array_map(fn($n) => [$n, ...$industries[$n]], $names));
$tilesBlock = fn(string $cols, string $bg, array $cards) => ['cardGrid', ['eyebrow' => 'Industries', 'sectionHeading' => 'Industries That We [Serve]',
    'ctaLink' => $link('industries', 'View all industries'), 'cardColumns' => $cols, 'cardStyle' => 'tile', 'sectionBackground' => $bg, 'cardItems' => nested($cards, 'c')]];
$equipment = fn(string $heading, array $slugs, array $more) => ['productShowcase', ['eyebrow' => 'Equipment', 'sectionHeading' => $heading,
    'productSource' => 'picked', 'selectedProducts' => array_map($product, $slugs), 'cardColumns' => '3', 'ctaLink' => $more]];
$newsletter = ['newsletter', []];

// The design's page layer (flexible-packaging.css spacing + flexible-packaging-black-accents.css): the logo's black next to red
$css = <<<'CSS'
/* The Flexible Packaging design's page layer: the logo's black (always next to red) in section labels, small labels,
   icons and arrow badges */
main .section-head { margin-bottom: clamp(20px, 2.4vw, 28px); }
main .eyebrow { color: #141414; }
main .eyebrow::before { width: 28px; background: linear-gradient(90deg, #141414 0 70%, var(--c-red) 70% 100%); }
main .section-head .text-link { color: #141414; }
main .section-head .text-link .icon { color: var(--c-red); }
.highlights .ico { background: #141414; color: var(--c-white); }
.ctrl-points .ico { background: #EFEEED; color: #141414; }
.spotlight__label, .g-card__facts dt { color: #141414; }
.tile__title .icon { background: #141414; }
.img-card__title::before { width: 28px; background: linear-gradient(90deg, #141414 0 70%, var(--c-red) 70% 100%); }
CSS;

// color-label-printer: live's rows (29_…; its GIFs as WebP there): [heading, text, image file, alt, link]
$inhouse = fn() => [
    ['Faster Turnaround Time', 'Deliver labels faster than ever with our on-demand printing technology, designed to handle even the most urgent requests. Streamline your workflow and reduce lead times without sacrificing the vibrant quality your clients expect.', '0001-0770-2.webp', 'NIR Drying'],
    ['Custom Labels and Variable Data', 'Stand out with fully customizable labels that offer variable data printing, allowing you to personalize content like names, barcodes, or unique designs on every label. Perfect for limited-edition products, region-specific packaging, or targeted marketing.', 'Untitled-design-2024-09-11T142550.178.webp', 'Custom Labels and Variable Data'],
    ['Security Printing for Counterfeiting', 'Safeguard your brand and products with advanced security printing features, including anti-counterfeiting measures that ensure your labels are genuine and tamper-resistant, protecting your customers and your reputation.', 'Untitled-design-2024-09-11T142558.645.webp', 'Blister Foils'],
    ['Bag More Profitable Print Jobs', 'Increase your revenue by taking on high-profit short-run jobs. With our technology, you can cater to diverse industries with no minimum order requirements, offering clients the flexibility they need while securing more business for yourself.', 'Untitled-design-2024-09-11T142629.003.webp', 'Food And Beverages Labels'],
    ['Lower Setup Costs and Operations', 'Increase your revenue by taking on high-profit short-run jobs. With our technology, you can cater to diverse industries with no minimum order requirements, offering clients the flexibility they need while securing more business for yourself.', 'Screenshot-2024-09-09-150638-1024x576.png', 'Lower setup costs and operations'],
    ['Water based Inks vs UV?', ['Choosing water-based pigment inks over UV inks offers a range of advantages, particularly in terms of sustainability, safety, and cost-efficiency.',
        'Water-based inks are environmentally friendly with low VOCs, making them compliant with stringent industry regulations like Nestlé standards for food and pharmaceutical packaging.',
        'They deliver vibrant, high-quality prints on a wide variety of substrates, especially absorbent materials, without the need for expensive UV curing equipment.',
        'Additionally, water-based inks are non-toxic and safer for both operators and consumers, ensuring better health standards. In contrast, UV inks come with higher setup costs, potential health risks, and compliance challenges for sensitive industries.',
        'Overall, water-based inks are a superior choice for companies seeking cost-effective, eco-friendly, and versatile label printing solutions.'], '0001-0770-4.webp', 'Label Printing', $link('contact-us', 'Learn More')],
];

$pages = [
    'flexible-packaging' => fn() => [
        'css' => $css . "\nmain .ind-equipment { background: var(--c-white); }   /* the printers on white, as the design */",
        'blocks' => [
            ['industryHeader', ['eyebrow' => 'Solutions', 'sectionHeading' => 'Flexible [Packaging]', 'highlightItems' => $rows($highlights),
                'headerStyle' => 'cutout', 'mediaImage' => [pageImage(DESIGN . 'categories/web/fp-hero-pouches-trim.webp', 'Printed flexible packaging pouches')]]],
            $spotlight(['eyebrow' => 'With ArrowJet Aqua 800M', 'sectionHeading' => 'Print Beyond [Limits]', 'highlightItems' => $rows($points),
                'selectedProducts' => [$product('arrowjet-aqua-800m')],
                'mediaImage' => [pageImage(DESIGN . 'categories/web/fp-arrowjet-aqua-800m-tight.webp', 'ArrowJet Aqua 800M wide-web inkjet printer')],
                'specChips' => $specs('Speed: up to 150 meters/min'),
                'primaryCta' => $link('request-quote', 'Request Quote'), 'secondaryCta' => $link('product/arrowjet-aqua-800m', 'View Printer')]),
            ['contentBlock', ['eyebrow' => 'In-house production', 'headingText' => 'Take Control of Everything [In Your Hands]',
                'richText' => $p('In today’s fast-paced market, flexible packaging demand is rising. Arrow Systems, Inc. offers the ideal in-house solution to meet your needs with innovation and sustainability.'),
                'highlightItems' => $rows([
                    ['sparkles', 'Stand out in the marketplace', 'Empowers you to create custom packaging with unlimited design possibilities, from personalized labels to unique variations, all tailored to your brand.'],
                    ['layers', 'Versatile Material Compatibility', 'Print on various materials like coated paper, PP, PET, PVC, BOPP, Mylar, and Tyvek, ensuring it meets any industry’s packaging needs.'],
                    ['package', 'Print Short Runs with No Minimum Orders', 'Print short runs with no minimum orders, giving you the flexibility to meet any demand quickly and efficiently.']]),
                'mediaImage' => [pageImage(DESIGN . 'categories/web/fp-nestle-compliant-pouch.webp', 'Printed cookie pouch, marked Nestle compliant and oxygen protection')]]],
            $tilesBlock('5', 'surface', $industryTiles('Retail Labels', 'Food And Beverages Labels', 'Wines & Spirits', 'Pharma & Nutraceuticals', 'Industrial & Chemical Labels')),
            $equipment('Latest Printing Technology for [Flexible Packaging]', ['arrowjet-aqua-330r-hybrid-pro-m', 'arrowjet-nova250r-inline-digital-printer-and-laser-die-cutter-2', 'arrowjet-aqua-800m'],
                $link('all-products/digital-flexible-packaging-printers', 'All flexible packaging printers')),
            ['cardGrid', ['eyebrow' => 'Materials', 'sectionHeading' => 'Media Types for [Flexible Packaging]', 'cardColumns' => '3', 'sectionBackground' => 'surface',
                'cardItems' => nested(array_map(fn($m) => ['card', ['headingText' => $m[0], 'richText' => $p($m[1]),
                    'mediaImage' => [pageImage(DESIGN . "categories/web/$m[2]", $m[3])], 'specItems' => [['term' => 'Application', 'description' => $m[4]], ['term' => 'Properties', 'description' => $m[5]]]]], [
                    ['Poly Laminated Paper Roll', 'Used mainly for flexible packaging of short term snacks & various items. Can be used as pouch or FFS Machine poly is heat sealed.',
                        'fp-media-poly-laminated-paper.webp', 'Printed flexible packaging pouches on poly laminated paper', 'Convenience Food, Refill Packs, Melted products', 'Protects from scratch, water resistant, Grease and Moisture resistant'],
                    ['Kraft Paper/ Alu', 'Polyester laminated with heat seal coating on one side and water-based inkjet coating on other side',
                        'fp-media-kraft-paper-alu.webp', 'Kraft paper bags and envelopes', 'Food Packaging', 'Moisture resistant and Grease resistant'],
                    ['CCPET PET/ MATT', 'Pet film is a biaxially oriented polyester film which is chemically pre-treated on one side to give improved ink adhesion',
                        'fp-media-ccpet-pet-matt.webp', 'Printed popsicle and snack wrappers on PET film', 'Food/ Snacks', 'Odor barrier, Good seal strength'],
                ]), 'c')]],
            ['cardGrid', ['eyebrow' => 'Pouch formats', 'sectionHeading' => 'Application [Possibilities]', 'cardColumns' => '5', 'cardStyle' => 'plain',
                'cardItems' => nested(array_map(fn($c) => ['card', ['headingText' => $c[0], 'richText' => $p($c[1]), 'mediaImage' => [pageImage(DESIGN . "categories/web/$c[2]", $c[3])]]], [
                    ['3 Side Seal', 'Sealed on three sides, these pouches are ideal for smaller products like snacks or powders. Their flat design offers ample space for high-quality branding on both sides.', 'fp-pouch-3-side-seal.webp', 'Three side seal tea pouch'],
                    ['Central Seal Pouch', 'Featuring a back seal, this pouch is great for bulkier products like coffee or grains. The full front panel allows for standout branding and design.', 'fp-pouch-central-seal.webp', 'Central seal chip bag'],
                    ['Side Gusseted Pouch', 'With expandable sides, these pouches hold larger products like coffee or pet food. They stand upright and provide extra space for branding on all sides.', 'fp-pouch-side-gusseted.webp', 'Side gusseted coffee and food pouches'],
                    ['Stand Up Pouch', 'Featuring a bottom gusset, these pouches stand upright, perfect for snacks, coffee, or liquids, with plenty of space for bold branding.', 'fp-pouch-stand-up.webp', 'Stand up pet food pouch'],
                    ['5 Panel Pouches', 'Offering five printable surfaces, these pouches are perfect for products requiring detailed labeling or branding, like meal kits or specialty foods.', 'fp-pouch-5-panel.webp', 'Five panel coffee pouches with a coffee cup'],
                ]), 'c')]],
            ['ctaBand', ['sectionHeading' => 'Ready to print flexible packaging in-house?', 'sectionLead' => 'Tell us your pouch formats, materials and run lengths.',
                'ctaLink' => designLink('tel:+18886972749', 'Talk to a Specialist'), 'secondaryCta' => $link('request-quote', 'Request a Quote')]],
            $newsletter,
        ],
    ],
    'color-label-printer' => fn() => [
        'css' => $css,
        'blocks' => [
            ['industryHeader', ['eyebrow' => 'Label Printing', 'sectionHeading' => 'Color Label [Printer]', 'highlightItems' => $rows($highlights),
                'headerStyle' => 'cutout', 'mediaImage' => [pageImage(DESIGN . 'industries/web/arrowjet-aqua-330r-cutout.webp', 'ArrowJet Aqua 330R color label printer')]]],
            ['cardGrid', ['cardColumns' => '3', 'cardItems' => nested([
                ['card', ['headingText' => 'Water Based Pigment Ink', 'richText' => $p('Our CMYK water-based pigment inks are Nestlé-compliant, ensuring the highest quality standards')]],
                ['card', ['headingText' => 'Short-Run Printing', 'richText' => $p('Our CMYK water-based pigment inks are Nestlé-compliant, ensuring the highest quality standards')]],
                ['card', ['headingText' => 'Variable data printing', 'richText' => $p('Personalize every label effortlessly with dynamic, custom content and unique product identification.')]],
            ], 'c')]],
            $spotlight(['eyebrow' => 'Color Label Printer', 'sectionHeading' => 'Cut Costs, Boost [Profits]',
                'sectionLead' => 'Whether you’re looking to produce short-run or medium-run jobs, ArrowJet delivers unmatched vibrancy and quality to make your products stand out on any shelf.',
                'highlightItems' => $rows($points), 'selectedProducts' => [$product('arrowjet-aqua-330r-lite-2')], 'specChips' => $specs('Speed: up to 150/min'),
                'primaryCta' => $link('request-quote', 'Request Quote'), 'secondaryCta' => $link('product/arrowjet-aqua-330r-lite-2', 'View Printer')]),
            $tilesBlock('6', 'white', $industryTiles('Food And Beverages Labels', 'Industrial & Chemical Labels', 'Aviation', 'Retail Labels', 'Pharma & Nutraceuticals', 'Wines & Spirits')),
            $equipment('Options That You Can [Explore]', ['arrowjet-aqua-330r-hybrid-pro-m', 'arrowezcut-350r', 'arrowjet-aqua-330r-lite-2'],
                $link('all-products/digital-label-printers/arrow-digital-label-printers', 'All digital label printers')),
            ...array_map(fn($i, $b) => ['contentBlock', ['eyebrow' => $i === 0 ? 'ArrowJet For Inhouse Labels and Converters' : null, 'headingText' => $b[0],
                'richText' => $p(...(array) $b[1]), 'mediaImage' => [pageImage(LIVE . $b[2], $b[3])], 'ctaLink' => $b[4] ?? null]], array_keys($inhouse()), $inhouse()),
            ['ctaBand', ['sectionHeading' => 'Looking To Upgrade Your [Workflow]?', 'sectionLead' => 'Contact us today to see how we can help you take control of your label production!',
                'ctaLink' => $link('contact-us', 'Contact us Now')]],
            $newsletter,
        ],
    ],
];

$pb = Craft::$app->getFields()->getFieldByHandle('pageBuilder')->id;
foreach ($pages as $slug => $build) {
    $page = Entry::find()->section('pages')->slug($slug)->status(null)->one() ?? exit("no /$slug/ page: run 29_flexible_packaging_color_label_printer.php first\n");
    $onDesign = Entry::find()->ownerId($page->id)->fieldId($pb)->type('productSpotlight')->status(null)->exists();
    if ($onDesign && !in_array('--update', $argv, true)) { say("  = /$slug/ is on the design already (--update rebuilds it)"); continue; }
    (new Query())->from('{{%drafts}}')->where(['canonicalId' => $page->id])->exists() && exit("/$slug/ has an open draft: apply or delete it, then run again\n");

    $d = $build();
    $page->setFieldValues(['pageBuilder' => nested($d['blocks'], 'b'), 'customCss' => $d['css']]);
    Craft::$app->getElements()->saveElement($page) || fail("page $slug", $page);

    // check what was saved: the blocks, and the nested cards
    $saved = Entry::find()->ownerId($page->id)->fieldId($pb)->status(null)->all();
    count($saved) === count($d['blocks']) || exit("$slug: " . count($saved) . ' of ' . count($d['blocks']) . " blocks saved\n");
    $want = array_sum(array_map(fn($b) => count($b[1]['cardItems']['sortOrder'] ?? []), $d['blocks']));
    $got = array_sum(array_map(fn($b) => $b->type->handle === 'cardGrid' ? $b->getFieldValue('cardItems')->status(null)->count() : 0, $saved));
    $got === $want || exit("$slug: $got of $want cards saved\n");
    say("  ~ /$slug/: " . implode(', ', array_map(fn($b) => $b->type->name, $saved)) . " ($want cards)");
}

// self-check: both pages load with the new parts
foreach (['flexible-packaging' => ['spotlight__grid', 'tile__link', 'img-card', 'g-card__facts', 'ctrl-points', 'highlights'],
          'color-label-printer' => ['spotlight__grid', 'tile__link', 'highlights', 'Looking To Upgrade Your']] as $slug => $needles) {
    $html = @file_get_contents("http://localhost:8081/$slug/") ?: exit("/$slug/ does not load\n");
    foreach ($needles as $n) str_contains($html, $n) || exit("/$slug/: no “{$n}” on the page\n");
}
say('  = /flexible-packaging/ and /color-label-printer/ load with the design’s parts');
