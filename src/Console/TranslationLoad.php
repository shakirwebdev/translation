<?php

namespace Translation\Console;

use Illuminate\Console\Command;
use Translation\Contracts\LanguageContract;
use Translation\Contracts\TranslationContract;

class TranslationLoad extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'translation:load';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load translation from language files.';

    /**
     * The language.
     *
     * @var LanguageContract
     */
    protected $language;

    /**
     * The translation repository.
     *
     * @var TranslationContract
     */
    protected $trans;

    /**
     * The file system.
     *
     * @var FileSystem
     */
    protected $files;

    /**
     * The default locale.
     *
     * @var string
     */
    protected $defaultLocale;

    /**
     * Available locales.
     *
     * @var array
     */
    protected $availableLocales = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(LanguageContract $language, TranslationContract $trans)
    {
        $this->trans = $trans;
        $this->language = $language;
        $this->files = app('files');
        $this->defaultLocale = config('app.locale');
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->availableLocales = $this->language->all()
                                    ->pluck('locale')
                                    ->toArray();
        $path = base_path('lang');
        $this->loadLocaleDirectories($path);
        $this->info('Laravel default translation loaded succesfully.');
    }

    /**
     *  Loads all locale directories in the given path (/en, /es, /fr) as long as the locale corresponds to a language in the database.
     *  If a vendor directory is found not inside another vendor directory, the files within it will be loaded with the corresponding namespace.
     *
     *  @param  string  $path           Full path to the root directory of the locale directories. Usually /path/to/laravel/resources/lang
     *
     *  @return void
     */
    public function loadLocaleDirectories($path)
    {
        $directories = $this->files->directories($path);
        foreach ($directories as $directory) {
            $locale = basename($directory);
            if (in_array($locale, $this->availableLocales)) {
                $this->loadDirectory($directory, $locale);
            }
            if ('vendor' === $locale) {
                $this->loadVendor($directory);
            }

            if ('default' === $locale) {
                $this->loadDirectory($directory, null);
            }
        }
    }

    /**
     *  Load all vendor overriden localization packages. Calls loadLocaleDirectories with the appropriate namespace.
     *
     *  @param  string  $path   path to vendor locale root, usually /path/to/laravel/resources/lang/vendor
     *
     *  @see    http://laravel.com/docs/5.1/localization#overriding-vendor-language-files
     *
     *  @return void
     */
    public function loadVendor($path)
    {
        $directories = $this->files->directories($path);
        foreach ($directories as $directory) {
            $namespace = basename($directory);
            $this->loadLocaleDirectories($directory, $namespace);
        }
    }

    /**
     *  Load all files inside a locale directory and its subdirectories.
     *
     *  @param  string  $path       Path to locale root. Ex: /path/to/laravel/resources/lang/en
     *  @param  string  $locale     locale to apply when loading the localization files
     *  @param  string  $namespace  Namespace to apply when loading the localization files ('*' by default, or the vendor package name if not)
     *  @param  string  $group      When loading from a subdirectory, the subdirectory's name must be prepended. For example: trans('subdir/file.entry').
     *
     *  @return void
     */
    public function loadDirectory($path, $locale)
    {
        // Load all files inside subdirectories:
        $directories = $this->files->directories($path);
        foreach ($directories as $directory) {
            $this->loadDirectory($directory, $locale);
        }

        // Load all files in root:
        $files = $this->files->files($path);
        foreach ($files as $file) {
            $this->loadFile($file, $locale);
        }
    }

    /**
     *  Loads the given file into the database.
     *
     *  @param  string  $path           Full path to the localization file. For example: /path/to/laravel/resources/lang/en/auth.php
     *  @param  string  $locale
     * @param mixed $file
     *
     *  @return void
     */
    public function loadFile($file, $locale)
    {
        $group = basename($file, '.php');
        if (is_null($locale)) {
            if (in_array($group, $this->availableLocales)) {
                $locale = $group;
                $group = null;
            } else {
                $locale = $this->defaultLocale;
            }
        }

        $translations = $this->files->getRequire($file);
        $this->trans->loadArray($translations, $locale, $group);
    }
}
