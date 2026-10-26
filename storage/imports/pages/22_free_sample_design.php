<?php
/**
 * /try-a-free-sample/ as the design (Arrowsystem/try-a-free-sample, review 2026-10-22: "take reference from this page").
 * Replaces the page's blocks from 21_free_sample_and_careers.php, general blocks only:
 *   Page Header  Header Style "Small illustration" (new option): compact, the design's sample pack (an SVG, taken from
 *                the design page) small at the right
 *   Columns 2-1  grey background · card: Form block "Your sample request" (the form's steps, cards and privacy note are
 *                the Formie form + craft.css, forms/12_free_sample_and_careers.php) · plain: an HTML block with the
 *                design's side column (Talk to a Specialist panel with the Site Settings phones / email / address, and
 *                Looking for something else?: Request a Quote, Order Supplies, Request Support)
 * Stops when the page has an open draft (apply or delete it first). Idempotent. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/pages/22_free_sample_design.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\db\Query;
use craft\elements\Asset;
use craft\elements\Entry;
use verbb\formie\elements\Form;

const DESIGN = '/home/avadh/server/theglobalwebdev/demo/public_html/Arrowsystem/try-a-free-sample/index.html';

$page = Entry::find()->section('pages')->slug('try-a-free-sample')->status(null)->one() ?? exit("no page try-a-free-sample: run 21_free_sample_and_careers.php\n");
!(new Query())->from('{{%drafts}}')->where(['canonicalId' => $page->id])->exists() || exit("try-a-free-sample has an open draft: apply or delete it, then run again\n");
$form = Form::find()->handle('freeSampleRequest')->one() ?? exit("no Formie form freeSampleRequest\n");

// Header Style: + "Small illustration"
$fields = Craft::$app->getFields();
$style = $fields->getFieldByHandle('headerStyle');
if (!in_array('illustration', array_column($style->options, 'value'), true)) {
    $style->options[] = ['label' => 'Small illustration', 'value' => 'illustration', 'default' => false];
    $style->instructions = 'Full-width banner: the image on its own under the text, across the full width and shown whole (like a blog post cover). Use a wide photo, 1600 px wide or more. Small illustration: a compact header with the image small at the right, as decoration (desktop only), e.g. an SVG.';
    $fields->saveField($style) || fail('field headerStyle', $style);
    saveProjectConfig();
    say('  + Header Style: Small illustration');
}

// the design's sample-pack illustration, as an SVG asset
$volume = Craft::$app->getVolumes()->getVolumeByHandle('images');
$folder = Craft::$app->getAssets()->ensureFolderByFullPathAndVolume('pages', $volume, false);
$art = Asset::find()->folderId($folder->id)->filename('free-sample-pack.svg')->one();
if (!$art) {
    preg_match('#<svg class="sample-pack" (viewBox="[^"]+")[^>]*>(.*?)</svg>#s', file_get_contents(DESIGN), $m) || exit("no sample-pack SVG in the design page\n");
    $tmp = Craft::$app->getPath()->getTempPath() . '/' . uniqid('art') . '-free-sample-pack.svg';
    file_put_contents($tmp, "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"240\" height=\"150\" {$m[1]}>{$m[2]}</svg>");
    $art = new Asset(['tempFilePath' => $tmp, 'filename' => 'free-sample-pack.svg', 'volumeId' => $volume->id, 'newFolderId' => $folder->id]);
    $art->alt = 'A free sample pack: a label roll, a film swatch and a pouch';
    $art->setScenario(Asset::SCENARIO_CREATE);
    Craft::$app->getElements()->saveElement($art) || fail('illustration', $art);
    say('  + asset pages/free-sample-pack.svg');
}

// the side column: the design's markup, contact details from Site Settings
$s = Craft::$app->getGlobals()->getSetByHandle('siteSettings');
$tel = fn(string $p) => '+' . ltrim(preg_replace('/\D/', '', str_starts_with($p, '+') ? $p : '1' . $p), '+');
$icon = fn(string $name, string $class = '') => "<svg class=\"icon$class\" aria-hidden=\"true\"><use href=\"#i-$name\"/></svg>";
$url = fn(string $slug, string $live) => Entry::find()->slug($slug)->status(null)->one()?->url ?? $live;
$link = fn(string $href, string $iconName, string $title, string $line) => "<li><a class=\"side-links__link\" href=\"$href\"><span class=\"side-links__icon\" aria-hidden=\"true\">{$icon($iconName)}</span><span><strong>$title</strong><span>$line</span></span>{$icon('arrow-right', ' side-links__arrow')}</a></li>";
$aside = '<div class="side-stack">'
    . '<section class="contact-panel" aria-labelledby="call-title"><span class="dot-grid dot-grid--panel" aria-hidden="true"></span>'
    . '<p class="contact-panel__eyebrow">Prefer to talk?</p><h2 class="contact-panel__title" id="call-title">Talk to a Specialist</h2>'
    . '<p>Tell us your application, materials and run lengths.</p>'
    . "<a class=\"btn btn--light btn--block\" href=\"tel:{$tel($s->tollFreePhone)}\">{$icon('phone')} {$s->tollFreePhone}</a>"
    . '<ul class="contact-panel__list">'
    . "<li>{$icon('phone')}<span>Direct <a href=\"tel:{$tel($s->directPhone)}\">{$s->directPhone}</a></span></li>"
    . "<li>{$icon('mail')}<a href=\"mailto:{$s->salesEmail}\">{$s->salesEmail}</a></li>"
    . "<li>{$icon('map-pin')}<span>{$s->address}</span></li></ul></section>"
    . '<section class="side-card" aria-labelledby="other-title"><h2 class="side-card__title" id="other-title">Looking for something else?</h2><ul class="side-links">'
    . $link($url('request-quote', 'https://arrsys.com/request-quote/'), 'file', 'Request a Quote', 'Pricing for equipment and materials')
    . $link('https://arrsys.com/arrow-materials/', 'package', 'Order Supplies', 'Label materials and consumables')   // as the menus' Order Supplies
    . $link($url('support', 'https://arrsys.com/support/'), 'headset', 'Request Support', 'Help with equipment you already own')
    . '</ul></section></div>';

$page->setFieldValue('pageBuilder', ['sortOrder' => ['b1', 'b2'], 'entries' => [
    'b1' => ['type' => 'industryHeader', 'fields' => ['eyebrow' => 'Free sample', 'sectionHeading' => 'Request a Free [Sample]',
        'sectionLead' => 'Tell us what you would like to try. Our team can follow up to confirm the right sample for your project.',
        'mediaImage' => [$art->id], 'headerStyle' => 'illustration']],
    'b2' => ['type' => 'columnsBlock', 'fields' => ['columnsLayout' => '2-1', 'leftStyle' => 'card', 'rightStyle' => 'plain', 'columnsAlign' => 'top',
        'sectionBackground' => 'surface', 'spacingTop' => 'small',
        'leftColumn' => ['sortOrder' => ['n1'], 'entries' => ['n1' => ['type' => 'formBlock', 'fields' => [
            'formieForm' => [$form->id], 'anchorId' => 'sample-form', 'sectionHeading' => 'Your sample request']]]],
        'rightColumn' => ['sortOrder' => ['n1'], 'entries' => ['n1' => ['type' => 'html', 'fields' => ['htmlCode' => $aside]]]],
    ]],
]]);
Craft::$app->getElements()->saveElement($page) || fail('page try-a-free-sample', $page);
say('  ~ /try-a-free-sample/: Page Header (small illustration), Columns 2-1: form card + side column (HTML)');

// self-check: the page shows the design's parts
$html = @file_get_contents('http://localhost:8081/try-a-free-sample/') ?: exit("/try-a-free-sample/ does not load\n");
foreach (['page-hero__art', 'formie-block__req', 'fui-heading', 'side-stack', 'side-links__link', 'section--surface'] as $part) {
    str_contains($html, $part) || exit("/try-a-free-sample/: no $part\n");
}
say('  = header art, form heading + steps, side column, grey band: all on the page');
