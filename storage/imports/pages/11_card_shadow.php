<?php
/**
 * Pages: Card Shadow on the Card Grid block's Section Settings (review 2026-10-21):
 *   None (default, flat cards as before) · Soft · Strong
 * Cards with a link still lift on hover. Idempotent. Back up first.
 * Run from the project root: /opt/lampp/bin/php storage/imports/pages/11_card_shadow.php
 */
require dirname(__DIR__) . '/blog/_bootstrap.php';

use craft\fields\Dropdown;

$shadow = field(Dropdown::class, ['name' => 'Card Shadow', 'handle' => 'cardShadow',
    'options' => options(['none' => 'None', 'soft' => 'Soft', 'strong' => 'Strong'], 'none'),
    'instructions' => 'A shadow under every card in this grid. None = flat cards with a border.']);
addTab(Craft::$app->getEntries()->getEntryTypeByHandle('cardGrid'), 'Section Settings', [$shadow]);
saveProjectConfig();
