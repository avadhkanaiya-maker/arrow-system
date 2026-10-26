<?php
/**
 * Live's product form submissions into the Formie form Product Request Quote (06_product_request_quote.php).
 * Source: Elementor → Submissions → export of every form (2026-10-22): one CSV per form copy; the product form is
 * "Product Request Quote (<form id>)", 89 files, 1,007 submissions (2022-11 to 2026-10); also (2026-10-22) the 9 untitled
 * copies "(<form id>)" of older product pages (28, headings name / email / field_4765d5e / country).
 * The live fields have no labels (placeholders only), so the export's field headings are empty: they are read by position
 *   1 Name · 2 Email · 3 Contact Number · 4 Country/Region (older copies have no Country: 3 field columns)
 * and the rest by heading:
 *   Referrer → Product Page (the live page, as recorded) and Product (the staging product with that address; an
 *              address no longer on staging → its words, e.g. "Arrowjet Aqua 330r")
 *   Submission ID → Live Submission ID · Form Name (ID) → Live Form · User Agent → User Agent · User IP → the
 *   submission's IP · Created At → its date (live's clock, UTC-4) and title
 * The export writes text JSON-style (\r\n, \", \/): decoded as in 04_live_submissions.php. Nothing is sent (a directly
 * saved submission triggers no emails); saved without validation (old entries predate today's rules). Skips rows whose
 * Live Submission ID is already in Formie, so it can run again. The CSVs hold personal data: keep them out of the
 * project (pass the folder). Back up first.
 * Run from the project root:
 *   /opt/lampp/bin/php storage/imports/forms/07_product_request_quote_submissions.php ~/Downloads/arrow-system-all-form-submission
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\db\Table;
use craft\elements\Entry;
use craft\helpers\Db;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

const LIVE_TZ = '-04:00';
$dir = rtrim($argv[1] ?? exit("usage: … 07_product_request_quote_submissions.php <export folder>\n"), '/');
// "Product Request Quote (<id>)" + the untitled copies on older product pages "(<id>)". Not "All product Form": its 4th
// field is a message, not the country, and its 6 rows are all spam (SEO offers)
$files = array_merge(glob("$dir/*Product Request Quote (*.csv"), glob("$dir/*-export-(*.csv"))
    ?: exit("no product form CSVs in $dir\n");
$form = Form::find()->handle('productRequestQuote')->one() ?? exit("no Formie form productRequestQuote: run 06_product_request_quote.php\n");
foreach (['liveSubmissionId', 'liveForm', 'userAgent', 'product', 'productPage'] as $h) {
    in_array($h, array_map(fn($f) => $f->handle, $form->getFormLayout()->getCustomFields()), true) || exit("the form has no field $h: run 06_product_request_quote.php\n");
}

/** Rows of one export file, JSON-style escapes decoded (\" and \\ left for the CSV reader) */
$read = function (string $path): array {
    $raw = preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($path));
    $raw = preg_replace_callback('/\\\\(u[0-9a-fA-F]{4}|.)/s', fn($m) => match ($m[1][0]) {
        'r' => "\r", 'n' => "\n", 't' => "\t", '/' => '/',
        'u' => mb_convert_encoding(pack('H*', substr($m[1], 1)), 'UTF-8', 'UTF-16BE'),
        default => $m[0],
    }, $raw);
    $fh = fopen('php://memory', 'r+');
    fwrite($fh, $raw);
    rewind($fh);
    $rows = [];
    while (($r = fgetcsv($fh, null, ',', '"', '\\')) !== false) {
        if ($r !== [null]) $rows[] = array_map(fn($v) => trim(str_replace(["\r\n", "\r"], "\n", stripslashes((string)$v))), $r);
    }
    return $rows;
};

// staging products by address
$products = [];
foreach (Entry::find()->section('products')->status(null)->all() as $p) $products[$p->slug] = trim($p->title);
$productFor = function (string $referrer) use ($products): string {
    if (!preg_match('#/product/([^/?]+)#', $referrer, $m)) return '';
    return $products[$m[1]] ?? ucwords(str_replace(['-', '_'], ' ', $m[1]));
};

// already imported: Live Submission IDs
$seen = [];
foreach (Submission::find()->formId($form->id)->status(null)->all() as $s) {
    if ($id = trim((string)$s->getFieldValue('liveSubmissionId'))) $seen[$id] = true;
}

$utc = new DateTimeZone('UTC');
$added = $skipped = $total = 0;
$unmatched = [];
foreach ($files as $file) {
    $rows = $read($file);
    $head = array_shift($rows);
    $k = array_search('Form Name (ID)', $head, true);
    $k >= 3 || exit(basename($file) . ": unexpected columns\n");
    $col = fn(array $r, string $name) => $r[array_search($name, $head, true)] ?? '';
    foreach ($rows as $n => $r) {
        count($r) === count($head) || exit(basename($file) . ' row ' . ($n + 2) . ': ' . count($r) . ' columns, expected ' . count($head) . "\n");
        $total++;
        $liveId = $col($r, 'Submission ID');
        if (isset($seen[$liveId])) { $skipped++; continue; }
        $referrer = $col($r, 'Referrer');
        $product = $productFor($referrer);
        if ($product && preg_match('#/product/([^/?]+)#', $referrer, $m) && !isset($products[$m[1]])) $unmatched[$m[1]] = ($unmatched[$m[1]] ?? 0) + 1;
        $date = (new DateTime($col($r, 'Created At'), new DateTimeZone(LIVE_TZ)))->setTimezone($utc);

        $s = new Submission();
        $s->setForm($form);
        $s->setFieldValues([
            'yourName' => $r[0], 'emailAddress' => $r[1], 'phoneNumber' => $r[2], 'country' => $k >= 4 ? $r[3] : '',
            'product' => $product, 'productPage' => $referrer,
            'liveSubmissionId' => $liveId, 'liveForm' => $col($r, 'Form Name (ID)'), 'userAgent' => $col($r, 'User Agent'),
        ]);
        $s->ipAddress = $col($r, 'User IP') ?: null;
        $s->dateCreated = $s->dateUpdated = $date;
        Craft::$app->getElements()->saveElement($s, false) || fail("submission $liveId", $s);
        // title = the date, as Formie's own ({timestamp}); a directly saved submission gets "New Submission"
        Db::update(Table::ELEMENTS_SITES, ['title' => $col($r, 'Created At')], ['elementId' => $s->id, 'siteId' => $s->siteId]);
        $seen[$liveId] = true;
        $added++;
    }
}
say(count($files) . " files, $total rows: + $added submissions" . ($skipped ? ", $skipped already there" : ''));
if ($unmatched) {
    arsort($unmatched);
    say('  addresses not on staging (Product = their words): ' . implode(', ', array_map(fn($s, $c) => "$s ($c)", array_keys($unmatched), $unmatched)));
}

// self-check: every row is in Formie once
$ids = [];
foreach (Submission::find()->formId($form->id)->status(null)->all() as $s) {
    if ($id = trim((string)$s->getFieldValue('liveSubmissionId'))) $ids[$id] = ($ids[$id] ?? 0) + 1;
}
count($ids) === $total && max($ids) === 1 || exit(count($ids) . " imported, expected $total (or a duplicate)\n");
say("  = all $total live submissions in Product Request Quote, once each");
