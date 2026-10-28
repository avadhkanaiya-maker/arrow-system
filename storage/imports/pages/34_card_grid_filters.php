<?php
/**
 * Page builder, general (2026-10-28, for the Arrow Material design, demo.theglobalwebdev.com/Arrowsystem/arrow-material/):
 *   Card Grid  + Filter Tabs and Search (Section Settings, a switch): above the cards, a tab per Subtitle (with counts, e.g.
 *              “Self Adhesive Material 9”) and a search box; the cards are paged, 3 rows per page (9 at 3 per row), with
 *              Previous / Next. ?type=…, ?q=… and ?page=… in the address pre-set the view. Without JS every card shows.
 * Templates: industries/blocks/cardGrid; script: craft.js; styles: craft.css.
 * Idempotent. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/pages/34_card_grid_filters.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\fieldlayoutelements\CustomField;
use craft\fields\Lightswitch;

$entries = Craft::$app->getEntries();
$cardGrid = $entries->getEntryTypeByHandle('cardGrid') ?? exit("no entry type cardGrid\n");
$filters = field(Lightswitch::class, ['name' => 'Filter Tabs and Search', 'handle' => 'cardFilters',
    'instructions' => 'On: a tab per card Subtitle (e.g. the material type) and a search box above the cards, which are shown 3 rows at a time with page links. For long lists, e.g. all materials.']);

$layout = $cardGrid->getFieldLayout();
if (!$layout->getFieldByHandle('cardFilters')) {
    foreach ($layout->getTabs() as $tab) {
        $els = $tab->getElements();
        foreach ($els as $i => $el) {
            if (!$el instanceof CustomField || $el->getField()->handle !== 'cardStyle') continue;
            array_splice($els, $i + 1, 0, [new CustomField($filters)]);
            $tab->setElements($els);
            $cardGrid->setFieldLayout($layout);
            $entries->saveEntryType($cardGrid) || fail('entry type cardGrid', $cardGrid);
            say('  ~ cardGrid: + Filter Tabs and Search after cardStyle');
            break 2;
        }
    }
}
saveProjectConfig();

$entries->getEntryTypeByHandle('cardGrid')->getFieldLayout()->getFieldByHandle('cardFilters') || exit("cardGrid: no cardFilters (is Card Style in its layout? run 30_cards_tab.php)\n");
say('  = Card Grid: Filter Tabs and Search');
