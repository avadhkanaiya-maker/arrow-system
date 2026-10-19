<?php
/**
 * Blog search: Craft's search index only covered titles (no field was "searchable"). This marks the blog's text fields
 * searchable (the lead, the Article Body and the text fields inside its blocks) and rebuilds the blog's search index,
 * so /blog/?q=… finds words in the article text. The fields are shared, so products / industries text becomes
 * searchable too (for a later site search). Idempotent. Back up first. Run from the project root:
 *   /opt/lampp/bin/php storage/imports/blog/09_search.php && /opt/lampp/bin/php craft resave/entries --section=blog --update-search-index
 */
require __DIR__ . '/_bootstrap.php';

$fields = Craft::$app->getFields();
foreach (['sectionLead', 'articleBody', 'headingText', 'answerLead', 'richText', 'shortRichText', 'quoteText', 'quoteSource',
    'listItems', 'specItems', 'stepItems', 'sectionBody', 'compareColumns', 'faqItems', 'productItems', 'productName',
    'caption', 'tableText', 'ctaHeading', 'ctaText'] as $handle) {
    $field = $fields->getFieldByHandle($handle) ?? fail("field $handle", new craft\base\Model());
    if ($field->searchable) continue;
    $field->searchable = true;
    $fields->saveField($field) || fail("field $handle", $field);
    say("  ~ $handle: searchable");
}
saveProjectConfig();
