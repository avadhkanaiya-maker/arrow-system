<?php
/**
 * Exhibitions, Phase 1: fields, entry types, sections and the homepage block type (EXHIBITIONS_MIGRATION_PLAN.md §3).
 *
 *   Exhibitions (exhibitions)                 Structure, 1 level   /{slug}/ when "Has a Page" is on   exhibitions/_entry
 *       one entry per show for its whole life: dates, location, booth, logo (upcoming) → photos + page (past)
 *       Structure = manual order (past shows only have a year, D3), starting in the live listing order
 *   Exhibitions Page (exhibitionsPage)        Single   /exhibitions/            exhibitions/_index
 *   Upcoming Exhibitions Page (…)             Single   /upcoming-exhibitions/   exhibitions/_upcoming   (D1)
 *   Homepage Sections → "Exhibitions" block (exhibitionHighlights): section G, up to 3 picked shows (D6)
 *
 * Reuses the blog scripts' helpers (../blog/_bootstrap.php). Idempotent: existing fields / entry types / sections are
 * left as they are. Back up first. Run from the project root:
 *   /opt/lampp/bin/php storage/imports/exhibitions/01_structure.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\fieldlayoutelements\CustomField;
use craft\fields\Assets;
use craft\fields\Date;
use craft\fields\Dropdown;
use craft\fields\Entries;
use craft\fields\Lightswitch;
use craft\fields\PlainText;

$fields = Craft::$app->getFields();
$entries = Craft::$app->getEntries();
$f = fn(string $handle, array $opts = []) => new CustomField($fields->getFieldByHandle($handle) ?? throw new Exception("No field $handle"), $opts);

$images = Craft::$app->getVolumes()->getVolumeByHandle('images');
$exhibitionImages = [
    'allowedKinds' => ['image'], 'restrictFiles' => true, 'viewMode' => 'large', 'previewMode' => 'full',
    'sources' => ["volume:{$images->uid}"],
    'defaultUploadLocationSource' => "volume:{$images->uid}", 'defaultUploadLocationSubpath' => 'exhibitions',
];

say('Fields');
field(Date::class, ['name' => 'Start Date', 'handle' => 'startDate', 'showDate' => true, 'showTime' => false,
    'instructions' => 'The first day of the show. Decides "upcoming" or "past", the countdown and the year group.']);
field(Date::class, ['name' => 'End Date', 'handle' => 'endDate', 'showDate' => true, 'showTime' => false,
    'instructions' => 'The last day. Leave empty for a one-day show.']);
field(Dropdown::class, ['name' => 'Show Dates As', 'handle' => 'datePrecision',
    'instructions' => '“Year only” for past shows whose exact dates aren’t published (the site then shows just the year).',
    'options' => options(['exact' => 'Exact dates (e.g. Oct 18–21, 2026)', 'year' => 'Year only (e.g. 2025)'], 'exact')]);
field(PlainText::class, ['name' => 'Booth', 'handle' => 'boothNumber', 'instructions' => 'e.g. “1335”. Empty = “To be announced”.']);
field(PlainText::class, ['name' => 'Tech Spotlight', 'handle' => 'techSpotlight', 'instructions' => 'What Arrow shows there. Empty = “To be announced”.']);
field(Assets::class, ['name' => 'Show Logo', 'handle' => 'showLogo', 'maxRelations' => 1,
    'instructions' => 'The show’s own logo, for the upcoming-show ticket and schedule.'] + $exhibitionImages);
field(Assets::class, ['name' => 'Photos', 'handle' => 'exhibitionPhotos',
    'instructions' => 'Photos from the show; drag to reorder. Set each photo’s alt text (click it → Alternative Text).'] + $exhibitionImages);
field(Lightswitch::class, ['name' => 'Has a Page', 'handle' => 'hasPage',
    'instructions' => 'On = the show gets its own page at /its-slug/ (photos, intro). Off = it is only listed (e.g. an announced show).']);

say('Exhibitions');
$exhibition = entryType('Exhibition', 'exhibition', [
    'Content' => [
        $f('startDate', ['required' => true]),
        $f('endDate'),
        $f('datePrecision'),
        $f('city', ['label' => 'Location', 'instructions' => 'e.g. “Chicago, IL, USA”. Leave empty if not known.']),
        $f('boothNumber'),
        $f('techSpotlight'),
        $f('showLogo'),
        $f('featuredImage', ['label' => 'Card Photo', 'instructions' => 'The photo on the show’s card (homepage, Exhibitions page, “More Exhibitions”). 4:3 works best. An upcoming show without one shows its Show Logo instead.']),
    ],
    'Page' => [
        $f('hasPage'),
        $f('subheading', ['label' => 'Subtitle', 'instructions' => 'e.g. “Arrow Systems, Inc. at Labelexpo Europe (Barcelona)”.']),
        $f('sectionLead', ['label' => 'Intro', 'instructions' => 'The paragraph under the title.']),
        $f('exhibitionPhotos', ['instructions' => 'The gallery. The first two photos are also the large photos at the top of the page; drag to reorder.']),
    ],
], ['icon' => 'calendar-days', 'showSlug' => true, 'titleLabel' => 'Show Name',
    'description' => 'One trade show: dates, location, booth, then its photos and page. URL: /slug/ when “Has a Page” is on.']);
$section = section('Exhibitions', 'exhibitions', 'structure', [$exhibition],
    '{% if object.hasPage %}{{ object.slug }}{% endif %}', 'exhibitions/_entry', ['maxLevels' => 1]);

say('Listing pages');
$pageType = entryType('Exhibitions Page', 'exhibitionsPage', [
    'Content' => [
        $f('eyebrow'),
        $f('sectionHeading', ['label' => 'Heading']),
        $f('sectionLead', ['label' => 'Intro']),
        $f('exhibitionPhotos', ['label' => 'Header Photos', 'instructions' => 'First = the large photo, second = the small one.']),
        $f('caption', ['label' => 'Large Photo Caption', 'instructions' => 'e.g. “Label Expo 2026, Chicago”.']),
        $f('ctaLink', ['label' => 'Contact Link', 'instructions' => 'The “Contact Us” button next to the next show (e.g. the Contact page).']),
    ],
], ['icon' => 'calendar-days', 'description' => 'The /exhibitions/ page.']);
section('Exhibitions Page', 'exhibitionsPage', 'single', [$pageType], 'exhibitions', 'exhibitions/_index');

$upcomingType = entryType('Upcoming Exhibitions Page', 'upcomingExhibitionsPage', [
    'Content' => [
        $f('eyebrow'),
        $f('sectionHeading', ['label' => 'Heading']),
        $f('sectionLead', ['label' => 'Intro']),
        $f('ctaLink', ['label' => 'Schedule a Meeting Link', 'instructions' => 'Used by the “Schedule a Meeting” buttons (e.g. the Contact page).']),
    ],
    'Bottom Band' => [
        $f('helpTitle', ['label' => 'Band Heading', 'instructions' => 'e.g. “Can’t make it to a show?”.']),
        $f('helpText', ['label' => 'Band Text']),
        $f('helpLink', ['label' => 'First Button', 'instructions' => 'e.g. a phone link labelled “Talk to a Specialist”.']),
        $f('quoteLink', ['label' => 'Second Button', 'instructions' => 'e.g. the Contact page.']),
    ],
], ['icon' => 'calendar-days', 'description' => 'The /upcoming-exhibitions/ page: the year’s show calendar and schedule.']);
section('Upcoming Exhibitions Page', 'upcomingExhibitionsPage', 'single', [$upcomingType], 'upcoming-exhibitions', 'exhibitions/_upcoming');

say('SEO tabs');
field(PlainText::class, ['name' => 'Canonical URL', 'handle' => 'seoCanonical',
    'instructions' => 'Leave empty (normal): the page’s own URL. Only for a page that should point search engines to another page, e.g. “/exhibitions/”.']);
$seo = array_map(fn($h) => $fields->getFieldByHandle($h), ['seoTitle', 'seoDescription', 'socialImage', 'seoNoIndex']);
foreach (['exhibition', 'exhibitionsPage', 'upcomingExhibitionsPage'] as $handle) addTab($entries->getEntryTypeByHandle($handle), 'SEO', $seo);
// Exhibitions only: two live shows point their canonical to /exhibitions/ (kept as live, D5)
addTab($entries->getEntryTypeByHandle('exhibition'), 'SEO', [$fields->getFieldByHandle('seoCanonical')]);

say('Homepage block: Exhibitions (section G)');
field(Entries::class, ['name' => 'Shows to Display', 'handle' => 'selectedExhibitions', 'maxRelations' => 3,
    'instructions' => 'Optional: pick up to 3 shows and drag them into order. Leave empty to show the next upcoming show first, then the latest past shows; with 1 or 2 picked, those fill the rest.',
    'sources' => ["section:{$section->uid}"], 'showSearchInput' => true]);
$block = entryType('Exhibitions', 'exhibitionHighlights', ['Content' => [
    $f('eyebrow'),
    $f('sectionHeading', ['required' => true, 'instructions' => 'Put words in [square brackets] to show them in red; type “ | ” for a line break, e.g. “Our [Innovation] | in Focus”.']),
    $f('sectionLead', ['label' => 'Intro']),
    $f('selectedExhibitions'),
    $f('ctaLink', ['label' => 'All Exhibitions Link', 'instructions' => 'e.g. “All exhibitions” → Exhibitions Page.']),
    $f('anchorId'),
]], ['icon' => 'calendar-days', 'titleLabel' => 'Name in the list',
    'description' => 'Section G: 3 exhibition cards (picked, or the next upcoming + latest past shows automatically).']);
$matrix = $fields->getFieldByHandle('homepageSections');
$types = $matrix->getEntryTypes();
if (!in_array('exhibitionHighlights', array_map(fn($t) => $t->handle, $types), true)) {
    $types[] = $block;
    $matrix->setEntryTypes($types);
    $fields->saveField($matrix) || fail('homepageSections', $matrix);
    say('  ~ homepageSections: + Exhibitions');
}

saveProjectConfig();
