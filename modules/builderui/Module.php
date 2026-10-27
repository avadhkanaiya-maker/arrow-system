<?php
namespace modules\builderui;

use Craft;
use craft\base\Model;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\events\DefineRulesEvent;
use craft\events\ModelEvent;
use craft\events\ReplaceAssetEvent;
use craft\fields\Matrix;
use craft\helpers\Assets as AssetsHelper;
use craft\services\Assets;
use craft\web\View;
use verbb\navigation\events\RegisterNodeTypeEvent;
use verbb\navigation\nodetypes\Category as CategoryNode;
use verbb\navigation\nodetypes\Dynamic as DynamicNode;
use verbb\navigation\services\NodeTypes;
use yii\base\Event;
use yii\base\UserException;

/**
 * Control panel touches for the page builders (Matrix fields: the blog's Article Body, the products' Page Builder), so adding blocks feels like Elementor's widget panel:
 * the "Add block" menu (which has Craft's own search box and groups) is shown as larger bordered icon tiles in 4 columns;
 * in the blog / product / industry builders the blocks start collapsed and an "Outline" navigator lists them (resources/navigator.*).
 * Only adds CSS + JS to control panel pages (resources/).
 */
class Module extends \yii\base\Module
{
    private const MAX_VIDEO_BYTES = 5 * 1024 * 1024;

    public function init(): void
    {
        // Lets Yii resolve the module's (empty) controller path, so console commands like `craft help` work
        Craft::setAlias('@modules/builderui', __DIR__);
        parent::init();

        // Navigation: no Categories or Dynamic item types (unused: blog categories are entries; menus are built by hand), so
        // they're gone from every menu's Add panel and Settings (review 2026-10-23)
        Event::on(NodeTypes::class, NodeTypes::EVENT_REGISTER_NODE_TYPES, function(RegisterNodeTypeEvent $event) {
            $event->types = array_values(array_diff($event->types, [CategoryNode::class, DynamicNode::class]));
        });

        // Video uploads (the Video File field of hero slides, carousel slides and the One Stop section, or the Assets
        // screen): at most 5 MB each, so the autoplaying videos stay light (client request 2026-10-26)
        Event::on(Asset::class, Model::EVENT_DEFINE_RULES, function(DefineRulesEvent $event) {
            /** @var Asset $asset */
            $asset = $event->sender;
            $event->rules[] = ['tempFilePath', function() use ($asset) {
                if ($error = self::videoSizeError($asset->getFilename(), $asset->tempFilePath)) {
                    $asset->addError('tempFilePath', $error);
                }
            }, 'on' => [Asset::SCENARIO_CREATE]];
        });
        // Replace File: Craft ignores a failed save there and reports success, so the replace is stopped with the message
        Event::on(Assets::class, Assets::EVENT_BEFORE_REPLACE_ASSET, function(ReplaceAssetEvent $event) {
            if ($error = self::videoSizeError($event->filename, $event->replaceWith)) {
                throw new UserException($error);
            }
        });

        // Every new page starts with a Newsletter block in its page builder (it switches the newsletter band on; the
        // editor can delete it), like the pages that existed when the block was added (2026-10-26). "New entry" in the
        // control panel first saves an unpublished draft, so the block is there when the form opens.
        Event::on(Entry::class, Entry::EVENT_BEFORE_SAVE, function(ModelEvent $event) {
            /** @var Entry $entry */
            $entry = $event->sender;
            if ($entry->id || $entry->fieldId || $entry->duplicateOf || !$entry->getIsUnpublishedDraft()) {
                return;
            }
            foreach ($entry->getFieldLayout()?->getCustomFields() ?? [] as $field) {
                if ($field instanceof Matrix && in_array('newsletter', array_map(fn($t) => $t->handle, $field->getEntryTypes()), true)) {
                    $entry->setFieldValue($field->handle, ['sortOrder' => ['new1'], 'entries' => ['new1' => ['type' => 'newsletter']]]);
                }
            }
        });

        $request = Craft::$app->getRequest();
        if ($request->getIsConsoleRequest() || !$request->getIsCpRequest()) {
            return;
        }

        Event::on(View::class, View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE, function() {
            $view = Craft::$app->getView();
            $view->registerCss(file_get_contents(__DIR__ . '/resources/cp.css'));
            $view->registerJs(file_get_contents(__DIR__ . '/resources/cp.js'), View::POS_END);
            // page builders (blog, products, industries, pages): blocks collapsed by default + the "Outline" navigator in the sidebar
            $view->registerCss(file_get_contents(__DIR__ . '/resources/navigator.css'));
            $view->registerJs(file_get_contents(__DIR__ . '/resources/navigator.js'), View::POS_END);
        });
    }

    private static function videoSizeError(string $filename, string $path): ?string
    {
        if (AssetsHelper::getFileKindByExtension($filename) !== Asset::KIND_VIDEO || filesize($path) <= self::MAX_VIDEO_BYTES) {
            return null;
        }
        return sprintf('“%s” is %s. Videos can be at most 5 MB: please compress it and upload again.',
            $filename, Craft::$app->getFormatter()->asShortSize(filesize($path), 1));
    }
}
