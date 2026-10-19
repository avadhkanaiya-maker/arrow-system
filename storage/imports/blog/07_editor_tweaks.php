<?php
/**
 * Blog migration: editor wording fixes after the first review of the control panel.
 *   - Items / Terms tables: instructions are rendered as Markdown, so the asterisks are escaped ("\*text\*")
 *   - Blog Post: a hint on the Title field about [red] words
 * Idempotent. Run from the project root: /opt/lampp/bin/php storage/imports/blog/07_editor_tweaks.php
 */
require __DIR__ . '/_bootstrap.php';

use craft\fieldlayoutelements\entries\EntryTitleField;

$fields = Craft::$app->getFields();
$texts = [
    'listItems' => 'One point per row. Use \*text\* for italic and \*\*text\*\* for bold.',
    'specItems' => 'A term and its explanation per row. Use \*text\* for italic and \*\*text\*\* for bold.',
];
foreach ($texts as $handle => $text) {
    $field = $fields->getFieldByHandle($handle);
    if ($field->instructions !== $text) {
        $field->instructions = $text;
        $fields->saveField($field) || fail("field $handle", $field);
        say("  ~ $handle instructions");
    }
}

$entries = Craft::$app->getEntries();
$type = $entries->getEntryTypeByHandle('blogPost');
$layout = $type->getFieldLayout();
$hint = 'The post title, shown as the big heading. Put words in [square brackets] to show them in red, e.g. “Throughput & [Cost]”.';
foreach ($layout->getTabs() as $tab) {
    foreach ($tab->getElements() as $el) {
        if ($el instanceof EntryTitleField && $el->instructions !== $hint) {
            $el->instructions = $hint;
            $type->setFieldLayout($layout);
            $entries->saveEntryType($type) || fail('entry type blogPost', $type);
            say('  ~ blogPost: Title instructions');
        }
    }
}
saveProjectConfig();
