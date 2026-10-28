<?php
/**
 * Pages: the /all-products/ tree, as nested Pages (the Pages structure: parent page → /all-products/<child>/…), from the
 * live pages (read 2026-10-23). General blocks only, as /arrowjet-uv-label-printers/: Page Header + Recommended
 * Products (picked, live order; the product cards show each product's own Card Title, tagline and Key Specs).
 * Built so far (PAGES below; add the other category pages there):
 *   /all-products/                                      "All Products": printers (9) + finishers (10)
 *   /all-products/label-finishers/                      "Label Finishers" (10)
 *   /all-products/label-finishers/arrow-label-finishers/ "Digital Label Finishing Equipments" (10)
 *   /all-products/printers/                              "Printer" (9: live's ArrowJet VDP left out, its product address now
 *                                                        redirects to the Limitronic printers page and it isn't on staging)
 *   /all-products/printers/gerber-printers/              "Printers by Gerber" (1: Gerber EDGE FX, a wide card)
 *   /all-products/printers/trojan-printers/              "Printers by Trojan" (4: T2-L, T2-C, T2, T4)
 *   /all-products/printers/quicklabel-printers/          "Printers by QuickLabel" (1: QL-300, a wide card)
 *     Live's two pages are empty (only the H1, since at least April 2024): filled with the brand's printers on staging.
 *     Card texts from live's "Digital Label Printers by Trojan" page; T4 and QL-300 have no live listing: their short
 *     name and subtitle only.
 *   /all-products/plotters/                              "Gerber Plotters" (3) + live's banner (Gerber-Envision-375-Plotter.jpg)
 *   /all-products/digital-label-printers/                switched off, as live (301 → its child, config/redirects.php)
 *   …/digital-label-printers/arrow-digital-label-printers/ "ArrowJet Digital Label Printers" (12), as the design
 *     (Arrowsystem/digital-label-printers, client-revised 2026-10-16): Page Header with Subtitle and the product cut-out
 *     (Header Style "Cut-out image beside text", 27_header_subtitle_cutout.php), the lineup, then Card Grids (about the
 *     range, who it's for, other categories) and a CTA Band; SEO as live. Left out: the design's search box, pagination
 *     (12 fit one page), "12 printers" line, section eyebrows and card kickers (no such block fields).
 *   …/digital-label-printers/trojan-digital-label-printers/ "Digital Label Printers by Trojan" (3: T2-L, T2-C, T2, as live;
 *     no banner on live). Trojan T2 had been switched off on staging (Draft 1 applied in the CP, 2026-10-16): switched on
 *     here (SWITCH_ON), since live shows it on this page and at /product/trojan-t2/
 *   /all-products/corrugate-and-end-of-line-printing/    "Corrugate and End of Line Printing" (5: the Limitronic printers,
 *                                                        as live); first made as a switched-off placeholder for its child
 *   …/corrugate-and-end-of-line-printing/printers-by-limitronic/ "Printers by Limitronic" (5) + live's banner (LIMITRONIC-3.jpg)
 * 'header': more Page Header fields (Subtitle, intro, a cut-out image from the design); 'after': sections after the
 * products (['cards', heading, background, [[title, text, image, link]…]] → Card Grid; ['cta', heading, intro, button,
 * second button] → CTA Band; a link starting with "/" → that page); 'enabled' => false: a page switched off (as live, or
 * not built yet: a placeholder parent). A switched-off page whose spec is no longer switched off is built in place: its
 * blocks replaced, SEO set, switched on (stops on an open draft).
 * A page with 'banner' (live's banner image, set as a section background there) gets it in its Page Header as a
 * Full-width banner, shown whole (it has text and logos in it): downloaded from live into Assets › Images › pages.
 * Live's mix-ups not copied: "Arrow EZCut 330A" links to the EZCut 350R (the 350R shows once), "Arrow ezcut 330S"
 * links to the EZCut 330R+ (shown with the 330R+'s own text); "Aries Laser Label Finishing" links to an address that
 * redirects to the Taurus Laser Finisher (shown as Taurus); "Arrow slitter" links to an address that now redirects to
 * the homepage (shown as its product, Arrow Slitter 1500: same photo).
 * Finisher cards (CARDS): empty Card Tagline / Key Specs filled with live's text (from arrow-label-finishers, which
 * names them right; the slitter from label-finishers); a short Card Title where the product name is long. Skipped
 * for a product with an open draft; values already set are kept.
 * Then every menu item, link field and rich text link pointing at a built page's live address links here (a switched-off
 * page's address: where config/redirects.php sends it; rich text gets the page's path, e.g. /all-products/plotters/, as
 * the blog's other internal links); skipped under an open draft; stops if a menu has unsaved builder changes. Live
 * redirects: config/redirects.php. Skips pages that exist, except: a page whose product list lacks products its spec
 * now names gets the spec's list (only when the spec covers every product on the page: CP additions are kept; not under
 * an open draft).
 * Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/pages/25_all_products.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\db\Query;
use craft\elements\Entry;
use craft\fields\Link;
use verbb\navigation\elements\Node;
use verbb\navigation\nodetypes\Entry as EntryNode;

const DESIGN = '/home/avadh/server/theglobalwebdev/demo/public_html/Arrowsystem/assets/';
const LIMITRONIC = ['limitronic-v6-lite', 'limitronic-v6-2-color-box-printer', 'limitronic-v7-color-box-printer',
    'limitronic-v7-white-box-printer', 'limitronic-v6-color-box-printer'];
const FINISHERS = ['aries', 'taurus-laser-finisher', 'arrowcut-nova-330r-roll-to-roll-laser-cutter', 'arrowcut-nova-250r-laser-label-cutter',
    'arrow-eco-300-label-cutter', 'arrow-ezcut-330r', 'arrowezcut-350r', 'arrowezcut-330rplus', 'arrow-ezcut-240', 'arrow-arrow-slitter-1500'];
// path => page; parent = the path of the parent page; blocks = [heading, product slugs, optional "view all" [label, live
// url]]: a "view all" to a page built here is turned into a link to that page below, like every other link
const PAGES = [
    'all-products' => ['title' => 'All Products', 'parent' => null, 'eyebrow' => 'Products', 'h1' => 'All [Products]',
        'seoTitle' => 'All Products - Arrow Systems, Inc.',
        'seoDescription' => 'Experience the ultimate blend of digital speed and analog finesse for premium labels.',
        'blocks' => [
            ['ArrowJet Digital Label [Printers]', ['arrowjet-aqua-330r-hybrid-pro-plus-m', 'arrowjet-aqua-330r-hybrid-pro-m', 'arrowjet-aqua-800m',
                'arrow-bolt-330r-hybrid-pro-m', 'arrowjet-uv', 'arrowjet-aqua-330r-lite-2', 'arrowjet-nova-330r', 'arrow-overjet-pro-700',
                'arrowjet-nova250r-inline-digital-printer-and-laser-die-cutter-2'],
                ['View all digital label printers', 'https://arrsys.com/all-products/digital-label-printers/arrow-digital-label-printers/']],
            ['Digital Label [Finishers]', FINISHERS, ['View all label finishers', 'https://arrsys.com/all-products/label-finishers/']],
        ]],
    'all-products/label-finishers' => ['title' => 'Label Finishers', 'parent' => 'all-products', 'eyebrow' => 'Product category', 'h1' => 'Label [Finishers]',
        'seoTitle' => 'High-Performance Label Finishers | Arrow Systems, Inc.',
        'seoDescription' => 'Explore Arrow Systems’ advanced Label Finishers for precise cutting, slitting, and laminating—ideal for custom labels and short-run jobs.',
        'blocks' => [['Our Label [Finishers]', FINISHERS, null]]],
    'all-products/label-finishers/arrow-label-finishers' => ['title' => 'Arrow Label Finishers', 'parent' => 'all-products/label-finishers',
        'eyebrow' => 'Product category', 'h1' => 'Digital Label Finishing [Equipments]',
        'seoTitle' => 'Digital Label Finishers: Enhance Your Labeling Process',
        'seoDescription' => 'Discover how Digital Label Finishers can elevate your printing capabilities. Learn about features, benefits, and why they’re vital for your business.',
        'banner' => ['Label-Finishers.jpg', 'Label finishers: labelled whiskey, honey and green tea packs beside an ArrowCut Nova 330R laser finisher'],
        'blocks' => [['Arrow Label [Finishers]', ['aries', 'taurus-laser-finisher', 'arrowcut-nova-330r-roll-to-roll-laser-cutter', 'arrowezcut-330rplus',
            'arrowcut-nova-250r-laser-label-cutter', 'arrow-eco-300-label-cutter', 'arrow-ezcut-330r', 'arrowezcut-350r', 'arrow-ezcut-240', 'arrow-lam-380'], null]]],
    'all-products/printers' => ['title' => 'Printers', 'parent' => 'all-products', 'eyebrow' => 'Product category', 'h1' => '[Printer]',
        'seoTitle' => 'Advanced Digital Printers | Arrow Systems, Inc.',
        'seoDescription' => 'Discover high-performance digital printers from Arrow Systems—designed for labels, packaging, and industrial printing with unmatched precision.',
        'blocks' => [['ArrowJet Digital Label [Printers]', ['arrowjet-aqua-330r-hybrid-pro-plus-m', 'arrowjet-aqua-330r-hybrid-pro-m', 'arrowjet-aqua-800m',
            'arrow-bolt-330r-hybrid-pro-m', 'arrowjet-uv', 'arrowjet-aqua-330r-lite-2', 'arrow-overjet-pro-700',
            'arrowjet-nova250r-inline-digital-printer-and-laser-die-cutter-2', 'arrowjet-nova-330r'],
            ['View all digital label printers', 'https://arrsys.com/all-products/digital-label-printers/arrow-digital-label-printers/']]]],
    'all-products/printers/gerber-printers' => ['title' => 'Gerber Printers', 'parent' => 'all-products/printers', 'eyebrow' => 'Product category',
        'h1' => 'Printers by [Gerber]',
        'seoTitle' => 'Printers by Gerber - Arrow Systems, Inc.',
        'seoDescription' => 'Printing Technology: Thermal transfer',
        'banner' => ['GERBER-PRINTERS.jpg', 'Gerber Technology EDGE FX thermal transfer printer beside aircraft safety placards'],
        'blocks' => [['Gerber [Printers]', ['gerber-edge-fx'], null]]],
    'all-products/printers/trojan-printers' => ['title' => 'Trojan Printers', 'parent' => 'all-products/printers', 'eyebrow' => 'Product category',
        'h1' => 'Printers by [Trojan]', 'seoTitle' => 'Printers by Trojan - Arrow Systems, Inc.', 'seoDescription' => null,
        'blocks' => [['Trojan [Printers]', ['trojan-t2-l', 'trojan-t2-c-large-capacity-tabletop-digital-printer', 'trojan-t2',
            'trojan-t4-all-in-one-digital-label-printer-and-finisher'], null]]],
    'all-products/printers/quicklabel-printers' => ['title' => 'QuickLabel Printers', 'parent' => 'all-products/printers', 'eyebrow' => 'Product category',
        'h1' => 'Printers by [QuickLabel]', 'seoTitle' => 'Printers by QuickLabel - Arrow Systems, Inc.', 'seoDescription' => null,
        'blocks' => [['QuickLabel [Printers]', ['ql-300-5-color-toner-printer'], null]]],
    'all-products/plotters' => ['title' => 'Gerber Plotters', 'parent' => 'all-products', 'eyebrow' => 'Product category', 'h1' => 'Gerber [Plotters]',
        'seoTitle' => 'Gerber Plotters | Arrow Systems for Precision Cutting',
        'seoDescription' => 'Discover Gerber Plotters and Arrow Systems for precise cutting solutions. Enhance your projects with superior quality and reliability.',
        'banner' => ['Gerber-Envision-375-Plotter.jpg', 'Gerber Technology plotter cutting labels, beside fruit and logo sticker samples'],
        'blocks' => [['Our Gerber [Plotters]', ['gerber-gs15-plus-plotter', 'gerber-envision-375-plotter', 'gerber-gsxplus'], null]]],
    'all-products/digital-label-printers' => ['title' => 'Digital Label Printers', 'parent' => 'all-products', 'enabled' => false,
        'eyebrow' => 'Product category', 'h1' => 'Digital Label [Printers]', 'seoTitle' => null, 'seoDescription' => null, 'blocks' => []],
    'all-products/digital-label-printers/arrow-digital-label-printers' => ['title' => 'ArrowJet Digital Label Printers',
        'parent' => 'all-products/digital-label-printers', 'eyebrow' => 'Product category', 'h1' => 'ArrowJet Digital Label [Printers]',
        'seoTitle' => 'Digital Label Printers by ArrowJet | Inkjet Solutions',
        'seoDescription' => 'Discover ArrowJet\'s Digital Label Printers for flexible packaging. High-quality inkjet printers designed to enhance your printing experience.',
        'header' => ['subheading' => 'Commercial Inkjet Label Printers for Production-Scale Printing',
            'sectionLead' => 'ArrowJet digital label printers are built for converters, brand owners, and in-house production teams that demand professional output at commercial scale. Powered by Memjet DuraFlex technology, our inkjet label printers deliver high-resolution CMYK output at production speeds — with no plates, no setup waste, and no compromise on quality.',
            'cutout' => [DESIGN . 'products/web/arrowjet-aqua-330r.webp', 'ArrowJet Aqua 330R digital label printer']],
        'blocks' => [['ArrowJet Printer [Lineup]', ['arrowjet-eco-330r', 'arrowjet-aqua-330r-lite-2', 'arrowjet-aqua-330r-ii', 'arrowjet-aqua-800m',
            'arrow-bolt-330r-hybrid-pro-m', 'arrowjet-aqua-330r-hybrid-pro-plus-m', 'arrow-overjet-pro-700', 'arrowjet-nova-250-plus',
            'arrowjet-nova250r-inline-digital-printer-and-laser-die-cutter-2', 'arrowjet-nova-330r', 'arrowjet-lumina-48f', 'arrowjet-aqua-330r-hybrid-pro-m'], null]],
        'after' => [
            ['cards', null, null, [
                ['Why ArrowJet Inkjet Label Printers?', 'ArrowJet inkjet label printing systems are engineered for the realities of commercial label production — fast job changeovers, consistent color across runs, and substrate flexibility that covers BOPP, PET, PP, paper, and specialty films. Whether you’re producing food and beverage labels, pharmaceutical packaging, or cosmetic product labels, our printers deliver the print quality your customers expect at the throughput your operation demands.'],
                ['Commercial Color Label Printing for Every Application', 'From short-run boutique batches to high-volume commercial color label production, the ArrowJet range of commercial color label printers scales with your output. Choose aqueous pigment systems for vibrant, water-resistant labels at lower operating cost — or step up to a UV hybrid press for specialty substrates, opaque white, and premium varnish finishes. Both platforms run as standalone production label printers or integrate with inline finishing for a complete print-and-cut workflow.'],
                ['Label Printing Equipment Built for Real Production Environments', 'ArrowJet label printing equipment is designed to run. Single-pass print engines deliver speeds from 20 to 195 feet per minute, with print widths up to 25 inches across the range. Single-phase power options, compact footprints, and straightforward installation mean you’re producing from day one — without the infrastructure overhead of traditional press equipment. Find your inkjet printer for label printing in the product lineup above, or speak with our team to match the right system to your volume and substrate requirements.'],
            ]],
            ['cards', 'Built for Commercial [Label Production]', 'surface', [
                ['Converters & Label Printers', 'Short-run digital label production with fast job changeovers and no plate costs.'],
                ['Brand Owners (In-House)', 'Full-color labels for food, beverage, cosmetic, and nutraceutical products — printed on demand.'],
                ['Industrial & OEM', 'High-volume, durable labels for chemical, pharmaceutical, and manufacturing environments.'],
            ]],
            ['cta', 'Not sure which printer fits?', 'Tell us your application, materials and run lengths.',
                ['Talk to a Specialist', 'tel:+18886972749'], ['Request a Quote', '/request-quote/']],
            ['cards', 'Other [Categories]', null, [
                ['Digital Label Finishers', 'Die-cutting, slitting and finishing for printed label rolls.',
                    [DESIGN . 'categories/web/category-digital-label-finishers.webp', 'Arrow EZCut digital label finisher'], ['Digital Label Finishers', '/all-products/label-finishers/arrow-label-finishers/']],
                ['Print & Cut Inline', 'Print and finish labels in one pass.',
                    [DESIGN . 'categories/web/category-print-cut-inline.webp', 'ArrowJet Nova print and cut inline system'], ['Print & Cut Inline', 'https://arrsys.com/all-products/print-and-cut-systems/']],
                ['Label Materials', 'ArrowJet inkjet receptive media, foils, tag stocks and specialty laminates.',
                    [DESIGN . 'materials/web/material-holography-1.webp', 'Holographic label printed with water-based ink'], ['Label Materials', 'https://arrsys.com/arrow-materials/']],
            ]],
        ]],
    'all-products/digital-label-printers/trojan-digital-label-printers' => ['title' => 'Digital Label Printers by Trojan',
        'parent' => 'all-products/digital-label-printers', 'eyebrow' => 'Product category', 'h1' => 'Digital Label Printers by [Trojan]',
        'seoTitle' => 'Digital Label Printers by Trojan - Arrow Systems, Inc.', 'seoDescription' => 'DIGITAL LABEL PRINTERS BY TROJAN',   // as live
        'blocks' => [['Trojan Digital Label [Printers]', ['trojan-t2-l', 'trojan-t2-c-large-capacity-tabletop-digital-printer', 'trojan-t2'], null]]],
    'all-products/corrugate-and-end-of-line-printing' => ['title' => 'Corrugate and End of Line Printing', 'parent' => 'all-products',
        'eyebrow' => 'Product category', 'h1' => 'Corrugate and End of Line [Printing]',
        'seoTitle' => 'Corrugate and End of Line Printing - Arrow Systems, Inc.', 'seoDescription' => 'Printhead resolution: 180dpi',   // as live
        'blocks' => [['Printers by [Limitronic]', LIMITRONIC, null]]],
    'all-products/corrugate-and-end-of-line-printing/printers-by-limitronic' => ['title' => 'Printers by Limitronic',
        'parent' => 'all-products/corrugate-and-end-of-line-printing', 'eyebrow' => 'Product category', 'h1' => 'Printers by [Limitronic]',
        'seoTitle' => 'Limitronic Printers: V6 Lite, V6 Standard & V7 Color',
        'seoDescription' => 'Discover Limitronic printers, including V6 Lite, V6 Standard, and V7 Color. Explore their features and find the perfect printer for your needs.',
        'banner' => ['LIMITRONIC-3.jpg', 'Limitronic V7 Color printing on a wooden crate, beside printed crate, carton and seafood box labels'],
        'blocks' => [['Limitronic [Printers]', LIMITRONIC, null]]],
];
// product slug => [Card Title (null = keep the product name), tagline, [label, value]…], live's text
const CARDS = [
    'aries' => ['Aries Semi-Rotary Finishing', 'The future of high volume label finishing with ARIES',
        [['Max Roll Diameter', '500mm'], ['Cutting Media Width', '330mm'], ['Max. Die cutting speed', '30m/min']]],
    'taurus-laser-finisher' => [null, 'Precision Meets Efficiency in Label Finishing',
        [['Max Roll Diameter', '500mm'], ['Cutting Media Width', '330mm'], ['Max. Die cutting speed', '70m/min']]],
    'arrowcut-nova-330r-roll-to-roll-laser-cutter' => ['ArrowCut Nova 330R', 'Roll to Roll Laser Finisher',
        [['Max Web Width', '350mm'], ['Max Web Speed', '30m/min'], ['Max Roll Diameter', '400 mm'], ['Cutting Area', '300mm x 300mm']]],
    'arrowezcut-330rplus' => [null, 'An advanced roll-to-roll digital label die cutter designed for precision, flexibility, and efficiency.',
        [['Max. Roll Diameter', '450 mm'], ['Max die-cutting Speed', '6m/min'], ['Slitting Width', '10 – 310 mm'], ['Slitting Blades', 'Up to 15 Blades']]],
    'arrowcut-nova-250r-laser-label-cutter' => ['ArrowCut Nova 250R', 'Laser Die Cutter',
        [['Max Web Width', '250mm'], ['Max Web Speed', '25m/min'], ['Max Roll Diameter', '300 mm'], ['Cutting Area', '225mm x 225mm']]],
    'arrow-eco-300-label-cutter' => ['Arrow Eco-300', 'Roll-to-Roll Desktop Finisher',
        [['Max Roll OD', '355mm'], ['Max Cutting Width', '305mm'], ['Max Cutting Speed', '3’/min'], ['Max Media Size', '406mm']]],
    'arrow-ezcut-330r' => ['Arrow EZCut 330R', 'Roll to Roll Multi Blade Digital Label Die-Cutter',
        [['Max Media Diameter', '450mm'], ['Cutting Media Width', '40-340mm'], ['Max. Die cutting speed', '6m/min / 100m/min'], ['Slitting Width', '10~310mm']]],
    'arrowezcut-350r' => [null, 'Multiple Blades Digital Label Cutter',
        [['Max Roll Diameter', '450mm'], ['Cutting Media Width', '100-350mm'], ['Max. Die cutting speed', '9m/min']]],
    'arrow-ezcut-240' => [null, 'Automatic Digital Roll Label Cutter',
        [['Max Roll Diameter', '290mm'], ['Media label Width', '100- 230mm'], ['Max. Cutting Speed', '8m/min (single unit)'], ['No. of Cutting Heads/ Slitter', '2 sets']]],
    'arrow-arrow-slitter-1500' => ['Arrow Slitter 1500', 'A perfect offline tool for slitting and rewinding rolls',
        [['Max. Media Width', '500mm'], ['Max Slitting Speed', '100m/min'], ['Min Slitting Width', '12mm'], ['Slitting Blades', 'Up to 10 Blades']]],
    'arrow-lam-380' => [null, 'Reel-to-Reel Thermal Laminating Machine', []],
    'gerber-edge-fx' => [null, '15″ Thermal Transfer Printer',
        [['Printing Technology', 'Thermal transfer'], ['Max. Printing Width', '11.8″ (30 cm)'], ['Material Width', '15″ (38 cm) punched media'],
         ['Resolution', '300, 600 or 1200 dpi'], ['Colors', 'Over 75 color foils available']]],
    'trojan-t2-l' => [null, 'Roll-to-Roll Inkjet Mini Press w/ Lamination',
        [['Resolution', '1600 x 1600 dpi full color'], ['Speed', '6 ips (9 m/min) or 12 ips (18 m/min)'], ['Print and laminate', '6 ips (9 m/min)'],
         ['Print Area', 'Width: 2″ – 8.76″ (51 – 222.5 mm); length 0.4″ (10 mm) – 60″ (1525 mm) or unlimited stitched'],
         ['Ink', 'High-performance water-based dye ink, four 2-liter tanks CMYK']]],
    'trojan-t2-c-large-capacity-tabletop-digital-printer' => ['Trojan T2-C', 'Full Capacity Digital Tabletop Press',
        [['Resolution', '1600 x 800 dpi or 1600 x 1600 dpi'], ['Speed', '6 ips (9 m/min) or 12 ips (18 m/min)'], ['Print Area', 'Width: 8.8″ (223 mm)'],
         ['Length without stitching', '0.6″ – 30″ (15 – 762 mm)'], ['Length with stitching', '0.4″ (10 mm) – 60″ (1525 mm) or unlimited'],
         ['Ink', 'High-performance water-based dye ink, four 2-liter tanks CMYK']]],
    'trojan-t2' => [null, 'Full Capacity Digital Tabletop Press',   // live's card: the same text as the T2-C's
        [['Resolution', '1600 x 800 dpi or 1600 x 1600 dpi'], ['Speed', '6 ips (9 m/min) or 12 ips (18 m/min)'], ['Print Area', 'Width: 8.8″ (223 mm)'],
         ['Length without stitching', '0.6″ – 30″ (15 – 762 mm)'], ['Length with stitching', '0.4″ (10 mm) – 60″ (1525 mm) or unlimited'],
         ['Ink', 'High-performance water-based dye ink, four 2-liter tanks CMYK']]],
    'trojan-t4-all-in-one-digital-label-printer-and-finisher' => ['Trojan T4', 'All-in-One Digital Label Printer and Finisher', []],
    'ql-300-5-color-toner-printer' => ['QuickLabel QL-300', '5 Color Laser Label Printer', []],
    'gerber-gs15-plus-plotter' => [null, 'Tangential Cutting & Swivel Knife Technology',
        [['Cutting Width', '13.25″ (327 mm)'], ['Cutting Speed', '4 ips (101.6 mm)'], ['Cutting Depth Adj', 'Dial Control'],
         ['Cutting Technology', 'Tangential or Swivel 30°, 45°, or 60°']]],
    'gerber-envision-375-plotter' => [null, 'Gerber Sprocket-feed Tabletop 15″ plotter',
        [['Cutting Width', '13.25″ (327 mm)'], ['Cutting Speed', '36 ips/50 ips (914.4/1270 mm)'], ['Cutting Depth Adj', 'Key Pad'],
         ['Cutting Technology', 'Swivel Knife 30°, 45° or 60° w/corner compensation']]],
    'gerber-gsxplus' => [null, 'Use 15″ or 30″ perforated materials',
        [['Cutting Width', '13.25″ (327 mm)'], ['Cutting Speed', '3.6 ips (91.44 mm)'], ['Cutting Depth Adj', 'Dial Control'],
         ['Cutting Technology', 'Swivel Knife 30°, 45° or 60° w/corner compensation']]],
    // Limitronic: live's spec lines joined ("0.2-192 m/min" + "depending resolution"), "injket" and "5 0 mm" corrected
    'limitronic-v6-lite' => [null, 'Online digital printing of GHS labels',
        [['Printhead resolution', '180dpi'], ['Speed', '0.2–192 m/min depending on resolution'], ['Height', '72 mm – 140 mm'], ['Densities', '3 levels']]],
    'limitronic-v6-2-color-box-printer' => [null, 'Modular solutions of high resolution printing',
        [['Printhead resolution', '180dpi'], ['Speed', '60 m/min @ 180dpi, 0.2–192 m/min depending on resolution'], ['Height', '72 mm / 140 mm per block to 180'], ['Densities', '3 levels']]],
    'limitronic-v7-color-box-printer' => [null, 'Hi-res full-color printing',
        [['Printhead resolution', '360dpi'], ['Speed', 'Up to 60 m/min depending on resolution'], ['Height', '70 mm (scalable)'], ['Densities', '7 levels']]],
    'limitronic-v7-white-box-printer' => [null, 'High resolution inkjet printing on white',
        [['Printhead resolution', '300dpi'], ['Speed', 'Up to 30 m/min depending on resolution'], ['Height', '50 mm'], ['Densities', '5 levels']]],
    'limitronic-v6-color-box-printer' => [null, 'All color printing',
        [['Printhead resolution', '180dpi'], ['Speed', '0.2–192 m/min depending on resolution'], ['Height', '72 mm / 140 mm'], ['Densities', '3 levels']]],
];

$elements = Craft::$app->getElements();
$product = fn(string $slug) => Entry::find()->section('products')->slug($slug)->status(null)->one() ?? exit("no product $slug\n");
$hasDraft = fn(int $id) => (new Query())->from('{{%drafts}}')->where(['canonicalId' => $id])->exists();

// products a page lists that were switched off on staging but live shows (product page and listings)
const SWITCH_ON = ['trojan-t2'];   // off since "Draft 1" was applied in the CP on 2026-10-16; live: /product/trojan-t2/ + the Trojan listing
foreach (SWITCH_ON as $slug) {
    $p = $product($slug);
    if ($p->enabled) continue;
    if ($hasDraft($p->id)) { say("  ! {$p->title}: open draft, not switched on"); continue; }
    $p->enabled = true;
    $elements->saveElement($p) || fail("product $slug", $p);
    say("  ~ {$p->title}: switched on");
}

// product cards: fill what's empty
foreach (CARDS as $slug => [$cardTitle, $tagline, $specs]) {
    $p = $product($slug);
    $set = [];
    if ($cardTitle && !$p->headingText) $set['headingText'] = $cardTitle;
    if (!$p->cardTagline) $set['cardTagline'] = $tagline;
    if ($specs && !$p->getFieldValue('keySpecs')) $set['keySpecs'] = array_map(fn($r) => ['col1' => $r[0], 'col2' => $r[1]], $specs);
    if (!$set) continue;
    if ($hasDraft($p->id)) { say("  ! {$p->title}: open draft, card not filled"); continue; }
    $p->setFieldValues($set);
    $elements->saveElement($p) || fail("product $slug", $p);
    say("  ~ {$p->title}: " . implode(', ', array_keys($set)));
}

// an image → Assets › Images › pages (once): live's banner (its /media/ file name) or a design file (a full path)
$imageAsset = function (string $path, string $alt) use ($elements): craft\elements\Asset {
    $file = basename($path);
    $volume = Craft::$app->getVolumes()->getVolumeByHandle('images');
    $folder = Craft::$app->getAssets()->ensureFolderByFullPathAndVolume('pages', $volume, false);
    if ($a = craft\elements\Asset::find()->folderId($folder->id)->filename($file)->one()) return $a;
    $bytes = (str_starts_with($path, '/') ? @file_get_contents($path)
        : @file_get_contents("https://arrsys.com/media/$file", false, stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0 (Arrow Systems staging migration)\r\n", 'timeout' => 90]])))
        ?: exit("could not read $path\n");
    $tmp = Craft::$app->getPath()->getTempPath() . '/' . uniqid('banner') . "-$file";
    file_put_contents($tmp, $bytes);
    $a = new craft\elements\Asset(['tempFilePath' => $tmp, 'filename' => $file, 'volumeId' => $volume->id, 'newFolderId' => $folder->id]);
    $a->alt = $alt;
    $a->setScenario(craft\elements\Asset::SCENARIO_CREATE);
    $elements->saveElement($a) || fail("banner $file", $a);
    say("  + asset pages/$file");
    return $a;
};
$bannerFields = fn(array $banner) => ['mediaImage' => [$imageAsset(...$banner)->id], 'headerStyle' => 'banner', 'bannerHeight' => 'whole'];
$headerFields = fn(array $h) => array_diff_key($h, ['cutout' => 1]) + (isset($h['cutout']) ? ['mediaImage' => [$imageAsset(...$h['cutout'])->id], 'headerStyle' => 'cutout'] : []);
// sections after the products (see 'after' above)
$link = fn(?array $l) => !$l ? null : (str_starts_with($l[1], '/')
    ? ['type' => 'entry', 'value' => (Entry::find()->section('pages')->uri(trim($l[1], '/'))->status(null)->one() ?? exit("no page {$l[1]}\n"))->id, 'label' => $l[0]]
    : ['type' => str_starts_with($l[1], 'tel:') ? 'tel' : 'url', 'value' => $l[1], 'label' => $l[0]]);
$sectionBlock = fn(array $s) => $s[0] === 'cta'
    ? ['type' => 'ctaBand', 'fields' => ['sectionHeading' => $s[1], 'sectionLead' => $s[2], 'ctaLink' => $link($s[3]), 'secondaryCta' => $link($s[4])]]
    : ['type' => 'cardGrid', 'fields' => ['sectionHeading' => $s[1], 'sectionBackground' => $s[2], 'cardColumns' => '3', 'cardShadow' => 'none',
        'cardItems' => ['sortOrder' => array_map(fn($i) => "c$i", array_keys($s[3])), 'entries' => array_combine(array_map(fn($i) => "c$i", array_keys($s[3])),
            array_map(fn($c) => ['type' => 'card', 'fields' => ['headingText' => $c[0], 'richText' => '<p>' . htmlspecialchars($c[1]) . '</p>',
                'mediaImage' => isset($c[2]) ? [$imageAsset(...$c[2])->id] : [], 'ctaLink' => $link($c[3] ?? null)]], $s[3]))]]];

// pages, parents first
$pagesSection = Craft::$app->getEntries()->getSectionByHandle('pages');
$built = [];
foreach (PAGES as $path => $spec) {
    $page = Entry::find()->section('pages')->uri($path)->status(null)->one();
    $placeholder = $page && !$page->enabled && ($spec['enabled'] ?? true);   // a switched-off placeholder, built now
    if (!$page || $placeholder) {
        $blocks = ['b0' => ['type' => 'industryHeader', 'fields' => ['eyebrow' => $spec['eyebrow'], 'sectionHeading' => $spec['h1']]
            + (isset($spec['banner']) ? $bannerFields($spec['banner']) : []) + $headerFields($spec['header'] ?? [])]];
        foreach ($spec['blocks'] as $i => [$heading, $slugs, $viewAll]) {
            $blocks['b' . ($i + 1)] = ['type' => 'productShowcase', 'fields' => ['sectionHeading' => $heading, 'productSource' => 'picked',
                'selectedProducts' => array_map(fn($s) => $product($s)->id, $slugs),
                'ctaLink' => $viewAll ? ['type' => 'url', 'value' => $viewAll[1], 'label' => $viewAll[0]] : null]];
        }
        foreach ($spec['after'] ?? [] as $s) $blocks['b' . count($blocks)] = $sectionBlock($s);
        if ($placeholder) {
            !$hasDraft($page->id) || exit("/$path/ has an open draft: apply or delete it, then run again\n");
            $page->enabled = true;
        } else {
            $page = new Entry(['sectionId' => $pagesSection->id, 'typeId' => Craft::$app->getEntries()->getEntryTypeByHandle('page')->id,
                'title' => $spec['title'], 'slug' => basename($path), 'enabled' => $spec['enabled'] ?? true]);
            if ($spec['parent']) $page->setParentId(Entry::find()->section('pages')->uri($spec['parent'])->status(null)->one()?->id ?? exit("no parent page {$spec['parent']}\n"));
        }
        $page->setFieldValues(['seoTitle' => $spec['seoTitle'], 'seoDescription' => $spec['seoDescription'],
            'pageBuilder' => ['sortOrder' => array_keys($blocks), 'entries' => $blocks]]);
        $elements->saveElement($page) || fail("page $path", $page);
        $page->uri === $path || exit("the page got /{$page->uri}/, expected /$path/\n");
        say(($placeholder ? '  ~ placeholder' : '  +') . " page /{$page->uri}/ (" . implode(', ', array_map(fn($b) => count($b[1]) . ' products', $spec['blocks'])) . ')');
    }
    // products added to the spec after the page was built: the page gets the spec's list, when it covers every product on it
    foreach (Entry::find()->ownerId($page->id)->type('productShowcase')->status(null)->all() as $i => $grid) {
        $want = array_map(fn($s) => $product($s)->id, $spec['blocks'][$i][1] ?? []);
        $have = $grid->getFieldValue('selectedProducts')->status(null)->ids();
        if (!$want || $have === $want || array_diff($have, $want)) continue;
        if ($hasDraft($page->id)) { say("  ! /$path/ has an open draft: products not updated"); continue; }
        $grid->setFieldValue('selectedProducts', $want);
        $elements->saveElement($grid) || fail("products of $path", $grid);
        say("  ~ /$path/: " . count($want) . ' products (was ' . count($have) . ')');
    }
    // a banner added to the spec after the page was built: into its Page Header (not under an open draft)
    $header = Entry::find()->ownerId($page->id)->type('industryHeader')->status(null)->one();
    if (isset($spec['banner']) && $header && !$header->getFieldValue('mediaImage')->exists()) {
        if ($hasDraft($page->id)) say("  ! /$path/ has an open draft: banner not added");
        else {
            $header->setFieldValues($bannerFields($spec['banner']));
            $elements->saveElement($header) || fail("header of $path", $header);
            say("  ~ /$path/: Page Header banner {$spec['banner'][0]}");
        }
    }
    if ($spec['enabled'] ?? true) $built["https://arrsys.com/$path/"] = $page;
}
foreach (require dirname(__DIR__, 3) . '/config/redirects.php' as $r) {   // a switched-off page → where it redirects
    if (isset(PAGES[$r['from']]) && isset($built["https://arrsys.com{$r['to']}"])) $built["https://arrsys.com/{$r['from']}/"] = $built["https://arrsys.com{$r['to']}"];
}
// menus and link fields pointing at a built page's live address → the page
$target = fn(?string $url) => $url ? ($built[rtrim($url, '/') . '/'] ?? null) : null;
$nodes = array_filter(Node::find()->status(null)->all(), fn($n) => $target($n->url));
foreach (array_unique(array_map(fn($n) => $n->menuId, $nodes)) as $menuId) {
    foreach ((new Query())->from('{{%navigation_build_sessions}}')->where(['menuId' => $menuId])->all() as $s) {
        !array_filter([$s['structureMoves'], $s['addedNodeIds'], $s['stagedDeletes'], $s['nodeDraftMap'], $s['menuContentDraft']])
            || exit("menu $menuId has unsaved builder changes: Save or Discard them, then run again\n");
    }
}
foreach ($nodes as $n) {
    $page = $target($n->url);
    $n->type = EntryNode::class;
    $n->elementId = $page->id;
    $n->url = null;
    $elements->saveElement($n) || fail("menu node {$n->id}", $n);
    say("  ~ menu {$n->menu->handle} “{$n->title}” → /{$page->uri}/");
}
// an element's link fields and rich text pointing at a built page's live address → [field handle => new value]
$fixes = function (craft\base\ElementInterface $el) use ($target): array {
    $set = [];
    foreach ($el->getFieldLayout()?->getCustomFields() ?? [] as $f) {
        $v = $el->getFieldValue($f->handle);
        if ($f instanceof Link && ($page = $target((string)$v?->url))) {
            $set[$f->handle] = ['type' => 'entry', 'value' => $page->id, 'label' => $v->label, 'target' => $v->target];
        } elseif ($v instanceof craft\htmlfield\HtmlFieldData) {
            $html = preg_replace_callback('#https?://(?:www\.)?arrsys\.com/(all-products/[a-z0-9/-]*)#i',
                fn($m) => ($page = $target("https://arrsys.com/$m[1]")) ? "/{$page->uri}/" : $m[0], $raw = $v->getRawContent());
            if ($html !== $raw) $set[$f->handle] = $html;
        }
    }
    return $set;
};
$top = function ($el) { for (; $el instanceof Entry && $el->getPrimaryOwner(); $el = $el->getPrimaryOwner()); return $el; };   // the page / post (or its draft) that holds the block: check its canonical for drafts
$q = (new Query())->select('es.elementId')->from(['es' => '{{%elements_sites}}'])->innerJoin(['e' => '{{%elements}}'], '[[e.id]] = [[es.elementId]]')
    ->where(['e.revisionId' => null, 'e.draftId' => null, 'e.dateDeleted' => null])->andWhere(['like', 'es.content', 'arrsys.com/all-products']);
foreach ($q->column() as $id) {
    $el = $elements->getElementById($id, null, null, ['status' => null]);
    if (!$el || !($set = $fixes($el))) continue;
    $owner = $top($el);
    if ($hasDraft($owner->getCanonicalId())) { say("  ! “{$owner->title}” has an open draft: its link to live is left; apply or delete the draft, then run again"); continue; }
    $el->setFieldValues($set);
    $elements->saveElement($el) || fail("element $id", $el);
    say('  ~ ' . ($el instanceof Entry ? "{$el->type->handle} “" . ($el->getPrimaryOwner()?->title ?? $el->title) . '”' : $el::displayName() . ' “' . ($el->title ?? $id) . '”') . ': ' . implode(', ', array_keys($set)));
}

// self-check: each page loads at its live path with its cards, the breadcrumb shows its parents, nothing links to live
foreach (PAGES as $path => $spec) {
    if (!($spec['enabled'] ?? true)) {   // switched off: redirected as live, or not found until built
        !str_contains(get_headers("http://localhost:8081/$path/")[0], '200') || exit("/$path/ is switched off but shows\n");
        continue;
    }
    $html = @file_get_contents("http://localhost:8081/$path/") ?: exit("/$path/ does not load\n");
    $want = array_sum(array_map(fn($b) => count($b[1]), $spec['blocks']));
    ($n = substr_count($html, 'class="pcard"')) === $want || exit("/$path/: $n cards, expected $want\n");
    for ($crumb = $spec['parent']; $crumb && !(PAGES[$crumb]['enabled'] ?? true); $crumb = PAGES[$crumb]['parent']);   // switched-off parents aren't in it
    !$crumb || str_contains($html, '>' . PAGES[$crumb]['title'] . '</a></li>') || exit("/$path/: breadcrumb without its parent\n");
    $want = array_sum(array_map(fn($s) => $s[0] === 'cards' ? count($s[3]) : 0, $spec['after'] ?? []));
    ($n = substr_count($html, '<li class="g-card')) === $want || exit("/$path/: $n section cards, expected $want\n");
    !isset($spec['header']['subheading']) || str_contains($html, 'ind-detail-hero__subtitle') || exit("/$path/: no subtitle\n");
    !isset($spec['header']['cutout']) || str_contains($html, 'ind-detail-hero__cutout') || exit("/$path/: no cut-out image\n");
    !isset($spec['banner']) || str_contains($html, 'page-banner__cover') && str_contains($html, pathinfo($spec['banner'][0], PATHINFO_FILENAME)) || exit("/$path/: no banner\n");
}
!array_filter(Node::find()->status(null)->all(), fn($n) => $target($n->url)) || exit("a menu item still links to a live page that is built\n");
foreach ($q->column() as $id) {
    $el = $elements->getElementById($id, null, null, ['status' => null]);
    !$el || !$fixes($el) || $hasDraft($top($el)->getCanonicalId()) || exit("element $id still links to a live page that is built\n");
}
say('  = ' . count(PAGES) . ' pages at their live paths with their product cards and breadcrumbs; menus link here');
