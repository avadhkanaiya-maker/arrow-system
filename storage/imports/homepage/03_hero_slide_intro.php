<?php
/**
 * Intro under the slider per slide (review 2026-10-26: "support of Intro under the slider for each slide"). The row
 * (heading, description, two buttons) now belongs to each slide and changes with it; Phase 83 had one fixed row on the
 * Hero Slider block.
 * Fields (existing ones, no new fields):
 *   - Hero Slide: + "Intro under the slider": Intro Heading, Intro Description, Intro First Button (ctaLink), Intro
 *     Second Button (secondaryCta)
 *   - Hero Slider block: its intro fields taken off
 * Content: the block's intro is copied to every slide that has no intro yet (the admin then changes it per slide).
 * Stops when an open draft of the homepage has its own copy of the Hero Slider block.
 * Template: _blocks/home/hero-slider.twig (the first slide with an intro has the H1, the others an H2); styles: site.css.
 * Idempotent. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/homepage/03_hero_slide_intro.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\db\Query;
use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\Heading;

$entries = Craft::$app->getEntries();
$fields = Craft::$app->getFields();
$elements = Craft::$app->getElements();
const INTRO = ['sectionHeading', 'sectionLead', 'primaryCta', 'secondaryCta'];   // the block's intro fields (Phase 83)

$home = Entry::find()->section('homepage')->one() ?? exit("no homepage\n");
$block = Entry::find()->ownerId($home->id)->type('heroSlider')->status(null)->one() ?? exit("no Hero Slider block on the homepage\n");
foreach ((new Query())->select('e.id')->from(['d' => '{{%drafts}}'])->innerJoin(['e' => '{{%elements}}'], '[[e.draftId]] = [[d.id]]')
    ->where(['d.canonicalId' => $home->id])->column() as $draftId) {
    !Entry::find()->ownerId($draftId)->type('heroSlider')->status(null)->id(['not', $block->id])->exists()
        || exit("the homepage's open draft has its own copy of the Hero Slider block: apply or delete the draft, then run again\n");
}
$blockIntro = $block->getFieldLayout()->getFieldByHandle('sectionHeading')
    ? array_combine(INTRO, array_map(fn($h) => $block->getFieldValue($h), INTRO)) : null;   // read before the fields come off

// 1. Hero Slide: the intro fields, before Display Time
$slideType = $entries->getEntryTypeByHandle('heroSlide');
if (!$slideType->getFieldLayout()->getFieldByHandle('sectionHeading')) {
    $layout = $slideType->getFieldLayout();
    $tab = $layout->getTabs()[0];
    $field = function (string $handle, string $label, string $instructions) use ($fields): CustomField {
        $el = new CustomField($fields->getFieldByHandle($handle));
        [$el->label, $el->instructions] = [$label, $instructions];
        return $el;
    };
    $intro = [
        new Heading(['heading' => 'Intro under the slider']),
        $field('sectionHeading', 'Intro Heading', 'Shown under the banner with this slide. The first slide’s is the homepage’s main heading (H1). Empty = no intro row on this slide.'),
        $field('sectionLead', 'Intro Description', 'One or two lines under the heading.'),
        $field('ctaLink', 'Intro First Button', 'Filled button with an arrow, e.g. Book a Demo.'),
        $field('secondaryCta', 'Intro Second Button', 'Outline button, e.g. Try a Free Sample.'),
    ];
    $els = [];
    foreach ($tab->getElements() as $el) {
        if ($el instanceof CustomField && $el->getField()->handle === 'slideDuration') array_push($els, ...$intro);
        $els[] = $el;
    }
    count($els) > count($tab->getElements()) || array_push($els, ...$intro);   // no Display Time: at the end
    $tab->setElements($els);
    $slideType->setFieldLayout($layout);
    $entries->saveEntryType($slideType) || fail('entry type heroSlide', $slideType);
    say('  + Hero Slide: Intro under the slider (Intro Heading, Description, First Button, Second Button)');
}

// 2. The block's intro → every slide without one
if ($blockIntro && trim((string)$blockIntro['sectionHeading'])) {
    $link = fn(string $h) => $blockIntro[$h] ? $fields->getFieldByHandle($h)->serializeValue($blockIntro[$h], $block) : null;   // as stored: an entry link stays an entry link
    foreach (Entry::find()->ownerId($block->id)->field('slides')->status(null)->all() as $slide) {
        if (trim((string)$slide->sectionHeading)) continue;
        $slide->setFieldValues(['sectionHeading' => $blockIntro['sectionHeading'], 'sectionLead' => $blockIntro['sectionLead'],
            'ctaLink' => $link('primaryCta'), 'secondaryCta' => $link('secondaryCta')]);
        $elements->saveElement($slide) || fail("slide {$slide->id}", $slide);
        say("  ~ slide “{$slide->productName}”: intro copied from the slider");
    }
}

// 3. Hero Slider block: its intro fields off (the "Intro under the slider" heading and the 4 fields)
$sliderType = $entries->getEntryTypeByHandle('heroSlider');
if ($sliderType->getFieldLayout()->getFieldByHandle('sectionHeading')) {
    $layout = $sliderType->getFieldLayout();
    foreach ($layout->getTabs() as $tab) {
        $tab->setElements(array_values(array_filter($tab->getElements(), fn($el) => !($el instanceof Heading && $el->heading === 'Intro under the slider')
            && !($el instanceof CustomField && in_array($el->getField()->handle, INTRO, true)))));
    }
    $sliderType->setFieldLayout($layout);
    $entries->saveEntryType($sliderType) || fail('entry type heroSlider', $sliderType);
    say('  - Hero Slider block: intro fields off (now per slide)');
}
saveProjectConfig();

// self-check: an intro inside each slide that has one, one H1 (the first of them), no row outside the slides
$withIntro = count(array_filter(Entry::find()->ownerId($block->id)->field('slides')->all(), fn($s) => trim((string)$s->sectionHeading)));
$html = @file_get_contents('http://localhost:8081/') ?: exit("the homepage does not load\n");
($n = substr_count($html, 'class="hero-intro__title"')) === $withIntro || exit("homepage: $n slide intros, expected $withIntro\n");
substr_count($html, '<h1') === ($withIntro ? 1 : 0) && (!$withIntro || str_contains($html, '<h1 class="hero-intro__title">')) || exit("homepage: not one H1 in the slide intros\n");
!preg_match('#</section>\s*<div class="hero-intro"#', $html) || exit("homepage: an intro row still outside the slides\n");
say("  = homepage: $withIntro slides with their own intro, one H1");
