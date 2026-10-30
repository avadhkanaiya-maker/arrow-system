<?php
/**
 * Links written as addresses to our own pages (live https://arrsys.com/… or root-relative /…) → links to the page
 * (client request 2026-10-29). The work is modules/builderui/LinkFixer.php (the server ran it once through a temporary
 * Utilities → Fix live links page, removed afterwards). Idempotent. Back up first.
 * Run from the project root, --dry-run first: /opt/lampp/bin/php storage/imports/pages/43_live_links_to_pages.php [--dry-run]
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

$dry = in_array('--dry-run', $argv, true);
$r = modules\builderui\LinkFixer::run(!$dry);
$byTarget = array_count_values(array_column($r['changes'], 'to'));
arsort($byTarget);
foreach ($byTarget as $to => $n) say(sprintf('%5d → %s', $n, $to));
foreach ($r['skipped'] as $s) say("  ! skipped $s");
foreach ($r['errors'] as $e) say("  ! not saved $e");
say(($dry ? '(dry run) ' : '') . count($r['changes']) . " link(s) in {$r['elements']} item(s)");
