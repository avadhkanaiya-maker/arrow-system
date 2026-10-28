<?php
/**
 * Answers, structure: the live "Answers" hub (https://arrsys.com/answers/) and its child pages as their own module.
 * Design: Arrowsystem_local/answers/index.html (listing). Content: 02_content.php.
 *
 *   Answers (answers)            Structure, 1 level (drag to reorder = the listing order), URL answers/{slug},
 *                                answers/_entry (the Pages / industries template: same blocks, breadcrumb Home › Answers)
 *   Answer:                      Answer Title, Slug · Card: Card Summary, Card Icon (the listing card) · Page Builder
 *                                (the shared pageBuilder: section + common blocks) · SEO (as Pages)
 *   Answers Page (answersPage)   Single, URL answers, answers/_index: small heading, heading, intro, count line,
 *                                help band (small heading, heading, text, button; + the phone from Site Settings), SEO
 * Card Icon is the menus' icon list (menuIcon) + the design's answer icons (also added to the icon sprite).
 * Heading, Card Grid and CTA Band get an optional Small Heading (eyebrow), as live shows above each answer section; Card
 * Grid also a Section Link (live's "View Equipment" beside the heading).
 * Idempotent. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/answers/01_structure.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\fieldlayoutelements\CustomField;

$fields = Craft::$app->getFields();
$entries = Craft::$app->getEntries();
$f = fn(string $handle, array $opts = []) => new CustomField($fields->getFieldByHandle($handle) ?? throw new Exception("No field $handle"), $opts);
$seo = fn(array $handles) => array_map(fn($h) => $fields->getFieldByHandle($h), $handles);

// ---- Card Icon: the design's answer icons the icon list doesn't have yet ---------------------------------------------
const NEW_ICONS = ['alert' => 'Alert', 'award' => 'Award', 'coffee' => 'Coffee cup', 'palette' => 'Palette', 'qr-code' => 'QR code',
    'shopping-bag' => 'Bag / pouch', 'sliders' => 'Sliders', 'sparkles' => 'Sparkles', 'sun' => 'Sun / UV', 'trending-up' => 'Trending up', 'zap' => 'Lightning'];
$icon = $fields->getFieldByHandle('menuIcon');
$have = array_column($icon->options, 'value');
$add = array_diff_key(NEW_ICONS, array_flip($have));
if ($add) {
    $icon->options = array_merge($icon->options, array_map(fn($v, $l) => ['label' => $l, 'value' => $v, 'default' => false], array_keys($add), $add));
    $fields->saveField($icon) || fail('field menuIcon', $icon);
    say('  ~ menuIcon: + ' . implode(', ', array_keys($add)));
}

// ---- "Small Heading" (eyebrow) on Heading, Card Grid and CTA Band: the label above every live answer section ---------
// inserted before the heading field; the other layout elements are kept as they are (their uids hold the saved values)
foreach (['heading' => 'headingText', 'cardGrid' => 'sectionHeading', 'ctaBand' => 'sectionHeading'] as $handle => $before) {
    $type = $entries->getEntryTypeByHandle($handle);
    $layout = $type->getFieldLayout();
    if ($layout->getFieldByHandle('eyebrow')) continue;
    foreach ($layout->getTabs() as $tab) {
        $els = [];
        foreach ($tab->getElements() as $el) {
            if ($el instanceof CustomField && $el->getField()->handle === $before) $els[] = $f('eyebrow', ['label' => 'Small Heading', 'instructions' => 'Optional small label above the heading, e.g. “Buying Criteria”.']);
            $els[] = $el;
        }
        $tab->setElements($els);
    }
    $type->setFieldLayout($layout);
    $entries->saveEntryType($type) || fail("entry type $handle", $type);
    say("  ~ $handle: + Small Heading");
}

// ---- Card Grid: "Section Link" after the intro (e.g. "View Equipment", at the right of the heading, as Recommended Products)
$grid = $entries->getEntryTypeByHandle('cardGrid');
$layout = $grid->getFieldLayout();
if (!$layout->getFieldByHandle('ctaLink')) {
    foreach ($layout->getTabs() as $tab) {
        $els = [];
        foreach ($tab->getElements() as $el) {
            $els[] = $el;
            if ($el instanceof CustomField && $el->getField()->handle === 'sectionLead') $els[] = $f('ctaLink', ['label' => 'Section Link', 'instructions' => 'Optional link at the right of the heading, e.g. “View Equipment”.']);
        }
        $tab->setElements($els);
    }
    $grid->setFieldLayout($layout);
    $entries->saveEntryType($grid) || fail('entry type cardGrid', $grid);
    say('  ~ cardGrid: + Section Link');
}

// ---- Answer + Answers section ---------------------------------------------------------------------------------------
say('Answers');
$answer = entryType('Answer', 'answer', [
    'Answer' => [],
    'Card' => [
        $f('cardTagline', ['label' => 'Card Summary', 'instructions' => 'The text on this answer’s card on /answers/ (one or two sentences).']),
        $f('menuIcon', ['label' => 'Card Icon', 'instructions' => 'The line icon on the card.']),
    ],
], ['icon' => 'circle-question', 'showSlug' => true, 'titleLabel' => 'Answer Title',
    'description' => 'An answer page at /answers/slug/. Moving a live page: keep its live slug.']);
addTab($answer, 'Page Builder', [$fields->getFieldByHandle('pageBuilder')]);
addTab($answer, 'SEO', $seo(['seoTitle', 'seoDescription', 'socialImage', 'seoNoIndex', 'seoCanonical', 'seoRobots']));
section('Answers', 'answers', 'structure', [$answer], 'answers/{slug}', 'answers/_entry', ['maxLevels' => 1]);

// ---- Answers listing page -------------------------------------------------------------------------------------------
say('Answers Page');
$page = entryType('Answers Page', 'answersPage', ['Content' => [
    $f('eyebrow'),
    $f('sectionHeading', ['label' => 'Heading', 'instructions' => '[brackets] = red, e.g. “Arrow Systems [Answers]”.']),
    $f('sectionLead', ['label' => 'Intro']),
    $f('caption', ['label' => 'Count Line', 'instructions' => 'Shown after “24 answers”, e.g. “Equipment, workflows and applications”.']),
    $f('subheading', ['label' => 'Help Band Small Heading', 'instructions' => 'The maroon band under the cards, e.g. “Need Help Choosing?”.']),
    $f('ctaHeading', ['label' => 'Help Band Heading']),
    $f('ctaText', ['label' => 'Help Band Text']),
    $f('ctaLink', ['label' => 'Help Band Button', 'instructions' => 'e.g. “Contact Arrow” → the Contact Us page. The second button calls the toll-free number (Site Settings).']),
]], ['icon' => 'circle-question', 'titleLabel' => 'Page Title']);
addTab($page, 'SEO', $seo(['seoTitle', 'seoDescription', 'socialImage', 'seoNoIndex']));
section('Answers Page', 'answersPage', 'single', [$page], 'answers', 'answers/_index');
saveProjectConfig();
