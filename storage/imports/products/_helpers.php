<?php
/** Shared by the products structure scripts (01_structure.php, 04_page_builder.php). Require after ../blog/_bootstrap.php. */

use craft\models\EntryType;

/** A block entry type (no title field) with the given layout elements; an existing one is returned as is */
function blockType(string $name, string $handle, string $icon, array $elements, ?string $titleFormat = null, ?string $description = null): EntryType {
    $entries = Craft::$app->getEntries();
    $et = $entries->getEntryTypeByHandle($handle);
    if ($et) return $et;
    $et = new EntryType(['name' => $name, 'handle' => $handle, 'icon' => $icon, 'hasTitleField' => false, 'titleFormat' => $titleFormat,
        'showSlugField' => false, 'description' => $description]);
    $et->setFieldLayout(layout(['Content' => $elements]));
    $entries->saveEntryType($et) || fail("entry type $handle", $et);
    say("  + entry type $handle");
    return $et;
}
