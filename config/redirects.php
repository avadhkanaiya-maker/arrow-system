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
    ['from' => 'author/admin', 'to' => '/blog/', 'statusCode' => 301],
    ['from' => 'author/admin/<rest:.+>', 'to' => '/blog/', 'statusCode' => 301],

    // Old post URLs that the live site already redirects (WordPress redirect plugin, checked 2026-10-13).
    // These 9 posts are still published in WordPress but are not imported: their URL leads to the newer post.
    ['from' => 'digital-printings-next-frontier-flexible-packaging', 'to' => '/multilayer-flexible-packaging-with-arrowjet/', 'statusCode' => 301],
    ['from' => 'food-packagings-technology-is-changing', 'to' => '/whats-changing-in-food-packaging/', 'statusCode' => 301],
    ['from' => 'lemorau-eb-finishing-system-live-stream', 'to' => '/lemorau/', 'statusCode' => 301],
    ['from' => 'mylar-bags-for-hydrated-food', 'to' => '/mylar-food-storage-bags-with-arrowjet-solutions/', 'statusCode' => 301],
    ['from' => 'lemorau-jumbo-roll-slitter', 'to' => '/lemorau/', 'statusCode' => 301],
    ['from' => 'how-to-increase-brand-awareness-strategies-to-make-it-stick', 'to' => '/how-to-increase-brand-awareness-for-your-business/', 'statusCode' => 301],
    ['from' => 'print-your-own-custom-cardboard-boxes', 'to' => '/how-to-print-your-own-custom-made-boxes/', 'statusCode' => 301],
    ['from' => 'short-run-vs-long-run-custom-tape-how-digital-printing-changes-the-game-for-small-businesses', 'to' => '/what-is-the-minimum-order-for-custom-printed-tape-a-complete-guide-for-small-businesses/', 'statusCode' => 301],
    ['from' => 'white-label-printing-solutions', 'to' => '/private-label-cosmetics-7-ways-digital-printing-transforms-contract-manufacturing/', 'statusCode' => 301],

    // The "2025" versions of three posts now live at the "2026" URL (live 301s)
    ['from' => 'digital-label-printers-for-2025', 'to' => '/digital-label-printers-for-2026/', 'statusCode' => 301],
    ['from' => 'short-run-label-printing-what-every-converter-needs-to-know-in-2025', 'to' => '/short-run-label-printing-what-every-converter-needs-to-know-in-2026/', 'statusCode' => 301],
    ['from' => 'top-5-digital-label-printers-of-2025', 'to' => '/top-5-digital-label-printers-of-2026/', 'statusCode' => 301],

    // ---- Products migration (PRODUCT_CATEGORIES_MIGRATION_PLAN.md / Phase 18), 301s as on the live site (checked 2026-10-14) ----
    // Live product URLs that already redirect (not imported)
    ['from' => 'product/3m-microfluidic-diagnostic-film-9962-thin-double-sided-hydrophilic-film-configurable-2', 'to' => '/product/3m-microfluidic-diagnostic-film-9962-thin-double-sided-hydrophilic-film-configurable/', 'statusCode' => 301],
    ['from' => 'product/arrow-ezcut330a-roll-to-roll-or-roll-to-sheet-blade-cutter', 'to' => '/product/arrowezcut-350r/', 'statusCode' => 301],
    ['from' => 'product/arrow-ezcut-330s', 'to' => '/product/arrowezcut-330rplus/', 'statusCode' => 301],
    ['from' => 'product/arrowjet-vdp', 'to' => 'https://arrsys.com/all-products/corrugate-and-end-of-line-printing/printers-by-limitronic/', 'statusCode' => 301],
    ['from' => 'product/arrow-slitter', 'to' => '/', 'statusCode' => 301],
    ['from' => 'product/cpm-200gu', 'to' => '/', 'statusCode' => 301],
    ['from' => 'product/kerajet-digital-inkjet-printer-for-building-materials', 'to' => '/', 'statusCode' => 301],
    ['from' => 'product/plain-white-laminated-roll-330mm-wpp', 'to' => '/product/plain-white-laminated-roll-330mm/', 'statusCode' => 301],
    ['from' => 'product/slitters', 'to' => '/', 'statusCode' => 301],
    // Old slugs still linked from the design listing and the menus (301 on live too)
    ['from' => 'product/arrowjet-bolt', 'to' => '/product/arrow-bolt-330r-hybrid-pro-m/', 'statusCode' => 301],
    ['from' => 'product/arrowjet-hybrid-m-pro-plus', 'to' => '/product/arrowjet-aqua-330r-hybrid-pro-plus-m/', 'statusCode' => 301],
    ['from' => 'product/arrowjet-nova-250', 'to' => '/product/arrowjet-nova-250-plus/', 'statusCode' => 301],
    ['from' => 'product/arrowjet-aqua-330r', 'to' => '/product/arrowjet-aqua-330r-lite-2/', 'statusCode' => 301],
    ['from' => 'product/arrow-systems-anycut-iii-13-roll-to-roll-laser-cutter', 'to' => '/product/arrowcut-nova-330r-roll-to-roll-laser-cutter/', 'statusCode' => 301],
];
