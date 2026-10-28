<?php
/**
 * Exhibitions page (/exhibitions/): both header buttons managed in the CP, no conditions.
 *   First Button    the existing Contact Link (ctaLink), relabelled; still the Contact button on the next-show ticket too
 *   Second Button   + the shared Second Button field (secondaryCta), filled with what the header showed: "Past
 *                   Exhibitions" → #past (the list below)
 * A button shows when it has both a text and a URL (templates/exhibitions/_index.twig); the template's fixed "Upcoming
 * Exhibitions" / "Past Exhibitions" buttons are gone. Stops if the page has an open draft. Idempotent. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/exhibitions/03_header_buttons.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\db\Query;
use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;

$entries = Craft::$app->getEntries();
$type = $entries->getEntryTypeByHandle('exhibitionsPage');
$layout = $type->getFieldLayout();
if (!$layout->getFieldByHandle('secondaryCta')) {
    foreach ($layout->getTabs() as $tab) {
        $els = [];
        foreach ($tab->getElements() as $el) {   // existing elements kept as they are (their uids hold the saved values)
            $els[] = $el;
            if ($el instanceof CustomField && $el->getField()->handle === 'ctaLink') {
                $el->label = 'First Button';
                $el->instructions = 'The first (red) button in the page header, shown when it has a text and a URL, e.g. “Contact Us” → the Contact page. Also the Contact button on the next-show ticket.';
                $els[] = new CustomField(Craft::$app->getFields()->getFieldByHandle('secondaryCta'), ['label' => 'Second Button',
                    'instructions' => 'The second (outline) button in the page header, shown when it has a text and a URL, e.g. “Past Exhibitions” → #past (the list below).']);
            }
        }
        $tab->setElements($els);
    }
    $type->setFieldLayout($layout);
    $entries->saveEntryType($type) || fail('entry type exhibitionsPage', $type);
    saveProjectConfig();
    say('  ~ exhibitionsPage: Contact Link → First Button; + Second Button');
}

$page = Entry::find()->section('exhibitionsPage')->one() ?? exit("no Exhibitions page\n");
if (!$page->getFieldValue('secondaryCta')?->url) {
    !(new Query())->from('{{%drafts}}')->where(['canonicalId' => $page->id])->exists() || exit("the Exhibitions page has an open draft: apply or delete it, then run again\n");
    $page->setFieldValue('secondaryCta', ['type' => 'url', 'value' => '#past', 'label' => 'Past Exhibitions']);
    Craft::$app->getElements()->saveElement($page) || fail('exhibitions page', $page);
    say('  ~ /exhibitions/: Second Button “Past Exhibitions” → #past');
}
