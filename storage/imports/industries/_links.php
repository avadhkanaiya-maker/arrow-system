<?php
/** Links to pages that now live in Craft. Shared by 02_content.php and 03_connect.php. Require after the bootstrap. */

use craft\elements\Entry;

/** A live arrsys.com URL → root-relative when the page now lives in Craft (blog posts, industries, exhibitions,
 *  products, /industries/, /blog/ …); other URLs unchanged */
function localHref(string $href): string {
    static $local;
    if ($local === null) {
        $local = [];
        foreach (Entry::find()->section(['blog', 'industries', 'exhibitions'])->status(null)->all() as $e) if ($e->uri) $local[$e->uri] = true;
    }
    if (!preg_match('#^https?://(?:www\.)?arrsys\.com/([^?\#]*)(.*)$#i', trim($href), $m)) return $href;
    $path = trim($m[1], '/');
    $first = explode('/', $path)[0];
    return $path === 'industries' || isset($local[$path]) || in_array($first, ['product', 'blog', 'category', 'tag'], true)
        ? '/' . $path . ($path ? '/' : '') . $m[2] : $href;
}

/** localHref() for every link in some HTML */
function localLinks(string $html): string {
    return preg_replace_callback('#href="([^"]*)"#', fn($m) => 'href="' . localHref(html_entity_decode($m[1])) . '"', $html);
}
