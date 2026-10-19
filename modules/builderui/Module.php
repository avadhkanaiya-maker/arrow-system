<?php
namespace modules\builderui;

use Craft;
use craft\web\View;
use yii\base\Event;

/**
 * Control panel touches for the page builders (Matrix fields: the blog's Article Body, the products' Page Builder), so adding blocks feels like Elementor's widget panel:
 * the "Add block" menu (which has Craft's own search box and groups) is shown as larger bordered icon tiles in 4 columns;
 * in the blog / product / industry builders the blocks start collapsed and an "Outline" navigator lists them (resources/navigator.*).
 * Only adds CSS + JS to control panel pages (resources/).
 */
class Module extends \yii\base\Module
{
    public function init(): void
    {
        // Lets Yii resolve the module's (empty) controller path, so console commands like `craft help` work
        Craft::setAlias('@modules/builderui', __DIR__);
        parent::init();

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
}
