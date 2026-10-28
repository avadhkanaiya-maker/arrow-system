<?php
/**
 * Page builder, general (2026-10-28, for the /arrow-materials/ design; any page, industry or product can use it):
 *   Tabs block  + Small Heading, Heading ([red] words) and Intro above the tabs (all optional); + Anchor ID (Section
 *               Settings): the section's id, e.g. a button linking to #specifications
 *   Cards Tab   a new tab type (Tabs → New → Cards Tab): Tab Title + Cards (the Card Grid's cards: title, subtitle, image,
 *               text, link), shown as a card grid; one card shows as one wide card, several show their number on the tab
 *   Button      + Asset: a button or a card's link can open a file from Documents, e.g. "Download Spec Sheet" → a PDF
 *   Card Grid   + Card Style: Standard · Link cards ("More from …": the image, or else the card's Icon, on top; Subtitle as a
 *               small heading above the title; the link's label as "View … →" at the bottom; heading centred)
 *   Card        + Icon (the Menu Icon list): shown by Link cards that have no image
 * Templates: products/blocks/tabsBlock (head, Cards Tab), industries/blocks/{tabsBlock,cardGrid}; styles in craft.css.
 * Idempotent. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/pages/30_cards_tab.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';
require dirname(__DIR__) . '/products/_helpers.php';

use craft\fieldlayoutelements\CustomField;
use craft\fields\Dropdown;
use craft\models\EntryType;

$fields = Craft::$app->getFields();
$entries = Craft::$app->getEntries();
$f = fn(string $h) => $fields->getFieldByHandle($h) ?? exit("no field $h\n");
$type = fn(string $h) => $entries->getEntryTypeByHandle($h) ?? exit("no entry type $h\n");

/** Put layout elements next to the field $next (after it, or before it); fields already in the layout are skipped */
function place(EntryType $et, string $next, array $elements, bool $before = false): void {
    $layout = $et->getFieldLayout();
    $elements = array_values(array_filter($elements, fn(CustomField $e) => !$layout->getFieldByHandle($e->getField()->handle)));
    if (!$elements) return;
    foreach ($layout->getTabs() as $tab) {
        $els = $tab->getElements();
        foreach ($els as $i => $el) {
            if (!$el instanceof CustomField || $el->getField()->handle !== $next) continue;
            array_splice($els, $before ? $i : $i + 1, 0, $elements);
            $tab->setElements($els);
            $et->setFieldLayout($layout);
            Craft::$app->getEntries()->saveEntryType($et) || fail("entry type {$et->handle}", $et);
            say("  ~ {$et->handle}: + " . implode(', ', array_map(fn($e) => $e->label ?: $e->getField()->name, $elements)) . ($before ? " before $next" : " after $next"));
            return;
        }
    }
    exit("{$et->handle}: no field $next in its layout\n");
}

// ---- Tabs block: a section head above the tabs + Anchor ID
$tabsBlock = $type('tabsBlock');
place($tabsBlock, 'productTabs', [
    new CustomField($f('eyebrow'), ['label' => 'Small Heading', 'instructions' => 'Optional small label above the heading, e.g. “Specialty materials”.']),
    new CustomField($f('sectionHeading'), ['label' => 'Heading', 'instructions' => 'Optional heading above the tabs. [brackets] = red.']),
    new CustomField($f('sectionLead'), ['label' => 'Intro', 'instructions' => 'Optional line under the heading.']),
], true);
addTab($tabsBlock, 'Section Settings', [$f('anchorId')]);

// ---- Cards Tab, offered by every Tabs block
$cardsTab = blockType('Cards Tab', 'cardsTab', 'table-cells-large', [
    new CustomField($f('headingText'), ['label' => 'Tab Title', 'required' => true]),
    new CustomField($f('cardItems'), ['instructions' => 'One card per item: Title, Text and a Link (a page, or a file such as a spec sheet PDF; its Label is the button text, e.g. “Download Spec Sheet”).']),
], '{headingText}', 'A tab of cards, e.g. one material per card with its spec sheet.');
$tabsField = $f('productTabs');
if (!in_array($cardsTab->uid, array_map(fn($t) => $t->uid, $tabsField->getEntryTypes()), true)) {
    $tabsField->setEntryTypes([...$tabsField->getEntryTypes(), $cardsTab]);
    $fields->saveField($tabsField) || fail('field productTabs', $tabsField);
    say('  ~ productTabs: + Cards Tab');
}

// ---- Button: can link to a file (Documents)
$button = $f('ctaLink');
if (!in_array('asset', $button->types, true)) {
    $docs = Craft::$app->getVolumes()->getVolumeByHandle('documents') ?? exit("no Documents volume\n");
    $button->types[] = 'asset';
    $button->typeSettings['asset'] = ['sources' => ['volume:' . $docs->uid]];
    $fields->saveField($button) || fail('field ctaLink', $button);
    say('  ~ ctaLink (Button): + Asset (Documents)');
}

// ---- Card Grid: Card Style; Card: Icon
$style = field(Dropdown::class, ['name' => 'Card Style', 'handle' => 'cardStyle',
    'instructions' => 'Link cards: the image (or else the card’s Icon) on top, the Subtitle as a small heading above the title and the link’s Label (e.g. “View films”) at the bottom; the heading is centred. For “More from …” sections.',
    'options' => options(['standard' => 'Standard', 'link' => 'Link cards'])]);
place($type('cardGrid'), 'cardShadow', [new CustomField($style)]);
place($type('card'), 'mediaImage', [new CustomField($f('menuIcon'), ['label' => 'Icon', 'instructions' => 'Shown on top of a Link card that has no image.'])]);

saveProjectConfig();

// ---- check
$entries = Craft::$app->getEntries();
in_array('cardsTab', array_map(fn($t) => $t->handle, $fields->getFieldByHandle('productTabs')->getEntryTypes()), true) || exit("productTabs: no Cards Tab\n");
foreach (['tabsBlock' => ['eyebrow', 'sectionHeading', 'sectionLead', 'anchorId'], 'cardGrid' => ['cardStyle'], 'card' => ['menuIcon'], 'cardsTab' => ['headingText', 'cardItems']] as $h => $want) {
    $layout = $entries->getEntryTypeByHandle($h)->getFieldLayout();
    foreach ($want as $w) $layout->getFieldByHandle($w) || exit("$h: no $w\n");
}
in_array('asset', $fields->getFieldByHandle('ctaLink')->types, true) || exit("ctaLink: no Asset type\n");
say('  = Tabs head + Anchor ID, Cards Tab, Button → file, Card Grid Card Style, Card Icon');
