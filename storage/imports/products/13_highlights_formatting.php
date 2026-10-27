<?php
/**
 * Highlights tab: bold / italic in a highlight's Title and Text, the same way as the Specifications tab's Description
 * (\*text\* / \*\*text\*\*, rendered by tabsBlock.twig with |md). This sets the editor hint on the Highlights field
 * of the Highlights Tab form; the field itself is not re-saved. Idempotent.
 * Run from the project root: /opt/lampp/bin/php storage/imports/products/13_highlights_formatting.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

const HINT = 'Use \*text\* for italic and \*\*text\*\* for bold (Title and Text).';

$entries = Craft::$app->getEntries();
$type = $entries->getEntryTypeByHandle('highlightsTab') ?? exit("no highlightsTab entry type\n");
$layout = $type->getFieldLayout();
$el = current(array_filter($layout->getCustomFieldElements(), fn($e) => $e->attribute() === 'highlightItems')) ?: exit("no Highlights field on the form\n");
if ($el->instructions === HINT) exit("already set\n");
$el->instructions = HINT;
$type->setFieldLayout($layout);
$entries->saveEntryType($type) || fail('entry type highlightsTab', $type);
say('  ~ Highlights Tab form: Highlights hint (bold / italic)');
saveProjectConfig();
