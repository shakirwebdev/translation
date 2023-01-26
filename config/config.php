<?php

use Illuminate\Support\Str;

return [
    /*
     |--------------------------------------------------------------------------
     | Repositories contract bindings
     | When overriding, just update the concrete class.
     |--------------------------------------------------------------------------
     */
    'bindings' => [
        'Translation\Contracts\LanguageContract' => 'Translation\Repositories\LanguageRepository',
        'Translation\Contracts\TranslationContract' => 'Translation\Repositories\TranslationRepository',
        'Translation\Contracts\TranslationFinderContract' => 'Translation\Repositories\TranslationFinderRepository',
        'Translation\Contracts\TranslationJsonContract' => 'Translation\Repositories\TranslationJsonRepository',
    ],

    /*
     |--------------------------------------------------------------------------
     | Scan
     |--------------------------------------------------------------------------
     |
     | Here you can configure what are the directories and files to scan
     |
     */

     'scan' => [
        'directories' => [
            'app',
        ],
        'ext' => ['php', 'js'],
     ],

    /*
     |--------------------------------------------------------------------------
     | Cache
     |--------------------------------------------------------------------------
     |
     | Here are the possible config for caching.
     | Caching is very important for boosting performance when finding keys
     |
     */

    'cache' => true,

    /*
     |--------------------------------------------------------------------------
     | Regex
     |--------------------------------------------------------------------------
     |
     | Here you can change the regex for finding the keys
     |
     */

    'regex' => [
         // Regex parses this format __('group.text') or __("group.text")
         // Note that . is a delimter, that means "trans" is the group and "translation" is the text
        'php' => '/__\([\"|\']([a-zA-Z0-9.: <>%&=!?\-#\/[\]\(_)\"\\\']+)[\"|\']/',

        // Regex parses this format $t('group.text') or $t("group.text")
         // Note that . is a delimter, that means "trans" is the group and "translation" is the text
        'js' => '/\$t\([\"|\']([a-zA-Z0-9.: <>%&=!?\-#\/[\]\(_)\"\\\']+)[\"|\']/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Translation Driver
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default driver for tranlation.
    | By default it'll be pulling from file which is stored in resources/lang
    |
    */

    'default' => env('TRANSLATION_DRIVER', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Translation Drivers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the driver for the translation
    |
    | Supported Drivers: "file", "cache", "database"
    |
    */

    'drivers' => [
        'file' => [],
        'cache' => [
            'ttl' => 2628000,
            'prefix' => Str::slug(env('APP_NAME', 'laravel'), '_').'_'.env('APP_ENV', 'local'), '_'.'_translation',
        ],
        'database' => [],
    ],
];
