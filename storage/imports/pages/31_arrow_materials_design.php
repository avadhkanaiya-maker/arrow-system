<?php
/**
 * Pages: /arrow-materials/ rebuilt to its design (demo.theglobalwebdev.com/Arrowsystem/arrow-materials/), from
 * pages/data/arrow-materials.json (the design's text, read from its index.html 2026-10-28). General blocks only:
 *   Page Header  small heading, H1, the 2 intro paragraphs, the design's photo beside the text; buttons
 *                "Material Specifications" → #specifications, "Talk to a Specialist" → 1-888-697-2749
 *   Tabs         "Foils, Tags and [Security Materials]" (grey background): 4 Cards tabs, one card each
 *   Tabs         "Arrow Material [Specifications]", Anchor ID specifications: Film Label Stocks (5), Paper Label Stocks (6),
 *                Overlaminates (11); each card's Link is its spec sheet PDF (Documents, imported by 28_…)
 *   CTA Band     "Need continuous or pre-cut materials?": Call 1-888-697-2749 · Request a Quote
 *   Card Grid    Link cards "More from [Arrow]": Arrow Material (the live URL until that page exists here), Thermal
 *                Lamination Film, Get a Free Sample
 *   Newsletter   last, as every page
 * Decisions 2026-10-28 (user): the design's names and texts (they match the PDFs; live has three names shifted against
 * them); live's 22 "ARR-… Technical Information" tables are left out, as in the design (the data is in each spec sheet).
 * Replaces the page's blocks (the old ones stay in its revisions). Skips the page once it is on the design (it has a Cards
 * tab); --update rebuilds it. Stops under an open draft. Back up first.
 * Run from the project root, after 28_arrow_materials_used_equipment.php and 30_cards_tab.php:
 *   /opt/lampp/bin/php storage/imports/pages/31_arrow_materials_design.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';
require __DIR__ . '/_helpers.php';

use craft\db\Query;
use craft\elements\Asset;
use craft\elements\Entry;

$data = json_decode(@file_get_contents(__DIR__ . '/data/arrow-materials.json') ?: 'null', true) ?? exit("no pages/data/arrow-materials.json\n");

$para = fn(string $text) => '<p>' . htmlspecialchars($text) . '</p>';
$pdf = fn(string $file) => Asset::find()->volume('documents')->filename($file)->one()?->id
    ?? exit("no $file in Documents: run 28_arrow_materials_used_equipment.php first\n");

$page = Entry::find()->section('pages')->slug('arrow-materials')->status(null)->one() ?? exit("no /arrow-materials/ page: run 28_arrow_materials_used_equipment.php first\n");
Craft::$app->getEntries()->getEntryTypeByHandle('cardsTab') ?? exit("no Cards Tab: run 30_cards_tab.php first\n");
$pb = Craft::$app->getFields()->getFieldByHandle('pageBuilder')->id;
$onDesign = Entry::find()->ownerId($page->id)->fieldId($pb)->type('tabsBlock')->status(null)->collect()
    ->contains(fn($b) => $b->getFieldValue('productTabs')->type('cardsTab')->status(null)->exists());
if ($onDesign && !in_array('--update', $argv, true)) { say('  = /arrow-materials/ is on the design already (--update rebuilds it)'); exit(0); }
(new Query())->from('{{%drafts}}')->where(['canonicalId' => $page->id])->exists() && exit("/arrow-materials/ has an open draft: apply or delete it, then run again\n");

// ---- the blocks, top to bottom
$h = $data['header'];
$blocks = [['industryHeader', ['eyebrow' => $h['eyebrow'], 'sectionHeading' => $h['heading'], 'sectionLead' => implode("\n\n", $h['lead']),
    'mediaImage' => [pageImage($h['image']['url'], $h['image']['alt'])],
    'primaryCta' => designLink($h['buttons'][0]['href'], $h['buttons'][0]['label']),
    'secondaryCta' => designLink($h['buttons'][1]['href'], $h['buttons'][1]['label'])]]];
$cardCount = 0;
foreach ($data['tabSets'] as $set) {
    $tabs = [];
    foreach ($set['tabs'] as $tab) {
        $cards = array_map(fn($c) => ['card', ['headingText' => $c['name'], 'richText' => $para($c['text']),
            'ctaLink' => $c['file'] ? ['type' => 'asset', 'value' => $pdf($c['file']), 'label' => $c['label']] : null]], $tab['cards']);
        $cardCount += count($cards);
        $tabs[] = ['cardsTab', ['headingText' => $tab['title'], 'cardItems' => nested($cards, 'c')]];
    }
    $blocks[] = ['tabsBlock', ['eyebrow' => $set['eyebrow'], 'sectionHeading' => $set['heading'], 'sectionLead' => $set['intro'],
        'anchorId' => $set['anchor'], 'sectionBackground' => $set['background'], 'productTabs' => nested($tabs, 't')]];
}
$cta = $data['cta'];
$blocks[] = ['ctaBand', ['sectionHeading' => $cta['heading'], 'sectionLead' => $cta['text'],
    'ctaLink' => designLink($cta['buttons'][0]['href'], $cta['buttons'][0]['label']),
    'secondaryCta' => designLink($cta['buttons'][1]['href'], $cta['buttons'][1]['label'])]];
$more = $data['more'];
$blocks[] = ['cardGrid', ['eyebrow' => $more['eyebrow'], 'sectionHeading' => $more['heading'], 'cardColumns' => '3', 'cardStyle' => 'link',
    'cardItems' => nested(array_map(fn($c) => ['card', ['headingText' => $c['title'], 'subheading' => $c['kicker'], 'richText' => $para($c['text']),
        'mediaImage' => $c['image'] ? [pageImage($c['image']['url'], $c['image']['alt'])] : null, 'menuIcon' => $c['icon'],
        'ctaLink' => designLink($c['href'], $c['more'])]], $more['cards']), 'm')]];
$blocks[] = ['newsletter', []];

$page->setFieldValue('pageBuilder', nested($blocks, 'b'));
Craft::$app->getElements()->saveElement($page) || fail('page arrow-materials', $page);

// ---- check what was saved
$saved = Entry::find()->ownerId($page->id)->fieldId($pb)->status(null)->all();
count($saved) === count($blocks) || exit('arrow-materials: ' . count($saved) . ' of ' . count($blocks) . " blocks saved\n");
$savedCards = 0;
foreach ($saved as $b) {
    if ($b->type->handle !== 'tabsBlock') continue;
    foreach ($b->getFieldValue('productTabs')->status(null)->all() as $t) $savedCards += $t->getFieldValue('cardItems')->status(null)->count();
}
$savedCards === $cardCount || exit("arrow-materials: $savedCards of $cardCount material cards saved\n");
say('  ~ /arrow-materials/: Page Header, Tabs ×' . count($data['tabSets']) . " ($cardCount material cards), CTA Band, Card Grid (" . count($more['cards']) . ' link cards), Newsletter');
