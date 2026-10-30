<?php
/**
 * Industries page (2026-10-28, QA): the help band's two buttons were fixed (phone + quote link from Site Settings). They
 * are now the page's own First / Second Button (the Button and Secondary Button fields, as the Exhibitions page), after
 * Help Band Text; each shown when it has a text and a link. They start with the band's former buttons: Talk to a
 * Specialist (the toll-free phone) and Request a Quote (the Site Settings quote link).
 * Template: industries/_index.twig. Idempotent. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/pages/37_industries_help_band_buttons.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\fieldlayoutelements\CustomField;

$fields = Craft::$app->getFields();
$type = Craft::$app->getEntries()->getEntryTypeByHandle('industriesPage') ?? exit("no entry type industriesPage\n");
$layout = $type->getFieldLayout();
if ($layout->getFieldByHandle('ctaLink')) exit("done already\n");

foreach ($layout->getTabs() as $tab) {
    $els = $tab->getElements();
    foreach ($els as $i => $el) {
        if (!$el instanceof CustomField) continue;
        if ($el->getField()->handle === 'ctaHeading') $el->instructions = 'The maroon band under the cards.';
        if ($el->getField()->handle !== 'ctaText') continue;
        array_splice($els, $i + 1, 0, [
            new CustomField($fields->getFieldByHandle('ctaLink'), ['label' => 'Help Band First Button',
                'instructions' => 'The first (white) button in the maroon band, shown when it has a text and a link, e.g. “Talk to a Specialist” → phone 1-888-697-2749.']),
            new CustomField($fields->getFieldByHandle('secondaryCta'), ['label' => 'Help Band Second Button',
                'instructions' => 'The second (outline) button, shown when it has a text and a link, e.g. “Request a Quote” → the Request a Quote page.']),
        ]);
        $tab->setElements($els);
        break 2;
    }
}
$type->setFieldLayout($layout);
Craft::$app->getEntries()->saveEntryType($type) || fail('entry type industriesPage', $type);
say('  ~ industriesPage: + Help Band First / Second Button after Help Band Text');
saveProjectConfig();

$site = GlobalSet::find()->handle('siteSettings')->one();
$page = Entry::find()->type('industriesPage')->status(null)->one() ?? exit("no Industries page\n");
$page->setFieldValue('ctaLink', ['type' => 'tel', 'value' => '+18886972749', 'label' => 'Talk to a Specialist']);
$page->setFieldValue('secondaryCta', array_merge($site->quoteLink->serialize(), ['label' => 'Request a Quote']));
Craft::$app->getElements()->saveElement($page) || fail('Industries page', $page);
say('  ~ Industries page: buttons Talk to a Specialist, Request a Quote');
