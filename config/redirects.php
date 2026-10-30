<?php
/**
 * Redirection Rules
 *
 * Rules returned in this array are evaluated only after Craft would ordinarily
 * throw a 404 exception. They can be key-value pairs representing “from”
 * and “to” URIs…
 *
 * ```php
 * return [
 *     'old/path' => 'new/path',
 * ];
 * ```
 *
 * …or a nested array with `from`, `to`, `caseSensitive`, and `statusCode` keys:
 *
 * ```php
 * return [
 *     [
 *         'from' => 'Helpdesk.aspx',
 *         'to' => 'account/tickets',
 *         'caseSensitive' => true,
 *         'statusCode' => 301,
 *     ],
 * ];
 * ```
 *
 * Read all about Craft’s redirection behavior and capabilities, here:
 * @link https://craftcms.com/docs/5.x/system/routing.html#redirection
 */

return [
    // ---- Blog migration (BLOG_MIGRATION_PLAN.md), 301s as on the live site ----

    // Author archive (noindex on live; the design has no author pages)
    ['from' => 'author/admin', 'to' => 'blog/', 'statusCode' => 301],
    ['from' => 'author/admin/<rest:.+>', 'to' => 'blog/', 'statusCode' => 301],

    // Old post URLs that the live site already redirects (WordPress redirect plugin, checked 2026-10-13).
    // These 9 posts are still published in WordPress but are not imported: their URL leads to the newer post.
    ['from' => 'digital-printings-next-frontier-flexible-packaging', 'to' => 'multilayer-flexible-packaging-with-arrowjet/', 'statusCode' => 301],
    ['from' => 'food-packagings-technology-is-changing', 'to' => 'whats-changing-in-food-packaging/', 'statusCode' => 301],
    ['from' => 'lemorau-eb-finishing-system-live-stream', 'to' => 'lemorau/', 'statusCode' => 301],
    ['from' => 'mylar-bags-for-hydrated-food', 'to' => 'mylar-food-storage-bags-with-arrowjet-solutions/', 'statusCode' => 301],
    ['from' => 'lemorau-jumbo-roll-slitter', 'to' => 'lemorau/', 'statusCode' => 301],
    ['from' => 'how-to-increase-brand-awareness-strategies-to-make-it-stick', 'to' => 'how-to-increase-brand-awareness-for-your-business/', 'statusCode' => 301],
    ['from' => 'print-your-own-custom-cardboard-boxes', 'to' => 'how-to-print-your-own-custom-made-boxes/', 'statusCode' => 301],
    ['from' => 'short-run-vs-long-run-custom-tape-how-digital-printing-changes-the-game-for-small-businesses', 'to' => 'what-is-the-minimum-order-for-custom-printed-tape-a-complete-guide-for-small-businesses/', 'statusCode' => 301],
    ['from' => 'white-label-printing-solutions', 'to' => 'private-label-cosmetics-7-ways-digital-printing-transforms-contract-manufacturing/', 'statusCode' => 301],

    // The "2025" versions of three posts now live at the "2026" URL (live 301s)
    ['from' => 'digital-label-printers-for-2025', 'to' => 'digital-label-printers-for-2026/', 'statusCode' => 301],
    ['from' => 'short-run-label-printing-what-every-converter-needs-to-know-in-2025', 'to' => 'short-run-label-printing-what-every-converter-needs-to-know-in-2026/', 'statusCode' => 301],
    ['from' => 'top-5-digital-label-printers-of-2025', 'to' => 'top-5-digital-label-printers-of-2026/', 'statusCode' => 301],

    // ---- Products migration (PRODUCT_CATEGORIES_MIGRATION_PLAN.md / Phase 18), 301s as on the live site (checked 2026-10-14) ----
    // Live product URLs that already redirect (not imported)
    ['from' => 'product/3m-microfluidic-diagnostic-film-9962-thin-double-sided-hydrophilic-film-configurable-2', 'to' => 'product/3m-microfluidic-diagnostic-film-9962-thin-double-sided-hydrophilic-film-configurable/', 'statusCode' => 301],
    ['from' => 'product/arrow-ezcut330a-roll-to-roll-or-roll-to-sheet-blade-cutter', 'to' => 'product/arrowezcut-350r/', 'statusCode' => 301],
    ['from' => 'product/arrow-ezcut-330s', 'to' => 'product/arrowezcut-330rplus/', 'statusCode' => 301],
    ['from' => 'product/arrowjet-vdp', 'to' => 'all-products/corrugate-and-end-of-line-printing/printers-by-limitronic/', 'statusCode' => 301],
    ['from' => 'product/arrow-slitter', 'to' => '', 'statusCode' => 301],
    ['from' => 'product/cpm-200gu', 'to' => '', 'statusCode' => 301],
    ['from' => 'product/kerajet-digital-inkjet-printer-for-building-materials', 'to' => '', 'statusCode' => 301],
    ['from' => 'product/plain-white-laminated-roll-330mm-wpp', 'to' => 'product/plain-white-laminated-roll-330mm/', 'statusCode' => 301],
    ['from' => 'product/slitters', 'to' => '', 'statusCode' => 301],
    // Old slugs still linked from the design listing and the menus (301 on live too)
    ['from' => 'product/arrowjet-bolt', 'to' => 'product/arrow-bolt-330r-hybrid-pro-m/', 'statusCode' => 301],
    ['from' => 'product/arrowjet-hybrid-m-pro-plus', 'to' => 'product/arrowjet-aqua-330r-hybrid-pro-plus-m/', 'statusCode' => 301],
    ['from' => 'product/arrowjet-nova-250', 'to' => 'product/arrowjet-nova-250-plus/', 'statusCode' => 301],
    ['from' => 'product/arrowjet-aqua-330r', 'to' => 'product/arrowjet-aqua-330r-lite-2/', 'statusCode' => 301],
    ['from' => 'product/arrow-systems-anycut-iii-13-roll-to-roll-laser-cutter', 'to' => 'product/arrowcut-nova-330r-roll-to-roll-laser-cutter/', 'statusCode' => 301],

    // ---- /all-products/ pages (storage/imports/pages/25_all_products.php): live's 301s within the built part ----
    ['from' => 'all-products/label-finishers/lemorau-label-finishers', 'to' => 'all-products/label-finishers/', 'statusCode' => 301],
    ['from' => 'all-products/label-finishers/quicklabel-label-finishers', 'to' => 'all-products/label-finishers/', 'statusCode' => 301],
    ['from' => 'all-products/printers/arrow-printers', 'to' => 'all-products/printers/', 'statusCode' => 301],
    ['from' => 'all-products/printers/kerajet-printers', 'to' => '', 'statusCode' => 301],   // as live: Kerajet is no longer sold
    ['from' => 'all-products/digital-label-printers', 'to' => 'all-products/digital-label-printers/arrow-digital-label-printers/', 'statusCode' => 301],   // as live: the category page is its ArrowJet listing
    ['from' => 'product-category/printers/thermal-transfer-printer', 'to' => 'all-products/printers/thermal-transfer-printer/', 'statusCode' => 301],   // live's shop category → its page (42_thermal_transfer_printer.php)

    // ---- Old URLs still linked from blog articles (Blog QA B06, live's 301s checked 2026-10-28) ----
    ['from' => 'contact', 'to' => 'contact-us/', 'statusCode' => 301],
    ['from' => 'try-free-samples', 'to' => 'try-a-free-sample/', 'statusCode' => 301],
    ['from' => 'does-your-product-need-tamper-evident-labels', 'to' => 'tamper-evident-label-printing/', 'statusCode' => 301],
    ['from' => 'tamper-evident-tamper-proof-and-tamper-resistant-labels-whats-the-difference', 'to' => 'tamper-evident-label-printing/', 'statusCode' => 301],
    ['from' => 'effective-ways-to-use-white-space-in-label-design', 'to' => 'white-space-in-label-design/', 'statusCode' => 301],
    ['from' => 'the-great-debate-laser-die-cutting-vs-rotary-die-cutting', 'to' => 'laser-die-cutting-vs-rotary-die-cutting/', 'statusCode' => 301],
    ['from' => 'trends-in-labeling', 'to' => 'label-design-trends/', 'statusCode' => 301],
    ['from' => 'why-a-digital-label-printer-is-better-than-an-analog-printer', 'to' => 'digital-vs-analog-label-printing/', 'statusCode' => 301],
    ['from' => 'why-printing-labels-in-house-is-better-than-outsourcing', 'to' => 'in-house-label-printing-vs-outsourcing/', 'statusCode' => 301],
    ['from' => 'products/arrowjet-aqua-800m', 'to' => 'product/arrowjet-aqua-800m/', 'statusCode' => 301],
    ['from' => 'product/aries-laser-label-finishing', 'to' => 'product/taurus-laser-finisher/', 'statusCode' => 301],
    ['from' => 'product/arrow-overjet-pro', 'to' => 'product/arrow-overjet-pro-700/', 'statusCode' => 301],
    ['from' => 'product/arrow-overjet', 'to' => 'product/arrow-overjet-2/', 'statusCode' => 301],
    ['from' => 'product/arrowcut-nova-250r', 'to' => 'product/arrowcut-nova-250r-laser-label-cutter/', 'statusCode' => 301],
    ['from' => 'product/arrowcut-nova-330r', 'to' => 'product/arrowcut-nova-330r-roll-to-roll-laser-cutter/', 'statusCode' => 301],
    ['from' => 'product/arrowjet-aqua-330r-hybrid', 'to' => 'product/arrowjet-aqua-330r-hybrid-2/', 'statusCode' => 301],
    ['from' => 'product/arrowjet-overjet-pro', 'to' => 'product/arrowjet-overjet-pro-300/', 'statusCode' => 301],
    ['from' => 'product/limitronic-v7', 'to' => 'product/limitronic-v7-color-box-printer/', 'statusCode' => 301],
    // Discontinued products and categories: live sends these to the home page
    ['from' => 'all-products/digital-label-printers/anytron-digital-label-printers', 'to' => '', 'statusCode' => 301],
    ['from' => 'all-products/digital-label-printers/colordyne-digital-label-printers', 'to' => '', 'statusCode' => 301],
    ['from' => 'all-products/digital-label-printers/epson-digital-label-printers', 'to' => '', 'statusCode' => 301],
    ['from' => 'inventory/anytron-any-002-laser-printer', 'to' => '', 'statusCode' => 301],
    ['from' => 'product/anytron-any-002-laser-printer', 'to' => '', 'statusCode' => 301],
    ['from' => 'product/arrowjet-mini', 'to' => '', 'statusCode' => 301],
    ['from' => 'product/colordyne-2800-mini-laser-pro', 'to' => '', 'statusCode' => 301],
    ['from' => 'product/colordyne-3600-uv-retrofit', 'to' => '', 'statusCode' => 301],
    ['from' => 'product/epson-colorworks-c6500a', 'to' => '', 'statusCode' => 301],
    ['from' => 'product/epson-colorworks-c7500', 'to' => '', 'statusCode' => 301],
    ['from' => 'product/epson-colorworks-c7500g', 'to' => '', 'statusCode' => 301],
    // Broken on live too (404 there): the page they meant
    ['from' => 'all-products/digital-label-printers/arrow-digital-label-printers/arrow-digital-label-printers', 'to' => 'all-products/digital-label-printers/arrow-digital-label-printers/', 'statusCode' => 301],
    ['from' => 'all-products/digital-label-printers/quicklabel-digital-label-printers', 'to' => 'all-products/printers/quicklabel-printers/', 'statusCode' => 301],
    ['from' => 'color-label-printers', 'to' => 'color-label-printer/', 'statusCode' => 301],
    ['from' => 'cbd-label-requirements-what-your-labels-must-say', 'to' => 'cbd-label-requirement/', 'statusCode' => 301],
    ['from' => 'usda-food-label-guidelines-six-common-fsis-labeling-mistakes-to-avoid', 'to' => 'usda-food-label-guidelines/', 'statusCode' => 301],
    ['from' => 'product/anytron-anycut-i-table-top-laser-finisher', 'to' => '', 'statusCode' => 301],   // discontinued, like the Anytron printer above
    ['from' => 'product/lf-200-8-digital-blade-finisher', 'to' => 'all-products/label-finishers/', 'statusCode' => 301],
];
