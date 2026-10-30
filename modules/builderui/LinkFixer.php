<?php
namespace modules\builderui;

use Craft;
use craft\db\Query;
use craft\elements\Entry;
use craft\fields\Link;
use craft\fields\PlainText;
use craft\fields\Table;

/**
 * Links to our own pages written as addresses → links to the page's entry (client request 2026-10-29): a live-site
 * address (https://arrsys.com/…) or a root-relative one (/…, broken on the server's subfolder) whose page exists here
 * (the same URI, else config/redirects.php's target) becomes, in rich text and HTML blocks, CKEditor's reference tag
 * {entry:ID@site:url||fallback} (+ its ?query / #anchor), in Table cells' Markdown links likewise (templates render them
 * with |md|parseRefs), and in Link fields type Entry (label and settings kept). It then
 * opens the page on any site: local, the server's subfolder, production. Left alone: addresses with no page here, files,
 * mailto / tel / # links, and anything under an open draft (listed). Idempotent: a fixed link is no longer an address.
 * Used by storage/imports/pages/43_live_links_to_pages.php (the server ran it once, 2026-10-29, through a temporary
 * Utilities page, since removed).
 */
class LinkFixer
{
    private const LIVE = '~^https?://(?:www\.)?arrsys\.com(/[^?#]*)?([?#].*)?$~i';
    private const RELATIVE = '~^(/(?!/)[^?#]*)([?#].*)?$~';
    private const FILE = '~^/(media|uploads|wp-content|wp-admin|wp-json|feed|cpresources|actions|admin)\b|\.(pdf|jpe?g|png|webp|gif|svg|mp4|zip|docx?|xlsx?)$~i';

    private static array $redirects = [];
    private static array $entries = [];

    /**
     * @param bool $apply save the changes (else only list them)
     * @param int $limit when applying: stop after this many elements (0 = all), so a web request stays short
     * @return array{changes: array<int, array{page: string, url: ?string, field: string, from: string, to: string}>, skipped: string[], errors: string[], elements: int, remaining: bool}
     */
    public static function run(bool $apply, int $limit = 0): array
    {
        self::$redirects = [];
        foreach (require Craft::getAlias('@root/config/redirects.php') as $from => $rule) {
            is_array($rule) ? self::$redirects[strtolower(trim($rule['from'], '/'))] = trim($rule['to'], '/') : self::$redirects[strtolower(trim($from, '/'))] = trim($rule, '/');
        }
        $elements = Craft::$app->getElements();
        $ids = (new Query())->select('es.elementId')->from('{{%elements_sites}} es')->innerJoin('{{%elements}} e', 'e.id = es.elementId')
            ->where(['e.dateDeleted' => null, 'e.revisionId' => null, 'e.draftId' => null])
            ->andWhere(['or', ['like', 'es.content', 'arrsys.com'], ['like', 'es.content', 'href=\"/'], ['like', 'es.content', '"value":"/'], ['like', 'es.content', '](/']])
            ->column();

        $out = ['changes' => [], 'skipped' => [], 'errors' => [], 'elements' => 0, 'remaining' => false];
        foreach ($ids as $id) {
            $el = $elements->getElementById($id, null, null, ['status' => null]);
            if (!$el) continue;
            $top = $el;
            while (method_exists($top, 'getPrimaryOwner') && $top->getPrimaryOwner()) $top = $top->getPrimaryOwner();
            $page = $top instanceof Entry ? '/' . ($top->uri === '__home__' ? '' : $top->uri) : (method_exists($top, 'getUiLabel') ? $top->getUiLabel() : get_class($top));

            $set = [];
            $log = [];
            foreach ($el->getFieldLayout()?->getCustomFields() ?? [] as $f) {
                $v = $el->getFieldValue($f->handle);
                if ($f instanceof Link) {
                    $data = $v?->serialize();
                    if (!$data || ($data['type'] ?? '') !== 'url' || !($t = self::target((string)$data['value']))) continue;
                    [$e, $suffix] = $t;
                    if ($suffix !== '') continue;   // an entry link can't keep ?query / #anchor
                    $set[$f->handle] = array_merge($data, ['type' => 'entry', 'value' => $e->id]);
                    $log[] = [$f->name, $data['value'], $e];
                } elseif ($f instanceof \craft\ckeditor\Field || ($f instanceof PlainText && $f->handle === 'htmlCode')) {
                    $html = (string)$f->serializeValue($v, $el);
                    $new = preg_replace_callback('~href=(["\'])((?:https?://(?:www\.)?arrsys\.com)?/(?!/)[^"\']*|https?://(?:www\.)?arrsys\.com)\1~i',
                        function ($m) use (&$log, $f) {
                            $t = self::target(html_entity_decode($m[2]));
                            if (!$t) return $m[0];
                            [$e, $suffix] = $t;
                            $log[] = [$f->name, $m[2], $e];
                            return 'href=' . $m[1] . '{entry:' . $e->id . '@' . $e->siteId . ':url||' . $e->getUrl() . '}' . $suffix . $m[1];
                        }, $html);
                    if ($new !== $html) $set[$f->handle] = $new;
                } elseif ($f instanceof Table) {
                    // table cells are Markdown (templates: |md|parseRefs): [text](address) → [text]({entry:…})
                    $rows = $f->serializeValue($v, $el) ?? [];
                    $changedRows = false;
                    foreach ($rows as $r => $row) {
                        foreach ($row as $col => $cell) {
                            if (!is_string($cell) || !str_contains($cell, '](')) continue;
                            $new = preg_replace_callback('~\]\(((?:https?://(?:www\.)?arrsys\.com)?/(?!/)[^)\s]*|https?://(?:www\.)?arrsys\.com)\)~i', function ($m) use (&$log, $f) {
                                $t = self::target($m[1]);
                                if (!$t) return $m[0];
                                [$e, $suffix] = $t;
                                $log[] = [$f->name, $m[1], $e];
                                return '](' . '{entry:' . $e->id . '@' . $e->siteId . ':url||' . $e->getUrl() . '}' . $suffix . ')';
                            }, $cell);
                            if ($new !== $cell) { $rows[$r][$col] = $new; $changedRows = true; }
                        }
                    }
                    if ($changedRows) $set[$f->handle] = $rows;
                }
            }
            if (!$set) continue;
            if ($top->id && (new Query())->from('{{%drafts}}')->where(['canonicalId' => $top->getCanonicalId()])->exists()) {
                $out['skipped'][] = "$page: " . count($log) . ' link(s), the page has an open draft';
                continue;
            }
            if ($apply && $limit && $out['elements'] >= $limit) { $out['remaining'] = true; break; }
            if ($apply) {
                $el->setFieldValues($set);
                if (!$elements->saveElement($el)) { $out['errors'][] = "$page: " . implode('; ', $el->getFirstErrors()); continue; }
            }
            $out['elements']++;
            foreach ($log as [$field, $from, $e]) {
                $out['changes'][] = ['page' => $page, 'url' => $top instanceof Entry ? $top->getUrl() : null, 'field' => $field, 'from' => $from, 'to' => '/' . ($e->uri === '__home__' ? '' : $e->uri)];
            }
        }
        return $out;
    }

    /** An address → [the entry here, its ?query/#anchor], or null (not ours, a file, or no page here) */
    private static function target(string $href): ?array
    {
        if (preg_match(self::LIVE, $href, $m)) { $path = ($m[1] ?? '') ?: '/'; $suffix = $m[2] ?? ''; }
        elseif (preg_match(self::RELATIVE, $href, $m)) { $path = $m[1]; $suffix = $m[2] ?? ''; }
        else return null;
        if (preg_match(self::FILE, $path)) return null;
        $uri = trim(rawurldecode($path), '/');
        if (!array_key_exists($uri, self::$entries)) {
            $find = fn(string $u) => Entry::find()->uri($u === '' ? '__home__' : $u)->status('live')->one();
            $e = $find($uri);
            if (!$e && isset(self::$redirects[strtolower($uri)])) $e = $find(self::$redirects[strtolower($uri)]);
            self::$entries[$uri] = $e;
        }
        return self::$entries[$uri] ? [self::$entries[$uri], $suffix] : null;
    }
}
