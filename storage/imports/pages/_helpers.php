<?php
/** Shared by the Pages design scripts (31_…, 33_…). Require after ../blog/_bootstrap.php. */

use craft\elements\Asset;
use craft\elements\Entry;

/** An image from a URL (a design's or live's) in Images › pages/; a file of that name already in Images is reused */
function pageImage(string $src, string $alt): int {
    $volume = Craft::$app->getVolumes()->getVolumeByHandle('images');
    $filename = basename(parse_url($src, PHP_URL_PATH));
    if ($asset = Asset::find()->volumeId($volume->id)->filename($filename)->one()) return $asset->id;
    $folder = Craft::$app->getAssets()->ensureFolderByFullPathAndVolume('pages', $volume, false);
    $bytes = @file_get_contents($src, false, stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0 (Arrow Systems staging migration)\r\n", 'timeout' => 90]]))
        ?: exit("could not read $src\n");
    $tmp = Craft::$app->getPath()->getTempPath() . '/' . uniqid('page') . '-' . $filename;
    file_put_contents($tmp, $bytes);
    $asset = new Asset(['tempFilePath' => $tmp, 'filename' => $filename, 'volumeId' => $volume->id, 'newFolderId' => $folder->id]);
    $asset->alt = $alt;
    $asset->setScenario(Asset::SCENARIO_CREATE);
    Craft::$app->getElements()->saveElement($asset) || fail("asset $filename", $asset);
    return $asset->id;
}

/** A design link as a Link field value: tel:, #anchor, or a live URL → the entry here with that URI, else the live URL */
function designLink(string $href, string $label): array {
    if (str_starts_with($href, 'tel:')) return ['type' => 'tel', 'value' => substr($href, 4), 'label' => $label];
    if (str_starts_with($href, '#')) return ['type' => 'url', 'value' => $href, 'label' => $label];
    $uri = trim((string)parse_url($href, PHP_URL_PATH), '/');
    $entry = $uri !== '' ? Entry::find()->uri($uri)->one() : null;
    return $entry ? ['type' => 'entry', 'value' => $entry->id, 'label' => $label] : ['type' => 'url', 'value' => $href, 'label' => $label];
}

/** Nested entries field value from [[entry type, fields], …] */
function nested(array $items, string $prefix): array {
    $out = ['sortOrder' => [], 'entries' => []];
    foreach (array_values($items) as $i => [$type, $fields]) {
        $out['sortOrder'][] = "$prefix$i";
        $out['entries']["$prefix$i"] = ['type' => $type, 'fields' => array_filter($fields, fn($v) => $v !== null)];
    }
    return $out;
}
