<?php
/**
 * Blog migration, Phases 1–2: fields, entry types, sections and the "Article Body" page builder (Matrix).
 * (BLOG_MIGRATION_PLAN.md, decisions D1–D7.)
 *
 *   Sections
 *     Blog              Channel    /{slug}/                      blog/_entry      the posts (root-level URLs, as live)
 *     Blog Page         Single     /blog/  (+ /blog/page/N/)     blog/_index      listing texts, default CTA, sidebar box, SEO
 *     Blog Categories   Structure  /category/{parent…}/{slug}/   blog/_category   3 levels, as live
 *     Blog Tags         Channel    /tag/{slug}/                  blog/_tag        noindex, as live (D3: migrated)
 *
 *   Matrix field "Article Body" (articleBody, Blocks view): the blocks of the design's article (blogs/blog-detail.html),
 *   one entry type per block, in groups Text · Lists · Media · Products & CTA · Q&A · Other. Blocks with child blocks
 *   (Steps, Comparison, Product List, FAQ) hold a nested Matrix field. (Until 2026-10-13 this was a Neo field; see
 *   08_neo_to_matrix.php.)
 *
 * Idempotent: fields, entry types and sections that exist are left as they are; the block entry types are synced by
 * handle (field-layout UIDs of kept fields are preserved, so block content survives a re-run).
 * Back up first:            /opt/lampp/bin/php craft db/backup
 * Run from the project root: /opt/lampp/bin/php storage/imports/blog/01_structure.php
 */
require __DIR__ . '/_bootstrap.php';

use craft\ckeditor\Field as Ckeditor;
use craft\elements\conditions\users\AdminConditionRule;
use craft\elements\User;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Assets;
use craft\fields\Dropdown;
use craft\fields\Entries;
use craft\fields\Lightswitch;
use craft\fields\Matrix;
use craft\fields\PlainText;
use craft\fields\Table;
use craft\models\EntryType;
use craft\models\FieldLayout;

foreach (['ckeditor'] as $plugin) {
    if (!Craft::$app->getPlugins()->isPluginEnabled($plugin)) {
        fwrite(STDERR, "Install the $plugin plugin first.\n");
        exit(1);
    }
}

$fields = Craft::$app->getFields();
$entries = Craft::$app->getEntries();

/** A field layout element with per-layout label / instructions: f('sectionLead', ['label' => 'Lead']) */
function f(string $handle, array $opts = []): CustomField {
    $field = Craft::$app->getFields()->getFieldByHandle($handle) ?? throw new Exception("No field $handle");
    return new CustomField($field, $opts);
}

$images = Craft::$app->getVolumes()->getVolumeByHandle('images');
$imageSettings = [
    'allowedKinds' => ['image'], 'restrictFiles' => true, 'viewMode' => 'large', 'previewMode' => 'full',
    'sources' => ["volume:{$images->uid}"],
    'defaultUploadLocationSource' => "volume:{$images->uid}", 'defaultUploadLocationSubpath' => 'blog',
];

// ---------------------------------------------------------------------------------------------------------------
say('Fields: SEO (shared by posts, the blog page and categories)');
field(PlainText::class, ['name' => 'SEO Title', 'handle' => 'seoTitle', 'charLimit' => 120,
    'instructions' => 'The title shown in Google and the browser tab. Leave empty to use the entry title + “| Arrow Systems, Inc.”.']);
field(PlainText::class, ['name' => 'Meta Description', 'handle' => 'seoDescription', 'multiline' => true, 'initialRows' => 2, 'charLimit' => 300,
    'instructions' => 'The text under the title in Google results (about 150–160 characters).']);
field(Assets::class, ['name' => 'Social Image', 'handle' => 'socialImage', 'maxRelations' => 1,
    'instructions' => 'The picture shown when the page is shared on LinkedIn, Facebook etc. Leave empty to use the cover image.'] + $imageSettings);
field(Lightswitch::class, ['name' => 'Hide from Search Engines', 'handle' => 'seoNoIndex',
    'instructions' => 'Adds “noindex”, so Google does not list this page. Leave off for normal pages.']);

say('Fields: article text (CKEditor)');
field(Ckeditor::class, ['name' => 'Rich Text', 'handle' => 'richText',
    'instructions' => 'Paragraphs, lists, links and tables. For a section heading that appears in “On this page”, add a Heading block instead.',
    'toolbar' => ['heading', '|', 'bold', 'italic', 'link', '|', 'bulletedList', 'numberedList', '|', 'insertTable', 'blockQuote', '|', 'sourceEditing'],
    'headingLevels' => [3, 4],
]);
field(Ckeditor::class, ['name' => 'Short Text', 'handle' => 'shortRichText',
    'instructions' => 'A short paragraph: bold, italic and links.',
    'toolbar' => ['bold', 'italic', 'link'],
    'headingLevels' => false,
]);
field(Ckeditor::class, ['name' => 'Table', 'handle' => 'tableText',
    'instructions' => 'Insert a table. Tick “Header row” (and “Header column” for row labels) in the table menu.',
    'toolbar' => ['insertTable', '|', 'bold', 'italic', 'link', '|', 'sourceEditing'],
    'headingLevels' => false,
]);

say('Fields: article blocks');
field(PlainText::class, ['name' => 'Heading Text', 'handle' => 'headingText']);
field(Dropdown::class, ['name' => 'Level', 'handle' => 'headingLevel',
    'instructions' => 'H2 = a main section (listed in “On this page”). H3 / H4 = a sub-heading inside a section.',
    'options' => options(['h2' => 'H2: section', 'h3' => 'H3: sub-heading', 'h4' => 'H4: small heading'], 'h2')]);
field(PlainText::class, ['name' => 'Contents Label', 'handle' => 'tocLabel',
    'instructions' => 'Optional: a shorter text for the “On this page” list, e.g. “Application Speed”. Empty = the heading.']);
field(Lightswitch::class, ['name' => 'Show in “On this page”', 'handle' => 'showInContents', 'default' => true,
    'instructions' => 'Lists this H2 in the article’s contents box. Ignored for H3 / H4.']);
field(PlainText::class, ['name' => 'Answer Lead', 'handle' => 'answerLead', 'multiline' => true, 'initialRows' => 2,
    'instructions' => 'Optional: one bold sentence under the heading that answers it directly (good for Google’s answer boxes).']);
field(Dropdown::class, ['name' => 'Style', 'handle' => 'calloutStyle',
    'options' => options(['callout' => 'Callout (red bar, key point)', 'note' => 'Note (grey, small print)'], 'callout')]);
field(PlainText::class, ['name' => 'Quote', 'handle' => 'quoteText', 'multiline' => true, 'initialRows' => 3]);
field(PlainText::class, ['name' => 'Source', 'handle' => 'quoteSource', 'instructions' => 'Optional: who said it.']);
field(Table::class, ['name' => 'Items', 'handle' => 'listItems', 'addRowLabel' => 'Add item',
    'instructions' => 'One point per row. Use \\*text\\* for italic and \\*\\*text\\*\\* for bold.',
    'columns' => ['col1' => ['heading' => 'Item', 'handle' => 'text', 'type' => 'multiline', 'width' => '']]]);
field(Table::class, ['name' => 'Terms', 'handle' => 'specItems', 'addRowLabel' => 'Add term',
    'instructions' => 'A term and its explanation per row. Use \\*text\\* for italic and \\*\\*text\\*\\* for bold.',
    'columns' => [
        'col1' => ['heading' => 'Term', 'handle' => 'term', 'type' => 'singleline', 'width' => '30%'],
        'col2' => ['heading' => 'Description', 'handle' => 'description', 'type' => 'multiline', 'width' => ''],
    ]]);
field(Dropdown::class, ['name' => 'Column Style', 'handle' => 'compareStyle',
    'options' => options(['pro' => 'Pros (green ticks)', 'con' => 'Cons / limits (red)'], 'pro')]);
field(PlainText::class, ['name' => 'Caption', 'handle' => 'caption', 'instructions' => 'Optional text under it.']);
field(Dropdown::class, ['name' => 'Width', 'handle' => 'imageWidth',
    'options' => options(['full' => 'Full article width', 'large' => 'Large (¾)', 'medium' => 'Medium (½)', 'small' => 'Small (⅓)'], 'full')]);
field(Assets::class, ['name' => 'Images', 'handle' => 'galleryImages',
    'instructions' => 'Choose or upload the photos; drag to reorder.'] + $imageSettings);
field(Dropdown::class, ['name' => 'Columns', 'handle' => 'galleryColumns',
    'options' => options(['2' => '2', '3' => '3', '4' => '4'], '3')]);
field(PlainText::class, ['name' => 'Tag Line', 'handle' => 'productTag',
    'instructions' => 'Small red line above the product name, e.g. “Entry-level press for lower-volume production”.']);
field(Dropdown::class, ['name' => 'Button Style', 'handle' => 'buttonStyle',
    'options' => options(['primary' => 'Red button', 'outline' => 'Outline button', 'link' => 'Text link with arrow'], 'primary')]);
field(PlainText::class, ['name' => 'HTML Code', 'handle' => 'htmlCode', 'multiline' => true, 'code' => true, 'initialRows' => 8,
    'instructions' => 'Output as-is, without filtering. Only paste code from a trusted source.']);
field(PlainText::class, ['name' => 'CTA Heading', 'handle' => 'ctaHeading']);
field(PlainText::class, ['name' => 'CTA Text', 'handle' => 'ctaText', 'multiline' => true, 'initialRows' => 4,
    'instructions' => 'Leave an empty line between paragraphs.']);

// ---------------------------------------------------------------------------------------------------------------
say('Blog Categories and Blog Tags');
$categoryType = entryType('Blog Category', 'blogCategory', ['Content' => [
    f('sectionLead', ['label' => 'Description', 'instructions' => 'Optional: a short intro shown under the category name.']),
]], ['icon' => 'folder', 'showSlug' => true, 'description' => 'A blog category; nest it under another one for /category/parent/child/ URLs.']);
$tagType = entryType('Blog Tag', 'blogTag', ['Content' => []],
    ['icon' => 'tag', 'showSlug' => true, 'description' => 'A blog tag (/tag/slug/). Tag pages are hidden from search engines, as on the live site.']);

$categories = section('Blog Categories', 'blogCategories', 'structure', [$categoryType],
    "{{ object.parent ? object.parent.uri ~ '/' : 'category/' }}{{ object.slug }}", 'blog/_category', ['maxLevels' => 3]);
$tags = section('Blog Tags', 'blogTags', 'channel', [$tagType], 'tag/{slug}', 'blog/_tag');

say('Blog posts and the blog page');
$postType = entryType('Blog Post', 'blogPost', ['Content' => [
    f('sectionLead', ['label' => 'Lead', 'instructions' => 'One or two sentences under the title. Also the excerpt on the blog cards.']),
    f('featuredImage', ['label' => 'Cover Image', 'instructions' => 'The large picture next to the title (16:9, at least 1280 × 720). Also the card image.']),
]], ['icon' => 'newspaper', 'showSlug' => true, 'titleLabel' => 'Title',
    'description' => 'A blog article. The URL is the slug at the root of the site: /slug/']);
$blog = section('Blog', 'blog', 'channel', [$postType], '{slug}', 'blog/_entry');

$pageType = entryType('Blog Page', 'blogPage', [
    'Content' => [
        f('eyebrow', ['instructions' => 'Small red label above the heading, e.g. “Guides & Articles”.']),
        f('sectionHeading', ['label' => 'Heading']),
        f('sectionLead', ['label' => 'Intro']),
    ],
    'Sidebar' => [
        f('helpTitle', ['label' => 'Help Box Title', 'instructions' => 'The red box in the blog sidebar and the article rail, e.g. “Not sure which equipment fits?”.']),
        f('helpText', ['label' => 'Help Box Text']),
        f('helpLink', ['label' => 'First Button', 'instructions' => 'e.g. a phone link labelled “Talk to a Specialist”.']),
        f('quoteLink', ['label' => 'Second Button', 'instructions' => 'e.g. the Request a Quote page.']),
    ],
    'Default CTA' => [
        f('ctaHeading', ['instructions' => 'The closing box at the end of every article that has no Call to Action block of its own.']),
        f('ctaText'),
        f('ctaLink', ['label' => 'CTA Button']),
    ],
], ['icon' => 'newspaper', 'description' => 'The /blog/ listing page.']);
$blogPage = section('Blog Page', 'blogPage', 'single', [$pageType], 'blog', 'blog/_index');

say('Relation fields');
$catField = field(Entries::class, ['name' => 'Categories', 'handle' => 'blogCategories',
    'instructions' => 'The first one is the main category (breadcrumb, card label and “Filed under”).',
    'sources' => ["section:{$categories->uid}"], 'maintainHierarchy' => false, 'showSearchInput' => true]);
$tagField = field(Entries::class, ['name' => 'Tags', 'handle' => 'blogTags',
    'sources' => ["section:{$tags->uid}"], 'showSearchInput' => true, 'viewMode' => 'list']);
$relatedField = field(Entries::class, ['name' => 'More Insights', 'handle' => 'relatedPosts', 'maxRelations' => 3,
    'instructions' => 'Optional: up to 3 articles shown under the post. Empty = the latest articles from the same category.',
    'sources' => ["section:{$blog->uid}"], 'showSearchInput' => true]);

// ---------------------------------------------------------------------------------------------------------------
say('Page builder: Article Body (Matrix)');
// The builder is a Matrix field in Blocks view. Each block is an entry type; blocks with child blocks (Steps, Comparison,
// Product List, FAQ) hold their own nested Matrix field. Blocks are grouped (Text, Lists, Media…) and have icons;
// modules/builderui turns the "Add block" menu into an icon grid with a search box.
$builderHandle = 'articleBody';

$anchorHint = ['instructions' => 'Optional: the #link name. Leave empty to make it from the heading.'];
// [group, name, handle, icon (Font Awesome), description, [fields], options: children = nested field, title = title format]
$catalog = [
    ['Text', 'Heading', 'heading', 'heading', 'A section heading (H2) or sub-heading (H3/H4)', [
        f('headingText', ['label' => 'Heading', 'required' => true]),
        f('headingLevel'), f('answerLead'), f('showInContents'), f('tocLabel'), f('anchorId', $anchorHint),
    ], ['title' => '{headingText}']],
    ['Text', 'Rich Text', 'richText', 'align-left', 'Paragraphs, lists, links, tables', [f('richText', ['label' => 'Text'])]],
    ['Text', 'Intro', 'intro', 'paragraph', 'The large opening paragraph', [f('shortRichText', ['label' => 'Text', 'required' => true])]],
    ['Text', 'Callout', 'callout', 'circle-info', 'A highlighted key point or a small note', [f('shortRichText', ['label' => 'Text', 'required' => true]), f('calloutStyle')]],
    ['Text', 'Quote', 'quote', 'quote-left', 'A quotation', [f('quoteText', ['required' => true]), f('quoteSource')]],

    ['Lists', 'Key Takeaways', 'takeaways', 'list-check', 'A boxed summary list with a heading', [
        f('headingText', ['label' => 'Heading', 'required' => true, 'instructions' => 'e.g. “Key Takeaways”. Listed in “On this page”.']),
        f('listItems', ['required' => true]), f('anchorId', $anchorHint),
    ], ['title' => '{headingText}']],
    ['Lists', 'Checklist', 'checklist', 'check', 'A list with red ticks', [f('listItems', ['required' => true])]],
    ['Lists', 'Steps', 'steps', 'list-ol', 'Numbered steps: add a Step for each one', [], ['children' => 'stepItems']],
    ['Lists', 'Step', 'step', 'hashtag', 'One numbered step', [
        f('headingText', ['label' => 'Title', 'required' => true]),
        f('sectionBody', ['label' => 'Text', 'instructions' => 'One or two sentences.']),
    ], ['child' => true, 'title' => '{headingText}']],
    ['Lists', 'Spec List', 'specList', 'clipboard-list', 'Terms with explanations', [f('specItems', ['required' => true])]],
    ['Lists', 'Comparison', 'compare', 'table-columns', 'Two columns side by side, e.g. pros and cons', [], ['children' => 'compareColumns']],
    ['Lists', 'Comparison Column', 'compareColumn', 'square', 'One column of a comparison', [
        f('headingText', ['label' => 'Heading', 'required' => true]), f('listItems', ['required' => true]), f('compareStyle'),
    ], ['child' => true, 'title' => '{headingText}']],

    ['Media', 'Image', 'image', 'image', 'A picture with an optional caption', [
        f('mediaImage', ['label' => 'Image', 'required' => true, 'instructions' => 'Set the alt text on the image itself (click it → Alternative Text).']),
        f('caption'), f('imageWidth'),
    ]],
    ['Media', 'Gallery', 'gallery', 'images', 'A grid of pictures', [f('galleryImages', ['required' => true]), f('galleryColumns')]],
    ['Media', 'Video', 'video', 'circle-play', 'A YouTube video', [f('videoUrl', ['required' => true]), f('caption')]],
    ['Media', 'Data Table', 'dataTable', 'table', 'A table with a caption', [f('caption', ['instructions' => 'Shown above the table; also names it for screen readers.']), f('tableText', ['required' => true])], ['title' => '{caption}']],

    ['Products & CTA', 'Product List', 'productList', 'boxes-stacked', 'Product cards: add a Product for each one', [], ['children' => 'productItems']],
    ['Products & CTA', 'Product', 'productItem', 'box', 'One product in a product list', [
        f('productTag'),
        f('productName', ['label' => 'Name', 'required' => true, 'instructions' => 'e.g. “ArrowJet Eco 330R”.']),
        f('ctaLink', ['label' => 'Link', 'instructions' => 'The product page.']),
        f('sectionBody', ['label' => 'Description', 'instructions' => 'A short paragraph about the product.']),
    ], ['child' => true, 'title' => '{productName}']],
    ['Products & CTA', 'Button', 'button', 'arrow-pointer', 'A link button', [f('ctaLink', ['label' => 'Button', 'required' => true]), f('buttonStyle')]],
    ['Products & CTA', 'Call to Action', 'cta', 'rocket', 'The red closing box with a button', [
        f('ctaHeading', ['label' => 'Heading', 'required' => true]), f('ctaText', ['label' => 'Text']), f('ctaLink', ['label' => 'Button']),
    ], ['title' => '{ctaHeading}']],

    ['Q&A', 'FAQ', 'faq', 'circle-question', 'Questions that open and close: add an FAQ Item for each one', [], ['children' => 'faqItems']],
    ['Q&A', 'FAQ Item', 'faqItem', 'chevron-down', 'One question and its answer', [
        f('headingText', ['label' => 'Question', 'required' => true]),
        f('richText', ['label' => 'Answer', 'required' => true, 'instructions' => 'The answer shown when the question is opened.']),
    ], ['child' => true, 'title' => '{headingText}']],

    ['Other', 'Divider', 'divider', 'minus', 'A horizontal line', []],
    ['Other', 'HTML', 'html', 'code', 'Custom HTML code (admins only)', [f('htmlCode', ['required' => true])], ['adminOnly' => true]],
];
// Nested fields of the blocks with child blocks: [name, child entry type, button label, min, max]
$nested = [
    'stepItems' => ['Steps', 'step', 'Add step', 1, null],
    'compareColumns' => ['Columns', 'compareColumn', 'Add column', 2, 2],
    'productItems' => ['Products', 'productItem', 'Add product', 1, null],
    'faqItems' => ['Questions', 'faqItem', 'Add question', 1, null],
];

/** Keep the field-layout element UIDs (where the entry content is stored) of fields that were already there. */
function keepUids(?FieldLayout $old, FieldLayout $new): void {
    if (!$old || !$old->id) return;
    $new->id = $old->id;
    $new->uid = $old->uid;
    $uids = [];
    foreach ($old->getCustomFieldElements() as $el) $uids[$el->getField()->uid] = $el->uid;
    foreach ($new->getCustomFieldElements() as $el) {
        if (isset($uids[$el->getField()->uid])) $el->uid = $uids[$el->getField()->uid];
    }
}

/** Create or update a block's entry type (no title field; the title format names the block in the editor) */
function blockType(array $row, array $extraFields = []): EntryType {
    [, $name, $handle, $icon, $description, $layoutFields] = $row;
    $opts = $row[6] ?? [];
    $entries = Craft::$app->getEntries();
    $et = $entries->getEntryTypeByHandle($handle);
    $isNew = !$et;
    $et ??= new EntryType();
    $et->name = $name;
    $et->handle = $handle;
    $et->icon = $icon;
    $et->description = $description;
    $et->hasTitleField = false;
    $et->titleFormat = $opts['title'] ?? null;
    $et->showSlugField = false;
    $elements = array_merge($layoutFields, $extraFields);
    if ($opts['adminOnly'] ?? false) {
        // HTML block: its code field is only shown to admins
        $condition = User::createCondition();
        $rule = new AdminConditionRule();
        $rule->value = true;
        $condition->addConditionRule($rule);
        foreach ($elements as $el) $el->setUserCondition($condition);
    }
    $layout = layout(['Content' => $elements]);
    keepUids($isNew ? null : $et->getFieldLayout(), $layout);
    $et->setFieldLayout($layout);
    $entries->saveEntryType($et) || fail("entry type $handle", $et);
    say(sprintf('  %s %-16s %s', $isNew ? '+' : '~', $handle, $row[0]));
    return $et;
}

$types = [];
// 1. child blocks and blocks without children
foreach ($catalog as $row) {
    if (!isset(($row[6] ?? [])['children'])) $types[$row[2]] = blockType($row);
}
// 2. the nested fields that hold the child blocks
foreach ($nested as $handle => [$name, $childType, $button, $min, $max]) {
    $nf = $fields->getFieldByHandle($handle) ?? new Matrix(['handle' => $handle]);
    $nf->name = $name;
    $nf->viewMode = Matrix::VIEW_MODE_BLOCKS;
    $nf->createButtonLabel = $button;
    $nf->minEntries = $min;
    $nf->maxEntries = $max;
    $nf->setEntryTypes([$types[$childType]]);
    $fields->saveField($nf) || fail("field $handle", $nf);
}
// 3. the blocks with children
foreach ($catalog as $row) {
    if ($child = ($row[6] ?? [])['children'] ?? null) {
        $types[$row[2]] = blockType($row, [f($child, ['required' => true])]);
    }
}
// 4. the Article Body field: the top-level blocks, in groups
$builder = $fields->getFieldByHandle($builderHandle);
$builder = $builder instanceof Matrix ? $builder : new Matrix(['handle' => $builderHandle]);
$builder->name = 'Article Body';
$builder->instructions = 'Build the article from blocks: Heading, Rich Text, Image, FAQ… Click a block’s title bar to collapse it, drag it to '
    . 'reorder, use its ⋯ menu to copy, paste or duplicate. H2 Heading blocks make the “On this page” list automatically.';
$builder->viewMode = Matrix::VIEW_MODE_BLOCKS;
$builder->createButtonLabel = 'Add block';
$builder->setEntryTypes(array_values(array_map(
    fn($row) => ['uid' => $types[$row[2]]->uid, 'group' => $row[0]],
    array_filter($catalog, fn($row) => !(($row[6] ?? [])['child'] ?? false)),
)));
$fields->saveField($builder) || fail("field $builderHandle", $builder);
say("  $builderHandle saved (" . count($builder->getEntryTypes()) . ' block types; ' . count($nested) . ' nested fields)');

// ---------------------------------------------------------------------------------------------------------------
say('Layouts');
$postType = $entries->getEntryTypeByHandle('blogPost');
if (!$postType->getFieldLayout()->getFieldByHandle($builderHandle)) {
    $layout = $postType->getFieldLayout();
    $tab = $layout->getTabs()[0];
    $tab->setElements(array_merge($tab->getElements(), [new CustomField($builder)]));
    $postType->setFieldLayout($layout);
    $entries->saveEntryType($postType) || fail('entry type blogPost', $postType);
    say("  ~ blogPost: added $builderHandle");
}
addTab($entries->getEntryTypeByHandle('blogPost'), 'Settings', [$catField, $tagField, $relatedField]);
$seo = array_map(fn($h) => $fields->getFieldByHandle($h), ['seoTitle', 'seoDescription', 'socialImage', 'seoNoIndex']);
foreach (['blogPost', 'blogPage', 'blogCategory'] as $handle) {
    addTab($entries->getEntryTypeByHandle($handle), 'SEO', $seo);
}

saveProjectConfig();
