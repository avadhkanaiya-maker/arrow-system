<?php
/**
 * General Configuration
 *
 * All of your system's general configuration settings go in here. You can see a
 * list of the available settings in vendor/craftcms/cms/src/config/GeneralConfig.php.
 *
 * @see \craft\config\GeneralConfig
 * @link https://craftcms.com/docs/5.x/reference/config/general.html
 */

use craft\config\GeneralConfig;
use craft\helpers\App;

return GeneralConfig::create()
    // Set the default week start day for date pickers (0 = Sunday, 1 = Monday, etc.)
    ->defaultWeekStartDay(1)
    // Prevent generated URLs from including "index.php"
    ->omitScriptNameInUrls()
    // Preload Single entries as Twig variables
    ->preloadSingles()
    // Prevent user enumeration attacks
    ->preventUserEnumeration()
    // Prevent the front-end Login page
    ->loginPath(false)
    // Enable the Twig sandbox for system messages, etc.
    ->enableTwigSandbox()
    // Own cookie names: browsers share cookies across localhost ports, so this site would otherwise
    // overwrite the practice project's (localhost:8080) CSRF and session cookies, and vice versa
    ->csrfTokenName('ARROW_STAGING_CSRF_TOKEN')
    ->phpSessionName('ArrowStagingSessionId')
    // Match the live arrsys.com URLs: page URLs end with "/" (e.g. /blog/, /some-post/), files don't
    ->addTrailingSlashesToUrls()
    // Paginated listings as on the live site: /blog/page/2/ (Craft's default would be /blog/p2)
    ->pageTrigger('page/')
    // Set the @webroot alias so the clear-caches command knows where to find CP resources
    ->aliases([
        '@webroot' => dirname(__DIR__) . '/web',
    ])
;
