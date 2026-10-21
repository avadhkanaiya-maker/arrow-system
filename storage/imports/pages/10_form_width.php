<?php
/**
 * Pages: Form Width on the Form block's Section Settings (review 2026-10-21: "form is too small", the admin should choose):
 *   Wide (default, 1100 px) · Narrow (820 px, the width so far) · Full width (the page content width)
 * Applies when the Form block is a section of its own; inside Columns the column sets the width.
 * Idempotent. Back up first. Run from the project root: /opt/lampp/bin/php storage/imports/pages/10_form_width.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\fields\Dropdown;

$width = field(Dropdown::class, ['name' => 'Form Width', 'handle' => 'formWidth',
    'options' => options(['wide' => 'Wide', 'narrow' => 'Narrow', 'full' => 'Full width'], 'wide'),
    'instructions' => 'How wide the form card is when the form is a section of its own. Inside Columns the column sets the width.']);
addTab(Craft::$app->getEntries()->getEntryTypeByHandle('formBlock'), 'Section Settings', [$width]);
saveProjectConfig();
