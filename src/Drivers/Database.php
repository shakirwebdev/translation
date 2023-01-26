<?php

namespace Translation\Drivers;

use Exception;
use Illuminate\Contracts\Translation\Loader as LaravelLoader;
use Translation\Contracts\TranslationContract;
use Translation\Contracts\TranslationLoaderContract;

class Database implements LaravelLoader, TranslationLoaderContract
{
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
     * The translation repository.
     *
     * @var TranslationContract
     */
    protected $translation;

    /**
     * Create a new file loader instance.
     *
     * @param \Illuminate\Filesystem\Filesystem $files
     * @param string                            $path
     *
     * @return void
     */
    public function __construct(TranslationContract $translation)
    {
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
        try {
            return $this->translation->loadSource($locale);
        } catch (Exception $e) {
            return [];
        }
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
        return $this->translation->updateTranslation($locale, $code, $text);
    }

    /**
     * Update json translation from database.
     *
     * @param string $locale
     */
    public function synchronise($locale = null)
    {
        return;
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
        return $this->translation->findByLangCode($locale, $item);
    }

    /**
     * Update translations.
     *
     * @param string $locale
     * @param array  $translations
     *
     * @return bool
     */
    public function updateTranslations($locale, $translations)
    {
        return;
    }
}
