<?php

namespace Translation\Drivers;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Translation\Loader as LaravelLoader;
use Illuminate\Support\Facades\Config;
use Translation\Contracts\TranslationContract;
use Translation\Contracts\TranslationLoaderContract;

class Cache implements LaravelLoader, TranslationLoaderContract
{
    /**
     * The default path for the loader.
     *
     * @var string
     */
    protected $path;

    /**
     * All of the registered paths to JSON translation files.
     *
     * @var array
     */
    protected $jsonPaths = [];

    /**
     * All of the namespace hints.
     *
     * @var array
     */
    protected $hints = [];

    /**
     * The cache repository.
     *
     * @var CacheRepository
     */
    protected $cache;

    /**
     * The database driver.
     *
     * @var Database
     */
    protected $database;

    /**
     * The translation repository.
     *
     * @var TranslationContract
     */
    protected $translation;

    /**
     * Create new loader instance.
     *
     * @param TranslationContract $translation
     * @param CacheRepository     $cache
     * @param Database            $database
     */
    public function __construct(TranslationContract $translation, CacheRepository $cache, Database $database)
    {
        $this->cache = $cache;
        $this->database = $database;
        $this->translation = $translation;
    }

    /**
     * Load the messages for the given locale.
     *
     * @param string      $locale
     * @param string      $group
     * @param string|null $namespace
     *
     * @return array
     */
    public function load($locale, $group, $namespace = null)
    {
        $database = $this->database;

        return $this->cache->remember(
            $this->getKey($locale),
            $this->getTTL(),
            function () use ($locale, $database) {
                return $database->load($locale, '*');
            }
        );
    }

    /**
     * Add a new namespace to the loader.
     *
     * @param string $namespace
     * @param string $hint
     *
     * @return void
     */
    public function addNamespace($namespace, $hint)
    {
        $this->hints[$namespace] = $hint;
    }

    /**
     * Add a new JSON path to the loader.
     *
     * @param string $path
     *
     * @return void
     */
    public function addJsonPath($path)
    {
        $this->jsonPaths[] = $path;
    }

    /**
     * Get an array of all the registered namespaces.
     *
     * @return array
     */
    public function namespaces()
    {
        return $this->hints;
    }

    /**
     * Insert or Update entry by translation code for the locale.
     *
     * @param string $locale
     * @param string $code
     * @param string $text
     *
     * @return bool
     */
    public function updateTranslation($locale, $code, $text)
    {
        $translations = $this->load($locale, $code);
        if (isset($translations[$code])) {
            $translations[$code] = $text;
        } else {
            $translations = array_merge($translations, [
                $code => $text,
            ]);
        }

        $this->updateTranslations($locale, $translations);
    }

    /**
     * Update json translation from database.
     *
     * @param string $locale
     */
    public function synchronise($locale = null)
    {
        $locales = [];
        if (!$locale) {
            $locales = $this->translation->getLocales(false);
        } else {
            $locales = [$locale];
        }

        foreach ($locales as $locale) {
            $translations = $this->translation->loadSource($locale);
            if ($translations) {
                $this->updateTranslations($locale, $translations);
            }
        }

        return true;
    }

    /**
     * Get translation path.
     *
     * @param string $locale
     */
    public function getTranslationPath($locale)
    {
        return;
    }

    /**
     * Get translation text.
     *
     * @param string $locale
     * @param string $item
     */
    public function getTranslation($locale, $item)
    {
        $translations = $this->cache->get(
            $this->getKey($locale)
        );

        return isset($translations[$item]) ?? '';
    }

    /**
     * Update translations.
     *
     * @param string $locale
     * @param array  $translations
     *
     * @return mixed
     */
    public function updateTranslations($locale, $translations)
    {
        // @todo Forgetting is efficent than forgetting?
        $this->cache->forget(
            $key = $this->getKey($locale)
        );
        $ttl = $this->getTTL();

        return $this->cache->remember($key, $ttl, function () use ($translations) {
            return $translations;
        });
    }

    /**
     * Get cache key.
     *
     * @param string $locale
     *
     * @return string
     */
    protected function getKey($locale)
    {
        return Config::get('translation.drivers.cache.prefix').'_'.$locale;
    }

    /**
     * Get time to live.
     *
     * @return int
     */
    protected function getTTL()
    {
        return 60 * (Config::get('translation.drivers.cache.ttl') ?? 2628000);
    }
}
