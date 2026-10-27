<?php
namespace modules\web;

use Craft;
use craft\web\Response;
use craft\web\View;
use yii\web\HttpException;

/**
 * The site's 404 and 403 pages (templates/404.twig, 403.twig) for every page request, also in dev mode:
 * - website: Craft's dev mode otherwise shows its developer error screen for every error;
 * - admin panel (e.g. a Reviewer opening Settings): Craft otherwise shows a bare "Unauthorized" box; here the same page,
 *   with a message for staff and "Back to Dashboard" (the layout gets inCp = true).
 * Other errors (500s …) and JSON requests keep Craft's handling (the developer screen in dev mode). If the page itself
 * fails, Craft's page is shown instead. Registered in config/app.web.php.
 */
class ErrorHandler extends \craft\web\ErrorHandler
{
    private bool $designedPage = false;

    protected function renderException($exception): void
    {
        $request = Craft::$app->has('request', true) ? Craft::$app->getRequest() : null;
        $designed = $exception instanceof HttpException && in_array($exception->statusCode, [403, 404], true)
            && $request && !$request->getAcceptsJson();

        if ($designed && $request->getIsCpRequest()) {
            try {
                $view = Craft::$app->getView();
                $view->setTemplateMode(View::TEMPLATE_MODE_SITE);   // the site's templates, Site Settings and menus
                $html = $view->renderPageTemplate((string)$exception->statusCode, ['inCp' => true]);
                $response = Craft::$app->getResponse();
                $response->clear();
                $response->format = Response::FORMAT_HTML;
                $response->content = $html;
                $response->setStatusCode($exception->statusCode);
                $response->send();
                return;
            } catch (\Throwable $e) {
                Craft::warning('Designed error page failed: ' . $e->getMessage(), __METHOD__);
                Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_CP);
            }
        }

        $this->designedPage = $designed && $request->getIsSiteRequest();
        parent::renderException($exception);
    }

    public function showExceptionDetails(): bool
    {
        return !$this->designedPage && parent::showExceptionDetails();
    }
}
