<?php
/**
 * Shared bootstrap + helpers for the blog migration scripts (copied from the practice project's storage/imports/arrow).
 * Every helper is idempotent: if the field / entry type / section already exists, it is returned as-is,
 * so the scripts can be re-run safely. Run from the project root:
 *   /opt/lampp/bin/php storage/imports/blog/01_structure.php
 */
require dirname(__DIR__, 3) . '/bootstrap.php';
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';

use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;

function fail(string $what, $model): never {
    fwrite(STDERR, "FAILED $what: " . json_encode($model->getErrors()) . "\n");
    exit(1);
}

function say(string $msg): void { echo $msg . "\n"; }

function field(string $class, array $config, ?array $entryTypes = null) {
    $f = Craft::$app->getFields()->getFieldByHandle($config['handle']);
    if ($f) return $f;
    $f = new $class($config);
    if ($entryTypes) $f->setEntryTypes($entryTypes);
    Craft::$app->getFields()->saveField($f) || fail("field {$config['handle']}", $f);
    say("  + field {$config['handle']}");
    return $f;
}

/** Field layout from [tabName => [field|layoutElement, ...]]; a FieldInterface becomes a CustomField. */
function layout(array $tabs, string $type = Entry::class): FieldLayout {
    $layout = new FieldLayout(['type' => $type]);
    $out = [];
    foreach ($tabs as $name => $elements) {
        $tab = new FieldLayoutTab(['name' => $name, 'layout' => $layout]);
        $tab->setElements(array_map(fn($e) => $e instanceof \craft\base\FieldInterface ? new CustomField($e) : $e, $elements));
        $out[] = $tab;
    }
    $layout->setTabs($out);
    return $layout;
}

/**
 * @param array $tabs  [tabName => elements]; when $hasTitle the title field is prepended to the first tab
 */
function entryType(string $name, string $handle, array $tabs, array $opts = []) {
    $et = Craft::$app->getEntries()->getEntryTypeByHandle($handle);
    if ($et) return $et;
    $hasTitle = $opts['hasTitle'] ?? true;
    if ($hasTitle) {
        $first = array_key_first($tabs) ?? 'Content';
        $tabs[$first] = array_merge([new EntryTitleField(['label' => $opts['titleLabel'] ?? null])], $tabs[$first] ?? []);
    }
    $et = new EntryType([
        'name' => $name, 'handle' => $handle,
        'icon' => $opts['icon'] ?? null,
        'hasTitleField' => $hasTitle,
        'titleFormat' => $hasTitle ? null : ($opts['titleFormat'] ?? null),
        'showSlugField' => $opts['showSlug'] ?? false,
        'description' => $opts['description'] ?? null,
    ]);
    $et->setFieldLayout(layout($tabs ?: ['Content' => []]));
    Craft::$app->getEntries()->saveEntryType($et) || fail("entry type $handle", $et);
    say("  + entry type $handle");
    return $et;
}

function section(string $name, string $handle, string $type, array $entryTypes, ?string $uri = null, ?string $template = null, array $opts = []) {
    $s = Craft::$app->getEntries()->getSectionByHandle($handle);
    if ($s) return $s;
    $s = new Section([
        'name' => $name, 'handle' => $handle, 'type' => $type,
        'enableVersioning' => true,
        'maxLevels' => $opts['maxLevels'] ?? null,
        'siteSettings' => [new Section_SiteSettings([
            'siteId' => Craft::$app->getSites()->getPrimarySite()->id,
            'enabledByDefault' => true,
            'hasUrls' => $uri !== null,
            'uriFormat' => $uri,
            'template' => $template,
        ])],
    ]);
    $s->setEntryTypes($entryTypes);
    Craft::$app->getEntries()->saveSection($s) || fail("section $handle", $s);
    say("  + section $handle");
    return $s;
}

/** Append fields to an existing entry type's layout as a new tab (skips fields already in the layout). */
function addTab(EntryType $et, string $tabName, array $fields): void {
    $layout = $et->getFieldLayout();
    $fields = array_filter($fields, fn($f) => !$layout->getFieldByHandle($f->handle));
    if (!$fields) return;
    $tabs = $layout->getTabs();
    $existing = array_values(array_filter($tabs, fn($t) => $t->name === $tabName))[0] ?? null;
    if ($existing) {
        $existing->setElements(array_merge($existing->getElements(), array_map(fn($f) => new CustomField($f), $fields)));
    } else {
        $tab = new FieldLayoutTab(['name' => $tabName, 'layout' => $layout]);
        $tab->setElements(array_map(fn($f) => new CustomField($f), $fields));
        $tabs[] = $tab;
        $layout->setTabs($tabs);
    }
    $et->setFieldLayout($layout);
    Craft::$app->getEntries()->saveEntryType($et) || fail("entry type {$et->handle}", $et);
    say("  ~ {$et->handle}: added " . implode(', ', array_map(fn($f) => $f->handle, $fields)) . " to '$tabName'");
}

/** Dropdown options from [value => label]; first one is the default. */
function options(array $opts, ?string $default = null): array {
    $default ??= array_key_first($opts);
    return array_map(fn($v, $l) => ['label' => $l, 'value' => (string)$v, 'default' => (string)$v === (string)$default], array_keys($opts), $opts);
}

/** Standalone scripts skip the end-of-request handler, so save project config (DB + YAML) explicitly. */
function saveProjectConfig(): void {
    Craft::$app->getProjectConfig()->flush();
    say('project config saved');
}
