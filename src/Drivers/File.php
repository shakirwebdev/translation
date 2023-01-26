<?php

namespace Translation\Drivers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Translation\Loader as LaravelLoader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Translation\Contracts\TranslationContract;
use Translation\Contracts\TranslationLoaderContract;

class File extends FileLoader implements LaravelLoader, TranslationLoaderContract
{
    /**
     * The translation repository.
     *
     * @var string
     */
    protected $translation;

    /**
     * Create new File instance.
     *
     * @param TranslationContract $trans
     * @param Filesystem          $files
     * @param string              $path
     */
    public function __construct(TranslationContract $trans, Filesystem $files, $path)
    {
        $this->translation = $trans;
        parent::__construct($files, $path);
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
        $content = $this->getFileContent($locale);
        $translations = json_decode($content, true);
        $path = $this->getTranslationPath($locale);
        if (empty($translations)) {
            $this->files->put($path, json_encode([]));
            chmod($path, 0666);
        }

        $translations[$code] = $text;

        return $this->files->put($path, json_encode($translations));
    }

    /**
     * Get translation path.
     *
     * @param string $locale
     *
     * @return string
     */
    public function getTranslationPath($locale)
    {
        return $this->path.DIRECTORY_SEPARATOR.$locale.'.json';
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
     * Get translation text.
     *
     * @param string $locale
     * @param string $item
     */
    public function getTranslation($locale, $item)
    {
        $content = $this->getFileContent($locale);
        if ($content) {
            $translations = json_decode($content, true);
            if (isset($translations[$item])) {
                return $translations[$item];
            }
        }

        return '';
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
        $path = $this->getTranslationPath($locale);
        $this->files->put($path, json_encode($translations));
    }

    /**
     * Get file content.
     *
     * @param string $locale
     *
     * @return string
     */
    protected function getFileContent($locale)
    {
        $path = $this->getTranslationPath($locale);
        if (!$this->files->exists($path)) {
            if (false === $this->files->put($path, json_encode([]))) {
                throw new FileNotFoundException();
            }
        }

        return $this->files->get($path);
    }
}
