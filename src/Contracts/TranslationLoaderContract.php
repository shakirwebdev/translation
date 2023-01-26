<?php

namespace Translation\Contracts;

interface TranslationLoaderContract
{
    /**
     * Insert or Update entry by translation code for the locale.
     *
     * @param string $locale
     * @param string $code
     * @param string $text
     *
     * @return bool
     */
    public function updateTranslation($locale, $code, $text);

    /**
     * Update json translation from database.
     *
     * @param string $locale
     */
    public function synchronise($locale = null);

    /**
     * Get translation path.
     *
     * @param string $locale
     */
    public function getTranslationPath($locale);

    /**
     * Get translation text.
     *
     * @param string $locale
     * @param string $item
     */
    public function getTranslation($locale, $item);

    /**
     * Update translations.
     *
     * @param string $locale
     * @param array  $translations
     *
     * @return bool
     */
    public function updateTranslations($locale, $translations);
}
