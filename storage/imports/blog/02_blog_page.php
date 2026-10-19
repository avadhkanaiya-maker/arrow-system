<?php
/**
 * Blog migration: content of the Blog Page single (/blog/), from the design (blogs/index.html) and the live site.
 *   Content      eyebrow, heading, intro (design)
 *   Sidebar      the "Not sure which equipment fits?" box (design; also the article rail)
 *   Default CTA  the closing box of every article without its own Call to Action block. Replaces the live per-post
 *                contact form (D4); 244 of the 245 forms sat under the heading "Contact With Our Team Now".
 *   SEO          meta description from the design (the live one is "Blog Page 1")
 * Only empty fields are filled, so edits made in the CP are kept on a re-run.
 * Run from the project root: /opt/lampp/bin/php storage/imports/blog/02_blog_page.php
 */
require __DIR__ . '/_bootstrap.php';

use craft\elements\Entry;

$page = Entry::find()->section('blogPage')->status(null)->one() ?? throw new Exception('Run 01_structure.php first');

$values = [
    'eyebrow' => 'Guides & Articles',
    'sectionHeading' => 'The Arrow [Blog]',
    'sectionLead' => 'More insights on the labels and packaging industry: practical guides to digital printing, finishing and bringing label production in-house.',
    'helpTitle' => 'Not sure which equipment fits?',
    'helpText' => 'Tell us your application, materials and run lengths.',
    'helpLink' => ['type' => 'tel', 'value' => '+18886972749', 'label' => 'Talk to a Specialist'],
    'quoteLink' => ['type' => 'url', 'value' => 'https://arrsys.com/request-quote/', 'label' => 'Request a Quote'],
    'ctaHeading' => 'Contact With Our Team Now',
    'ctaText' => 'Tell us about your labels or packaging: application, materials, volumes and run lengths. An Arrow Systems specialist will recommend the right printing and finishing setup for your line.',
    'ctaLink' => ['type' => 'url', 'value' => 'https://arrsys.com/contact-us/', 'label' => 'Contact Our Team'],
    'seoDescription' => 'Guides and articles from Arrow Systems, Inc. on digital label printing, finishing, flexible packaging and bringing label production in-house.',
];
$set = [];
foreach ($values as $handle => $value) {
    $current = $page->getFieldValue($handle);
    $empty = $current === null || $current === '' || (is_object($current) && property_exists($current, 'value') && !$current->value);
    if ($empty) $set[$handle] = $value;
}
$page->title = 'Blog';
$page->setFieldValues($set);
Craft::$app->getElements()->saveElement($page) || fail('blog page', $page);
say('Blog Page saved; filled: ' . (implode(', ', array_keys($set)) ?: 'nothing (all set)'));
