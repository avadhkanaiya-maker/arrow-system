<?php
/**
 * Homepage hero as the revised design (Arrowsystem/index.html + css/home.css, 2026-10-23):
 *   - each slide's caption is one slim line: Tag Name · Product Name · "Explore product" link (no buttons)
 *   - a fixed intro row under the slider (it doesn't change with the slides): the page's H1, a description, two buttons
 * Fields (existing ones, no new fields):
 *   - Hero Slide: Category Label → "Tag Name", Primary Button → "Explore Product Link" (its Label = the link text),
 *     Secondary Button taken off the slide (the caption has no buttons now)
 *   - Hero Slider block: + "Intro under the slider": Heading (the H1), Description, First Button, Second Button
 * Content (the design's text) goes into the Hero Slider block only when its Heading is empty; stops when an open draft
 * of the homepage has its own copy of the Hero Slider block (applying that draft would put the old block back).
 * Template: _blocks/home/hero-slider.twig; styles: site.css. Idempotent. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/homepage/02_hero_intro.php
 * Steps 2–3 (the intro on the Hero Slider block) are superseded by 03_hero_slide_intro.php (intro per slide): don't run
 * this again after 03, it would put the block's intro fields back.
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\db\Query;
use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\Heading;

$entries = Craft::$app->getEntries();
$fields = Craft::$app->getFields();

// 1. Hero Slide: labels, Secondary Button off
$slide = $entries->getEntryTypeByHandle('heroSlide');
$layout = $slide->getFieldLayout();
$changed = false;
foreach ($layout->getTabs() as $tab) {
    $els = [];
    foreach ($tab->getElements() as $el) {
        $h = $el instanceof CustomField ? $el->getField()->handle : null;
        if ($h === 'secondaryCta') { $changed = true; continue; }
        if ($h === 'categoryLabel' && $el->label !== 'Tag Name') {
            [$el->label, $el->instructions, $changed] = ['Tag Name', 'Small label before the product name, e.g. “UV flatbed printer”.', true];
        }
        if ($h === 'primaryCta' && $el->label !== 'Explore Product Link') {
            [$el->label, $el->instructions, $changed] = ['Explore Product Link',
                'Where the slide links (the caption link and the whole banner). Its Label is the link text, e.g. “Explore product”.', true];
        }
        $els[] = $el;
    }
    $tab->setElements($els);
}
if ($changed) {
    $slide->setFieldLayout($layout);
    $entries->saveEntryType($slide) || fail('entry type heroSlide', $slide);
    say('  ~ Hero Slide: Tag Name, Explore Product Link; Secondary Button off');
}

// 2. Hero Slider block: the intro fields
$slider = $entries->getEntryTypeByHandle('heroSlider');
if (!$slider->getFieldLayout()->getFieldByHandle('sectionHeading')) {
    $layout = $slider->getFieldLayout();
    $tab = $layout->getTabs()[0];
    $field = function (string $handle, string $label, string $instructions) use ($fields): CustomField {
        $el = new CustomField($fields->getFieldByHandle($handle));
        [$el->label, $el->instructions] = [$label, $instructions];
        return $el;
    };
    $tab->setElements(array_merge($tab->getElements(), [
        new Heading(['heading' => 'Intro under the slider']),
        $field('sectionHeading', 'Heading', 'The homepage’s main heading (its H1), in the row under the slider; it stays when the slides change. Empty = no row.'),
        $field('sectionLead', 'Description', 'One or two lines under the heading.'),
        $field('primaryCta', 'First Button', 'Filled button with an arrow, e.g. Book a Demo.'),
        $field('secondaryCta', 'Second Button', 'Outline button, e.g. Try a Free Sample.'),
    ]));
    $slider->setFieldLayout($layout);
    $entries->saveEntryType($slider) || fail('entry type heroSlider', $slider);
    say('  + Hero Slider: Intro under the slider (Heading, Description, First Button, Second Button)');
}
saveProjectConfig();

// 3. The design's intro text, into the homepage's Hero Slider block
$home = Entry::find()->section('homepage')->one() ?? exit("no homepage\n");
$block = Entry::find()->ownerId($home->id)->type('heroSlider')->status(null)->one() ?? exit("no Hero Slider block on the homepage\n");
if (!trim((string)$block->sectionHeading)) {
    foreach ((new Query())->select('e.id')->from(['d' => '{{%drafts}}'])->innerJoin(['e' => '{{%elements}}'], '[[e.draftId]] = [[d.id]]')
        ->where(['d.canonicalId' => $home->id])->column() as $draftId) {
        !Entry::find()->ownerId($draftId)->type('heroSlider')->status(null)->id(['not', $block->id])->exists()
            || exit("the homepage's open draft has its own copy of the Hero Slider block: apply or delete the draft, then run again\n");
    }
    $sample = Entry::find()->section('pages')->uri('try-a-free-sample')->one() ?? exit("no /try-a-free-sample/ page\n");
    $block->setFieldValues([
        'sectionHeading' => 'Digital Printing and Finishing Equipment for Labels, Packaging and Industrial Graphics',
        'sectionLead' => 'ArrowJet digital presses, label finishers and supplies for converters, brand owners and manufacturers.',
        // the design's interim: no demo booking page yet, an email to sales
        'primaryCta' => ['type' => 'email', 'value' => 'mailto:sales@arrsys.com?subject=Demo%20request', 'label' => 'Book a Demo'],
        'secondaryCta' => ['type' => 'entry', 'value' => $sample->id, 'label' => 'Try a Free Sample'],
    ]);
    Craft::$app->getElements()->saveElement($block) || fail('Hero Slider block', $block);
    say('  ~ homepage Hero Slider: intro text, Book a Demo, Try a Free Sample');
}

// self-check: one H1 (the intro), slim captions, no slide buttons
$html = @file_get_contents('http://localhost:8081/') ?: exit("the homepage does not load\n");
substr_count($html, '<h1') === 1 && str_contains($html, '<h1 class="hero-intro__title">') || exit("homepage: the intro H1 is missing or not the only H1\n");
substr_count($html, 'class="hero-caption__line"') === (int)Entry::find()->id($block->id)->status(null)->one()->slides->count() || exit("homepage: a slide without its caption line\n");
!str_contains($html, 'hero-caption__actions') || exit("homepage: slide buttons still shown\n");
say('  = homepage: intro row with the only H1, one caption line per slide');
