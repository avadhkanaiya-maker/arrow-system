<?php
/**
 * One section rhythm site-wide (2026-10-28, QA): every section has the site's --section-y at the top and the bottom
 * (craft.css). The Flexible Packaging design's page layer (33_…, the pages' Custom CSS) set a tighter --section-y on
 * flexible-packaging and color-label-printer; that line goes, the rest of their Custom CSS stays.
 * Idempotent. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/pages/39_one_section_rhythm.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\elements\Entry;

const OLD_HEAD = "/* The Flexible Packaging design's page layer: tighter section spacing, and the logo's black (always next to red) in\n   section labels, small labels, icons and arrow badges */\nmain { --section-y: clamp(36px, 4vw, 56px); }\n";
const NEW_HEAD = "/* The Flexible Packaging design's page layer: the logo's black (always next to red) in section labels, small labels,\n   icons and arrow badges */\n";

foreach (Entry::find()->section('pages')->status(null)->all() as $page) {
    $css = (string)($page->getFieldLayout()->getFieldByHandle('customCss') ? $page->customCss : '');
    if (!str_contains($css, OLD_HEAD)) continue;
    if (Entry::find()->draftOf($page)->status(null)->exists() || Entry::find()->draftOf($page)->provisionalDrafts()->status(null)->exists()) {
        say("  ! {$page->uri}: has an open draft, skipped");
        continue;
    }
    $page->setFieldValue('customCss', str_replace(OLD_HEAD, NEW_HEAD, $css));
    Craft::$app->getElements()->saveElement($page) || fail("page {$page->uri}", $page);
    say("  ~ {$page->uri}: Custom CSS without the tighter --section-y");
}
