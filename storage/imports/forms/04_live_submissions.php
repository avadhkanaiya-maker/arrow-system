<?php
/**
 * Form submissions from the live site (Elementor → Submissions → Export as CSV) into a Formie form, so they show under
 * Formie → Submissions with the new ones. Used for (2026-10-21):
 *   contactUs     ~/Downloads/live-site-contact-us-submission.csv   642, 2022-08 to 2026-10
 *   requestQuote  ~/Downloads/request-a-quote-submission.csv         157, 2022-08 to 2026-09
 *   (2026-10-22, the full export ~/Downloads/arrow-system-all-form-submission/: re-run for these three, +1 quote;
 *    becomeADealer  "Dealer (802cbb4)" 91, 2022-09 to 2026-09; freeSampleRequest "Free Sample Request (38584494)" 3;
 *    careers "New Form (a6a1356)" 60, its CVs downloaded from live)
 * Each CSV column goes into the form field with the same label (the live labels: Name, Email, Number / Phone No.,
 * Company, Country, Message, How Did You Hear About Us, Product Required); User IP → the submission's IP; Created At →
 * its date, and its title (as Formie's own titles, {timestamp}). A file field's column holds live's file address: the file
 * is downloaded into the field's upload volume (e.g. Careers → Career CVs) and attached. Not kept (no place in Formie): Elementor's Submission ID,
 * User Agent, Referrer. A column without a matching field stops the script.
 * The export writes text JSON-style (\r\n, \", \/ …): decoded first, so messages keep their line breaks and quotes.
 * Created At is the WordPress site's time, UTC-4 (arrsys.com: no timezone name, offset -4); stored as that moment.
 * Nothing is sent: saving a submission directly sends no emails and runs no integrations (Formie does that only on a
 * front-end submit). Saved without validation: old entries predate today's rules (e.g. phone numbers per country).
 * Skips rows already imported (same email and date), so it can be run again. The CSV holds personal data: keep it out of
 * the project (pass its path). Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/forms/04_live_submissions.php <form handle> <csv file>
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\db\Table;
use craft\elements\Asset;
use craft\helpers\Db;
use verbb\formie\fields\FileUpload;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

const LIVE_TZ = '-04:00';
const NOT_KEPT = ['Form Name (ID)', 'Submission ID', 'Created At', 'User ID', 'User Agent', 'User IP', 'Referrer'];
[, $handle, $path] = $argv + [null, null, null];
$handle && $path || exit("usage: … 04_live_submissions.php <form handle> <csv file>\n");
is_file($path) || exit("no file $path\n");
$form = Form::find()->handle($handle)->one() ?? exit("no Formie form $handle\n");

// decode the JSON-style escapes; \" and \\ are left for the CSV reader (escapechar)
$raw = preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($path));
$raw = preg_replace_callback('/\\\\(u[0-9a-fA-F]{4}|.)/s', function ($m) {
    return match ($m[1][0]) {
        'r' => "\r", 'n' => "\n", 't' => "\t", '/' => '/',
        'u' => mb_convert_encoding(pack('H*', substr($m[1], 1)), 'UTF-8', 'UTF-16BE'),
        default => $m[0],
    };
}, $raw);
$fh = fopen('php://memory', 'r+');
fwrite($fh, $raw);
rewind($fh);
$head = array_map('trim', fgetcsv($fh, null, ',', '"', '\\'));   // a heading may end in a space (Dealer: "Existing Business History ")
$rows = [];
while (($r = fgetcsv($fh, null, ',', '"', '\\')) !== false) {
    if ($r === [null]) continue;
    count($r) === count($head) || exit('row ' . (count($rows) + 2) . ': ' . count($r) . ' columns, expected ' . count($head) . "\n");
    $rows[] = array_combine($head, array_map(fn($v) => trim(str_replace(["\r\n", "\r"], "\n", stripslashes($v))), $r));
}
say(count($rows) . " rows in the CSV → {$form->title}");

// CSV column → form field, by label
$byLabel = $files = [];
foreach ($form->getFormLayout()->getCustomFields() as $f) {
    $byLabel[$f->label] = $f->handle;
    if ($f instanceof FileUpload) $files[$f->handle] = Craft::$app->getVolumes()->getVolumeByUid(explode(':', (string)$f->uploadLocationSource)[1] ?? '') ?? exit("{$f->label}: no upload volume\n");
}
/** live's file → an asset in the field's volume */
$download = function (string $url, craft\models\Volume $volume): int {
    $bytes = @file_get_contents($url, false, stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0 (Arrow Systems staging migration)\r\n", 'timeout' => 90]]))
        ?: exit("could not download $url\n");
    $name = basename((string)parse_url($url, PHP_URL_PATH));
    $tmp = Craft::$app->getPath()->getTempPath() . '/' . uniqid('upload') . '-' . $name;
    file_put_contents($tmp, $bytes);
    $asset = new Asset(['tempFilePath' => $tmp, 'filename' => $name, 'volumeId' => $volume->id,
        'newFolderId' => Craft::$app->getAssets()->getRootFolderByVolumeId($volume->id)->id, 'avoidFilenameConflicts' => true]);
    $asset->setScenario(Asset::SCENARIO_CREATE);
    Craft::$app->getElements()->saveElement($asset) || fail("file $url", $asset);
    return $asset->id;
};
$map = [];
foreach (array_diff($head, NOT_KEPT) as $col) $map[$col] = $byLabel[$col] ?? exit("column “{$col}” has no field with that label on {$form->title}\n");
$emailField = $byLabel['Email'] ?? exit("no Email field on {$form->title}\n");

// already imported: email + date (UTC)
$utc = new DateTimeZone('UTC');
$keys = function () use ($form, $emailField, $utc) {
    $k = [];
    foreach (Submission::find()->formId($form->id)->status(null)->all() as $s) {   // dates come back in the site's timezone
        $key = strtolower((string)$s->getFieldValue($emailField)) . '|' . (clone $s->dateCreated)->setTimezone($utc)->format('Y-m-d H:i:s');
        $k[$key] = ($k[$key] ?? 0) + 1;
    }
    return $k;
};
$seen = $keys();
$rowKey = fn(array $r) => strtolower($r['Email']) . '|' . (new DateTime($r['Created At'], new DateTimeZone(LIVE_TZ)))->setTimezone($utc)->format('Y-m-d H:i:s');

$added = $skipped = 0;
foreach ($rows as $r) {
    $key = $rowKey($r);
    if (isset($seen[$key])) { $skipped++; continue; }
    $s = new Submission();
    $s->setForm($form);
    $values = [];
    foreach ($map as $col => $field) $values[$field] = isset($files[$field]) ? ($r[$col] ? [$download($r[$col], $files[$field])] : []) : $r[$col];
    $s->setFieldValues($values);
    $s->ipAddress = $r['User IP'] ?: null;
    $s->dateCreated = $s->dateUpdated = (new DateTime($r['Created At'], new DateTimeZone(LIVE_TZ)))->setTimezone($utc);
    Craft::$app->getElements()->saveElement($s, false) || fail('submission ' . $r['Submission ID'], $s);
    // title = the date, as Formie's own ({timestamp}); a directly saved submission gets "New Submission"
    Db::update(Table::ELEMENTS_SITES, ['title' => $r['Created At']], ['elementId' => $s->id, 'siteId' => $s->siteId]);
    $seen[$key] = 1;
    $added++;
}
say("  + $added submissions" . ($skipped ? ", $skipped already there" : ''));

// self-check: every row is in Formie exactly once
$now = $keys();
$missing = array_filter($rows, fn($r) => !isset($now[$rowKey($r)]));
$doubled = array_filter($rows, fn($r) => ($now[$rowKey($r)] ?? 0) > 1);
!$missing && !$doubled || exit(count($missing) . ' rows missing, ' . count($doubled) . " rows twice\n");
say('  = all ' . count($rows) . " rows in {$form->title}, once each (" . count($now) . ' submissions in the form)');
