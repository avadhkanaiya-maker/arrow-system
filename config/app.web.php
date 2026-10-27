<?php
/**
 * Web-only application config, merged over config/app.php for web requests.
 */
return [
    'components' => [
        // the site's 404 / 403 pages also in dev mode (modules/web/ErrorHandler.php)
        'errorHandler' => [
            'class' => \modules\web\ErrorHandler::class,
        ],
    ],
];
