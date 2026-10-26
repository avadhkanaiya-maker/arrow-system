<?php
/**
 * Live's blog form submissions into the Formie form Request Quote From Blog (08_blog_quote_form.php).
 * Source: Elementor → Submissions → export of every form (2026-10-22): one CSV per post's copy, "Request Quote From Blog
 * (<form id>)", 73 files, 306 submissions (2022-08 to 2026-10). Columns: Name, Email, Contact No., Message + the export's
 *   Referrer → Blog Post Page (as recorded) and Blog Post (the staging entry at that address: post, industry, page)
 *   no Referrer (176 rows) → the post whose live content holds that form copy (blog/data/posts-*.json, the live posts
 *              as imported): Blog Post = its title, Blog Post Page = its live address
 *   Submission ID → Live Submission ID · Form Name (ID) → Live Form · User Agent → User Agent · User IP → the
 *   submission's IP · Created At → its date (live's clock, UTC-4) and title
 * A form copy found in no post and no Referrer → Blog Post empty (Live Form still says which copy). Decoding, no emails,
 * no validation and skipping by Live Submission ID as 07_product_request_quote_submissions.php. The CSVs hold personal
 * data: keep them out of the project (pass the folder). Back up first.
 * Run from the project root:
 *   /opt/lampp/bin/php storage/imports/forms/09_blog_quote_submissions.php ~/Downloads/arrow-system-all-form-submission
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\db\Table;
use craft\elements\Entry;
use craft\helpers\Db;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

const LIVE_TZ = '-04:00';
$dir = rtrim($argv[1] ?? exit("usage: … 09_blog_quote_submissions.php <export folder>\n"), '/');
$files = glob("$dir/*Request Quote From Blog (*.csv") ?: exit("no “Request Quote From Blog” CSVs in $dir\n");
$form = Form::find()->handle('requestQuoteFromBlog')->one() ?? exit("no Formie form requestQuoteFromBlog: run 08_blog_quote_form.php\n");

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

// live form copy → live post slug, from the live posts' content as imported
$copyToSlug = [];
foreach (glob(dirname(__DIR__) . '/blog/data/posts-*.json') as $f) {
    foreach (json_decode(file_get_contents($f), true) as $post) {
        if (preg_match_all('#name="Request Quote From Blog".*?name="form_id" value="([0-9a-f]+)"#s', $post['content']['rendered'] ?? '', $m)) {
            foreach ($m[1] as $id) $copyToSlug[$id] = $post['slug'];
        }
    }
}
// staging entries (posts, industries, pages) by address
$titleFor = function (string $uri): string {
    $uri = trim($uri, '/');
    return $uri === '' ? '' : (string)(Entry::find()->uri($uri)->status(null)->one()?->title ?? '');
};

$seen = [];
foreach (Submission::find()->formId($form->id)->status(null)->all() as $s) {
    if ($id = trim((string)$s->getFieldValue('liveSubmissionId'))) $seen[$id] = true;
}

$utc = new DateTimeZone('UTC');
$added = $skipped = $total = $byCopy = $unknown = 0;
foreach ($files as $file) {
    $rows = $read($file);
    $head = array_map('trim', array_shift($rows));
    foreach (['Name', 'Email', 'Contact No.', 'Message', 'Form Name (ID)', 'Submission ID', 'Created At', 'Referrer'] as $c) in_array($c, $head, true) || exit(basename($file) . ": no column $c\n");
    $col = fn(array $r, string $name) => $r[array_search($name, $head, true)] ?? '';
    foreach ($rows as $n => $r) {
        count($r) === count($head) || exit(basename($file) . ' row ' . ($n + 2) . ': ' . count($r) . ' columns, expected ' . count($head) . "\n");
        $total++;
        $liveId = $col($r, 'Submission ID');
        if (isset($seen[$liveId])) { $skipped++; continue; }
        $page = $col($r, 'Referrer');
        $path = $page ? (string)parse_url($page, PHP_URL_PATH) : '';
        $post = $titleFor($path);
        if (!$page && preg_match('#\(([0-9a-f]+)\)#', $col($r, 'Form Name (ID)'), $m) && isset($copyToSlug[$m[1]])) {
            $page = 'https://arrsys.com/' . $copyToSlug[$m[1]] . '/';
            $post = $titleFor($copyToSlug[$m[1]]) ?: ucwords(str_replace('-', ' ', $copyToSlug[$m[1]]));
            $byCopy++;
        }
        if (!$post && $path && $path !== '/') $post = ucwords(str_replace('-', ' ', trim($path, '/')));
        if (!$post) $unknown++;

        $s = new Submission();
        $s->setForm($form);
        $s->setFieldValues([
            'yourName' => $col($r, 'Name'), 'emailAddress' => $col($r, 'Email'), 'phoneNumber' => $col($r, 'Contact No.'), 'message' => $col($r, 'Message'),
            'blogPost' => $post, 'blogPostPage' => $page,
            'liveSubmissionId' => $liveId, 'liveForm' => $col($r, 'Form Name (ID)'), 'userAgent' => $col($r, 'User Agent'),
        ]);
        $s->ipAddress = $col($r, 'User IP') ?: null;
        $s->dateCreated = $s->dateUpdated = (new DateTime($col($r, 'Created At'), new DateTimeZone(LIVE_TZ)))->setTimezone($utc);
        Craft::$app->getElements()->saveElement($s, false) || fail("submission $liveId", $s);
        // title = the date, as Formie's own ({timestamp}); a directly saved submission gets "New Submission"
        Db::update(Table::ELEMENTS_SITES, ['title' => $col($r, 'Created At')], ['elementId' => $s->id, 'siteId' => $s->siteId]);
        $seen[$liveId] = true;
        $added++;
    }
}
say(count($files) . " files, $total rows: + $added submissions" . ($skipped ? ", $skipped already there" : '')
    . ($added ? " ($byCopy without a page found by their form copy, $unknown with no post)" : ''));

// self-check: every row is in Formie once
$ids = [];
foreach (Submission::find()->formId($form->id)->status(null)->all() as $s) {
    if ($id = trim((string)$s->getFieldValue('liveSubmissionId'))) $ids[$id] = ($ids[$id] ?? 0) + 1;
}
count($ids) === $total && max($ids) === 1 || exit(count($ids) . " imported, expected $total (or a duplicate)\n");
say("  = all $total live submissions in Request Quote From Blog, once each");
