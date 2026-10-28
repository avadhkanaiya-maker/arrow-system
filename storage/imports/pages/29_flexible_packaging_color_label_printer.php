<?php
/**
 * Pages: /flexible-packaging/ and /color-label-printer/, from the live landing pages (read 2026-10-27; no design file).
 * Text word for word (live's typos too); images are live's, added to Images › pages/; links to products and industries
 * link to their entries here. General blocks only:
 *   /flexible-packaging/ (https://arrsys.com/flexible-packaging/)
 *     Page Header  "Flexible [Packaging]", live's header background as a short banner; Request a Quote
 *     Card Grid    the 3 feature boxes · "Print Beyond [Limits]" (Aqua 800M benefits, 4)
 *     Content      ArrowJet Aqua 800M specifications + Request Quote (→ the product, as live)
 *     Card Grid    "Take Control of Everything In Your Hands" (3) · Industries That We Serve (5, linked)
 *                  · Latest Printing Technology (3 printers, linked) · Media Types (3) · Application Possibilities (5)
 *     Image        live's Nestlé-compliant badge (after "Take Control")
 *   /color-label-printer/ (https://arrsys.com/color-label-printer/)
 *     Page Header  "Color Label [Printer]", live's header background as a short banner; Request a Quote
 *     Card Grid    the 3 feature boxes · the 3 ink / run / variable data points · "Cut Costs, Boost [Profits]" (4)
 *     Content      ArrowJet Aqua 330R specifications + Request Quote (→ the product, as live)
 *     Card Grid    Industries That We Serve (6, linked) · Options That You Can Explore (3 machines, Learn More)
 *     Content × 6  ArrowJet For Inhouse Labels and Converters (image + text, alternating, as live)
 *     CTA Band     "Looking To Upgrade Your [Workflow]?" → Contact Us (live's button goes to a Google listing)
 *   Newsletter   last on both, as every page (the newsletter band above the footer, as live)
 *   SEO          the live title, description and og:image
 * Then the menu items and link fields pointing at the live pages link here. Stops if a menu has unsaved builder
 * changes. Skips a page that exists. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/pages/29_flexible_packaging_color_label_printer.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\db\Query;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\fields\Link;
use verbb\navigation\elements\Node;

const MEDIA = 'https://arrsys.com/media/';

/** A live image in Images › pages/, added once (by file name) */
function img(string $file, string $alt = ''): int {
    $volume = Craft::$app->getVolumes()->getVolumeByHandle('images');
    $folder = Craft::$app->getAssets()->ensureFolderByFullPathAndVolume('pages', $volume, false);
    $name = preg_replace('/\.gif$/i', '.webp', $file);   // GIF uploads are off (config/general.php): its first frame as WebP
    if ($asset = Asset::find()->folderId($folder->id)->filename($name)->one()) return $asset->id;
    $bytes = @file_get_contents(MEDIA . rawurlencode($file), false, stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0 (Arrow Systems staging migration)\r\n", 'timeout' => 90]]))
        ?: exit("could not download $file\n");
    $tmp = Craft::$app->getPath()->getTempPath() . '/' . uniqid('page') . "-$name";
    if ($name === $file) file_put_contents($tmp, $bytes);
    else {
        $gd = imagecreatefromstring($bytes) ?: exit("could not read $file\n");
        imagepalettetotruecolor($gd);   // WebP needs true colour; GIFs are palette images
        imagewebp($gd, $tmp, 90) || exit("could not convert $file\n");
    }
    $asset = new Asset(['tempFilePath' => $tmp, 'filename' => $name, 'volumeId' => $volume->id, 'newFolderId' => $folder->id]);
    $asset->alt = $alt ?: null;
    $asset->setScenario(Asset::SCENARIO_CREATE);
    Craft::$app->getElements()->saveElement($asset) || fail("asset $file", $asset);
    return $asset->id;
}

/** A live URI as a link field value: the entry here when there is one, else the live URL */
function liveLink(string $uri, string $label): array {
    $e = Entry::find()->uri($uri)->one();
    return $e ? ['type' => 'entry', 'value' => $e->id, 'label' => $label] : ['type' => 'url', 'value' => "https://arrsys.com/$uri/", 'label' => $label];
}

function card(string $title, string $html = '', ?int $image = null, ?array $link = null, string $sub = ''): array {
    return ['type' => 'card', 'fields' => array_filter(['headingText' => $title, 'subheading' => $sub, 'richText' => $html, 'mediaImage' => $image ? [$image] : null, 'ctaLink' => $link])];
}

function grid(string $cols, array $cards, string $heading = '', string $lead = ''): array {
    $items = ['sortOrder' => [], 'entries' => []];
    foreach (array_values($cards) as $i => $c) { $items['sortOrder'][] = "c$i"; $items['entries']["c$i"] = $c; }
    return ['type' => 'cardGrid', 'fields' => array_filter(['sectionHeading' => $heading, 'sectionLead' => $lead, 'cardColumns' => $cols, 'cardItems' => $items])];
}

$p = fn(string ...$paras) => implode('', array_map(fn($t) => "<p>$t</p>", $paras));
$ul = fn(array $items) => '<ul>' . implode('', array_map(fn($t) => "<li>$t</li>", $items)) . '</ul>';

$features = grid('3', [
    card('100% Color Vibrancy and Quality', $p('Tailor every detail to fit your brand&#039;s unique vision')),
    card('Print on Various substrates', $p('Coated paper, PP, PET, PVC, BOPP, Mylar, or Tyvek')),
    card('Variable Printing', $p('Customize each piece in a print run with unique text, images, or codes')),
]);
$benefits = fn(string $heading, string $lead) => grid('4', [
    card('Print Quality', $p('high-quality prints with vibrant and accurate colors, meeting the aesthetic requirements'), img('Untitled-design-2024-09-10T183915.339.webp', 'Print Quality')),
    card('Health and Safety', $p('Nestle food compliant inks makes it a better option for Pharma, Food and Beverages'), img('Untitled-design-2024-09-10T184559.063.webp', 'Health & Safety')),
    card('Adhesion to Various Substrates', $p('Water-based inks offer versatile adhesion to various substrates, such as paper, films, and more.'), img('Untitled-design-2024-09-10T185103.622.webp', 'Adhesion to Various Substrates')),
    card('Environmental Friendly', $p('Water-based inks are considered more environmentally friendly than solvent-based inks'), img('Untitled-design-2024-09-10T185639.257.webp', 'Environmental Friendly')),
], $heading, $lead);
$specs = fn(string $name, string $speed, string $photo, string $product) => ['type' => 'contentBlock', 'fields' => [
    'eyebrow' => 'Specifications', 'headingText' => $name, 'mediaImage' => [img($photo, $name)],
    'richText' => $ul([$speed, 'CMYK', 'NIR Drying Tech', '1600 X 1600 dpi res']), 'ctaLink' => liveLink($product, 'Request Quote')]];
$industry = fn(string $title, string $file, string $alt, string $uri) => card($title, '', img($file, $alt), liveLink($uri, $title));

// color-label-printer: ArrowJet For Inhouse Labels and Converters, live order: [heading, text, image, alt, link]
$inhouse = [
    ['Faster Turnaround Time', $p('Deliver labels faster than ever with our on-demand printing technology, designed to handle even the most urgent requests. Streamline your workflow and reduce lead times without sacrificing the vibrant quality your clients expect.'), '0001-0770-2.gif', 'NIR Drying'],
    ['Custom Labels and Variable Data', $p('Stand out with fully customizable labels that offer variable data printing, allowing you to personalize content like names, barcodes, or unique designs on every label. Perfect for limited-edition products, region-specific packaging, or targeted marketing.'), 'Untitled-design-2024-09-11T142550.178.webp', 'Custom Labels and Variable Data'],
    ['Security Printing for Counterfeiting', $p('Safeguard your brand and products with advanced security printing features, including anti-counterfeiting measures that ensure your labels are genuine and tamper-resistant, protecting your customers and your reputation.'), 'Untitled-design-2024-09-11T142558.645.webp', 'Blister Foils'],
    ['Bag More Profitable Print Jobs', $p('Increase your revenue by taking on high-profit short-run jobs. With our technology, you can cater to diverse industries with no minimum order requirements, offering clients the flexibility they need while securing more business for yourself.'), 'Untitled-design-2024-09-11T142629.003.webp', 'Food And Beverages Labels'],
    ['Lower Setup Costs and Operations', $p('Increase your revenue by taking on high-profit short-run jobs. With our technology, you can cater to diverse industries with no minimum order requirements, offering clients the flexibility they need while securing more business for yourself.'), 'Screenshot-2024-09-09-150638-1024x576.png', 'Lower setup costs and operations'],
    ['Water based Inks vs UV?', $p('Choosing water-based pigment inks over UV inks offers a range of advantages, particularly in terms of sustainability, safety, and cost-efficiency.',
        'Water-based inks are environmentally friendly with low VOCs, making them compliant with stringent industry regulations like Nestlé standards for food and pharmaceutical packaging.',
        'They deliver vibrant, high-quality prints on a wide variety of substrates, especially absorbent materials, without the need for expensive UV curing equipment.',
        'Additionally, water-based inks are non-toxic and safer for both operators and consumers, ensuring better health standards. In contrast, UV inks come with higher setup costs, potential health risks, and compliance challenges for sensitive industries.',
        'Overall, water-based inks are a superior choice for companies seeking cost-effective, eco-friendly, and versatile label printing solutions.'), '0001-0770-4.gif', 'Label Printing', liveLink('contact-us', 'Learn More')],
];

$pages = [
    'flexible-packaging' => [
        'title' => 'Flexible Packaging',
        'seoTitle' => 'Flexible Packaging Solutions by Arrow Systems',
        'seoDescription' => 'Discover innovative Flexible Packaging from Arrow Systems. Enhance your product\'s shelf life and appeal with our advanced packaging solutions.',
        'social' => ['Untitled-design-14.webp', 'Nestle'],
        'cardTagline' => 'In-house flexible packaging printing on coated paper, PP, PET, PVC, BOPP, Mylar and Tyvek.',
        'blocks' => fn() => [
            ['type' => 'industryHeader', 'fields' => ['eyebrow' => 'Flexible Packaging Solutions', 'sectionHeading' => 'Flexible [Packaging]',
                'mediaImage' => [img('Labels-4.webp', 'Flexible packaging printed with ArrowJet')], 'headerStyle' => 'banner', 'bannerHeight' => 'strip', 'primaryCta' => liveLink('request-quote', 'Request a Quote')]],
            $features,
            $benefits('Print Beyond [Limits]', 'With Arrowjet Aqua 800M'),
            $specs('ArrowJet Aqua 800M', 'Speed: up to 150 meters/min', 'Untitled-design-11.webp', 'product/arrowjet-aqua-800m'),
            grid('3', [
                card('Stand out in the marketplace', $p('Empowers you to create custom packaging with unlimited design possibilities, from personalized labels to unique variations, all tailored to your brand.'), img('Untitled-design-13.webp')),
                card('Versatile Material Compatibility', $p('Print on various materials like coated paper, PP, PET, PVC, BOPP, Mylar, and Tyvek, ensuring it meets any industry&#039;s packaging needs.'), img('Untitled-design-13.webp')),
                card('Print Short Runs with No Minimum Orders', $p('Print short runs with no minimum orders, giving you the flexibility to meet any demand quickly and efficiently.'), img('Untitled-design-12.webp')),
            ], 'Take Control of Everything In Your [Hands]', 'In today’s fast-paced market, flexible packaging demand is rising. Arrow Systems, Inc. offers the ideal in-house solution to meet your needs with innovation and sustainability.'),
            ['type' => 'image', 'fields' => ['mediaImage' => [img('Untitled-design-14.webp', 'Nestle')]]],
            grid('3', [
                $industry('Retail Labels', 'Untitled-600-x-700-px.webp', 'Retail Labels', 'label-production-and-in-house-labeling'),
                $industry('Food And Beverages Labels', 'Untitled-600-x-700-px-1.webp', 'Food And Beverages Labels', 'digital-printing-for-beverage-labels'),
                $industry('Wines & Spirits', 'Untitled-600-x-700-px-4.webp', 'Wines & Spirits', 'digital-printing-for-beverage-labels'),
                $industry('Pharma & Nutraceuticals', 'Untitled-600-x-700-px-2.webp', 'Pharma & Nutraceuticals', 'pharmaceutical-labeling'),
                $industry('Industrial & Chemical Labels', 'Untitled-600-x-700-px-5.webp', 'Industrial & Chemical Labels', 'manufacturing-and-industrial-labels'),
            ], 'Industries That We [Serve]'),
            grid('3', [
                card('HYBRID PRO M', '', img('Untitled-design-21.webp', 'ArrowJet Aqua Hybrid M'), liveLink('product/arrowjet-aqua-330r-hybrid-pro-m', 'HYBRID PRO M')),
                card('ArrowJet Nova 250R', '', img('Untitled-design-23-1.webp', 'ArrowJet Nova 250R'), liveLink('product/arrowjet-nova250r-inline-digital-printer-and-laser-die-cutter-2', 'ArrowJet Nova 250R')),
                card('ArrowJet Aqua 800M', '', img('Untitled-design-24.webp', 'ArrowJet Aqua 800M'), liveLink('product/arrowjet-aqua-800m', 'ArrowJet Aqua 800M')),
            ], 'Latest Printing Technology For Flexible [Packaging]'),
            grid('3', [
                card('Poly Laminated Paper Roll', $p('Used mainly for flexible packaging of short term snacks &amp; various items. Can be used as pouch or FFS Machine poly is heat sealed.',
                    '<strong>Application<br>Convenience Food, Refill Packs, Melted products</strong>', '<strong>Properties<br>Protects from scratch, water resistant, Grease and Moisture resistant</strong>'), img('Untitled-design-19.webp', 'Poly Laminated Paper Roll')),
                card('Kraft Paper/ Alu', $p('Polyester laminated with heat seal coating on one side and water-based inkjet coating on other side',
                    '<strong>Application<br>Food Packaging</strong>', '<strong>Properties<br>Moisture resistant and Grease resistant</strong>'), img('Untitled-design-18.webp', 'Kraft Paper/ Alu')),
                card('CCPET PET/ MATT', $p('Pet film is a biaxially oriented polyester film which is chemically pre-treated on one side to give improved ink adhesion',
                    '<strong>Applications<br>Food/ Snacks</strong>', '<strong>Properties<br>Oder barrier, Good seal strength</strong>'), img('Untitled-design-20.webp', 'CCPET PET/ MATT')),
            ], 'Media Types For Flexible [Packaging]'),
            grid('3', [
                card('3 Side Seal', $p('Sealed on three sides, these pouches are ideal for smaller products like snacks or powders. Their flat design offers ample space for high-quality branding on both sides.'), img('Untitled-design-25.webp', '3 Side Seal')),
                card('Central Seal Pouch', $p('Featuring a back seal, this pouch is great for bulkier products like coffee or grains. The full front panel allows for standout branding and design.'), img('Untitled-design-26.webp', 'Central Seal Pouch')),
                card('Side Gusseted Pouch', $p('With expandable sides, these pouches hold larger products like coffee or pet food. They stand upright and provide extra space for branding on all sides.'), img('Untitled-design-27.webp', 'Side Gusseted Pouch')),
                card('Stand Up Pouch', $p('Featuring a bottom gusset, these pouches stand upright, perfect for snacks, coffee, or liquids, with plenty of space for bold branding.'), img('Untitled-design-28.webp', 'Stand Up Pouch')),
                card('5 Panel Pouches', $p('Offering five printable surfaces, these pouches are perfect for products requiring detailed labeling or branding, like meal kits or specialty foods.'), img('Untitled-design-29.webp', '5 Panel Pouches')),
            ], 'Application [Possibilities]'),
        ],
    ],
    'color-label-printer' => [
        'title' => 'Color Label Printer',
        'seoTitle' => 'Color Label Printer for Premium Prints | Arrow Systems',
        'seoDescription' => 'Discover high-speed, vibrant Color Label Printers by Arrow Systems, Inc.—perfect for custom labels, packaging, and industrial applications.',
        'social' => ['Untitled-600-x-700-px-1.webp', 'Food And Beverages Labels'],
        'cardTagline' => 'Water-based color label printing for short and medium runs, with variable data.',
        'blocks' => fn() => [
            ['type' => 'industryHeader', 'fields' => ['eyebrow' => 'Label Printing', 'sectionHeading' => 'Color Label [Printer]',
                'mediaImage' => [img('Labels-21.webp', 'Color labels printed with ArrowJet')], 'headerStyle' => 'banner', 'bannerHeight' => 'strip', 'primaryCta' => liveLink('request-quote', 'Request a Quote')]],
            $features,
            grid('3', [
                card('Water Based Pigment Ink', $p('Our CMYK water-based pigment inks are Nestlé-compliant, ensuring the highest quality standards')),
                card('Short-Run Printing', $p('Our CMYK water-based pigment inks are Nestlé-compliant, ensuring the highest quality standards')),
                card('Variable data printing', $p('Personalize every label effortlessly with dynamic, custom content and unique product identification.')),
            ]),
            $benefits('Cut Costs, Boost [Profits]', 'Whether you’re looking to produce short-run or medium-run jobs, ArrowJet delivers unmatched vibrancy and quality to make your products stand out on any shelf.'),
            $specs('ArrowJet Aqua 330R', 'Speed: up to 150/min', 'Untitled-600-x-700-px-6.webp', 'product/arrowjet-aqua-330r-lite-2'),
            grid('3', [
                $industry('Food And Beverages Labels', 'Untitled-600-x-700-px-1.webp', 'Food And Beverages Labels', 'digital-printing-for-beverage-labels'),
                $industry('Industrial & Chemical Labels', 'Untitled-600-x-700-px-5.webp', 'Industrial & Chemical Labels', 'manufacturing-and-industrial-labels'),
                $industry('Aviation', 'Untitled-600-x-700-px-3.webp', 'Aviation', 'aircraft-interior-placards'),
                $industry('Retail Labels', 'Untitled-600-x-700-px.webp', 'Retail Labels', 'label-production-and-in-house-labeling'),
                $industry('Pharma & Nutraceuticals', 'Untitled-600-x-700-px-2.webp', 'Pharma & Nutraceuticals', 'pharmaceutical-labeling'),
                $industry('Wines & Spirits', 'Untitled-600-x-700-px-4.webp', 'Wines & Spirits', 'digital-printing-for-beverage-labels'),
            ], 'Industries That We [Serve]'),
            grid('3', [
                card('ArrowJet Hybrid Pro M', $p('<strong>Colors</strong>: Priming + CMYK + Varnish', '<strong>Print Speed:</strong> 150 ft./ min', '<strong>Max Printing Width</strong>: 12.75 inches', '<strong>Resolution:</strong> 1600 x 1600 dpi', '<strong>Ink</strong>: Waterbased Pigment Inks'),
                    img('Untitled-design-20.png', 'Hybrid Pro M'), liveLink('product/arrowjet-aqua-330r-hybrid-pro-m', 'Learn More'), 'Elevate Your Prints, Redefine Possibilities.'),
                card('Arrow EZCUT 350R', $p('<strong>Max Roll Diameter: </strong>450mm', '<strong>Cutting Media Width: </strong>100-350mm', '<strong>Max. Die cutting speed: </strong>9m/min'),
                    img('ArrowEZCut-350R-1024x846.webp', 'ArrowEZCut 350R'), liveLink('product/arrowezcut-350r', 'Learn More'), 'Multiple Blades Digital Label Cutter'),
                card('ArrowJet Aqua 330R', $p('<strong>Colors</strong>: CMYK', '<strong>Print Speed:</strong> 90ft or 150ft / min', '<strong>Max Printing Width</strong>: 12.75 inches', '<strong>Resolution:</strong> 1600 x 1600 dpi', '<strong>Ink</strong>: Waterbased Pigment Inks'),
                    img('1-9.png', 'AJ 330R'), liveLink('product/arrowjet-aqua-330r-lite-2', 'Learn More'), 'High-Resolution, Digital Inkjet Printer with higher speed'),
            ], 'Options That You Can [Explore]'),
            ...array_map(fn($i, $b) => ['type' => 'contentBlock', 'fields' => array_filter(['eyebrow' => $i === 0 ? 'ArrowJet For Inhouse Labels and Converters' : null,
                'headingText' => $b[0], 'richText' => $b[1], 'mediaImage' => [img($b[2], $b[3])], 'ctaLink' => $b[4] ?? null])], array_keys($inhouse), $inhouse),
            ['type' => 'ctaBand', 'fields' => ['sectionHeading' => 'Looking To Upgrade Your [Workflow]?', 'sectionLead' => 'Contact us today to see how we can help you take control of your label production!', 'ctaLink' => liveLink('contact-us', 'Contact us Now')]],
        ],
    ],
];

$pb = Craft::$app->getFields()->getFieldByHandle('pageBuilder')->id;
foreach ($pages as $slug => $d) {
    $page = Entry::find()->section('pages')->slug($slug)->status(null)->one();
    if (!$page) {
        $blocks = ['sortOrder' => [], 'entries' => []];
        // Newsletter last, as every page
        foreach ([...$d['blocks'](), ['type' => 'newsletter']] as $i => $b) { $blocks['sortOrder'][] = "b$i"; $blocks['entries']["b$i"] = $b; }
        $page = new Entry(['sectionId' => Craft::$app->getEntries()->getSectionByHandle('pages')->id,
            'typeId' => Craft::$app->getEntries()->getEntryTypeByHandle('page')->id, 'title' => $d['title'], 'slug' => $slug]);
        $page->setFieldValues(['seoTitle' => $d['seoTitle'], 'seoDescription' => $d['seoDescription'], 'socialImage' => [img(...$d['social'])],
            'cardTagline' => $d['cardTagline'], 'pageBuilder' => $blocks]);
        Craft::$app->getElements()->saveElement($page) || fail("page $slug", $page);
        $page->uri === $slug || exit("the page got /{$page->uri}/: another entry uses /$slug/\n");
        $want = count($blocks['sortOrder']);
        ($saved = (int)Entry::find()->ownerId($page->id)->fieldId($pb)->status(null)->count()) === $want || exit("$slug: $saved of $want blocks saved\n");
        say("  + page /{$page->uri}/ ($want blocks)");
    }
    $pages[$slug]['page'] = $page;
}

// menus: stop on unsaved builder changes, then items pointing at the live pages → these pages
$liveUrls = array_map(fn($slug) => "https://arrsys.com/$slug/", array_combine(array_keys($pages), array_keys($pages)));
$nodes = array_filter(Node::find()->status(null)->all(), fn($n) => in_array(rtrim((string)$n->url, '/') . '/', $liveUrls, true));
foreach (array_unique(array_map(fn($n) => $n->menuId, $nodes)) as $menuId) {
    foreach ((new Query())->from('{{%navigation_build_sessions}}')->where(['menuId' => $menuId])->all() as $s) {
        !array_filter([$s['structureMoves'], $s['addedNodeIds'], $s['stagedDeletes'], $s['nodeDraftMap'], $s['menuContentDraft']])
            || exit("menu $menuId has unsaved builder changes: Save or Discard them, then run again\n");
    }
}
foreach ($nodes as $n) {
    $page = $pages[array_search(rtrim((string)$n->url, '/') . '/', $liveUrls, true)]['page'];
    $n->type = Entry::class;
    $n->elementId = $page->id;
    $n->url = null;
    Craft::$app->getElements()->saveElement($n) || fail("menu node {$n->id}", $n);
    say("  ~ menu {$n->menu->handle} “{$n->title}” → /{$page->uri}/");
}

// link fields (block buttons, Site Settings) pointing at the live pages → these pages, label kept; rich text links stay
foreach ($liveUrls as $slug => $liveUrl) {
    $page = $pages[$slug]['page'];
    $ids = (new Query())->select('es.elementId')->from(['es' => '{{%elements_sites}}'])
        ->innerJoin(['e' => '{{%elements}}'], '[[e.id]] = [[es.elementId]]')
        ->where(['e.revisionId' => null, 'e.draftId' => null, 'e.dateDeleted' => null])
        ->andWhere(['like', 'es.content', "arrsys.com/$slug"])->column();
    foreach ($ids as $id) {
        $el = Craft::$app->getElements()->getElementById($id, null, null, ['status' => null]);
        if (!$el || !$el->getFieldLayout()) continue;
        $changed = [];
        foreach ($el->getFieldLayout()->getCustomFields() as $f) {
            if (!$f instanceof Link) continue;
            $v = $el->getFieldValue($f->handle);
            if (!$v || rtrim((string)$v->url, '/') . '/' !== $liveUrl) continue;
            $el->setFieldValue($f->handle, ['type' => 'entry', 'value' => $page->id, 'label' => $v->label, 'target' => $v->target]);
            $changed[] = $f->handle;
        }
        if (!$changed) continue;
        Craft::$app->getElements()->saveElement($el) || fail("element $id", $el);
        say('  ~ ' . ($el instanceof Entry ? "{$el->type->handle} $id" : $el::displayName() . ' ' . ($el->title ?? $el->name ?? $id)) . ': ' . implode(', ', $changed) . " → /{$page->uri}/");
    }
}

// self-check: both pages load with their last section
foreach (['flexible-packaging' => '5 Panel Pouches', 'color-label-printer' => 'Looking To Upgrade Your'] as $slug => $text) {
    $html = @file_get_contents("http://localhost:8081/$slug/") ?: exit("/$slug/ does not load\n");
    str_contains(html_entity_decode($html), $text) || exit("/$slug/: “{$text}” not on the page\n");
}
say('  = /flexible-packaging/ and /color-label-printer/ load with their content');
