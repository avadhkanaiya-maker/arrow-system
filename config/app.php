<?php
/**
 * Yii Application Config
 *
 * Edit this file at your own risk!
 *
 * The array returned by this file will get merged with
 * vendor/craftcms/cms/src/config/app.php and app.[web|console].php, when
 * Craft's bootstrap script is defining the configuration for the entire
 * application.
 *
 * You can define custom modules and system components, and even override the
 * built-in system components.
 *
 * If you want to modify the application config for *only* web requests or
 * *only* console requests, create an app.web.php or app.console.php file in
 * your config/ folder, alongside this one.
 *
 * Read more about application configuration:
 * @link https://craftcms.com/docs/5.x/reference/config/app.html
 */

use craft\helpers\App;

return [
    'id' => App::env('CRAFT_APP_ID') ?: 'CraftCMS',
    // builderui: control panel UI for the page builders (icon-grid "Add block" menu, Outline navigator)
    // forms: server-side rules for the Formie forms (valid phone numbers per country)
    'modules' => [
        'builderui' => \modules\builderui\Module::class,
        'forms' => \modules\forms\Module::class,
    ],
    'bootstrap' => ['builderui', 'forms'],
];
