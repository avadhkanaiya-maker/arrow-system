<?php
/**
 * Pages, structure (PAGES_MIGRATION_PLAN.md phase 1; content is added page by page later, nothing migrated here).
 * Like WordPress Pages: create, nest under a parent (the URL follows it), drag to reorder, drafts, preview, SEO.
 *
 *   Pages (pages)   Structure, up to 3 levels, URL {parent.uri}/{slug} (/our-company/, /answers/some-question/),
 *                   pages/_entry
 *   Page:           Title, Slug · Card: Card Photo, Card Summary (when the page is shown as a card) · Page Builder · SEO
 *   Page Builder (pageBuilder), Matrix in Blocks view, icon-grid menu + Outline (modules/builderui):
 *     Sections: the industries' section blocks, shared (D3): Page Header (was "Industry Header"; handle unchanged),
 *               Recommended Products, Content Block, Contact Form, CTA Band, Photo Gallery
 *     + the common set (Text, Lists, Media, Products & CTA, Q&A, Other: Stats Strip …): storage/imports/builder/
 *       01_common_blocks.php, run after this script.
 * Idempotent. Back up first. Run from the project root:
 *   /opt/lampp/bin/php storage/imports/pages/01_structure.php && /opt/lampp/bin/php storage/imports/builder/01_common_blocks.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\fieldlayoutelements\CustomField;
use craft\fields\Matrix;

$fields = Craft::$app->getFields();
$entries = Craft::$app->getEntries();
$f = fn(string $handle, array $opts = []) => new CustomField($fields->getFieldByHandle($handle) ?? throw new Exception("No field $handle"), $opts);
$type = fn(string $h) => $entries->getEntryTypeByHandle($h) ?? throw new Exception("No entry type $h");

// ---- the industries' header block becomes the generic "Page Header" (D3: one section block set) -------------------------
$header = $type('industryHeader');
if ($header->name !== 'Page Header') {
    $header->name = 'Page Header';
    $header->description = 'Breadcrumb, small heading, H1 ([red] words), intro, tags, image, 2 buttons. Put it first.';
    $entries->saveEntryType($header) || fail('entry type industryHeader', $header);
    say('  ~ industryHeader: named "Page Header" (industries and pages)');
}

// ---- Page entry type + Pages section --------------------------------------------------------------------------------
say('Pages');
$page = entryType('Page', 'page', [
    'Page' => [],
    'Card' => [
        $f('featuredImage', ['label' => 'Card Photo', 'instructions' => 'The photo when this page is shown as a card (related pages, lists). Optional.']),
        $f('cardTagline', ['label' => 'Card Summary', 'instructions' => 'One or two sentences for the card. Optional.']),
    ],
], ['icon' => 'file-lines', 'showSlug' => true, 'titleLabel' => 'Page Title',
    'description' => 'A page at /slug/, or /parent-slug/slug/ when it sits under another page. Moving to a live page: keep its live slug.']);
section('Pages', 'pages', 'structure', [$page], "{{ object.parent ? object.parent.uri ~ '/' }}{{ object.slug }}", 'pages/_entry', ['maxLevels' => 3]);

// ---- Page Builder: section blocks (+ the common set via builder/01_common_blocks.php) + SEO ---------------------------
$builder = $fields->getFieldByHandle('pageBuilder');
if (!$builder) {
    $builder = new Matrix(['name' => 'Page Builder', 'handle' => 'pageBuilder', 'viewMode' => Matrix::VIEW_MODE_BLOCKS,
        'createButtonLabel' => 'Add block', 'instructions' => 'The page, top to bottom. Add blocks and drag them into order. Start with a Page Header (else the page gets a plain title).']);
    $builder->setEntryTypes(array_map(fn($h) => ['uid' => $type($h)->uid, 'group' => 'Sections'],
        ['industryHeader', 'productShowcase', 'contentBlock', 'contactBlock', 'ctaBand', 'photoGallery']));
    $fields->saveField($builder) || fail('field pageBuilder', $builder);
    say('  + field pageBuilder');
}
addTab($type('page'), 'Page Builder', [$builder]);
addTab($type('page'), 'SEO', array_map(fn($h) => $fields->getFieldByHandle($h), ['seoTitle', 'seoDescription', 'socialImage', 'seoNoIndex', 'seoCanonical', 'seoRobots']));
saveProjectConfig();
