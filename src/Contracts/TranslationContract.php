<?php

namespace Translation\Contracts;

use Genesis\Repositories\Contracts\CrudContract;
use Genesis\Repositories\Contracts\SlugContract;
use Genesis\Repositories\Contracts\SoftDeleteableContract;

interface TranslationContract extends CrudContract, SlugContract, SoftDeleteableContract
{
    /**
     * Get locales from DB.
     *
     * @return collection
     *
     * @param mixed $active
     */
    public function getLocales($active = true);

    /**
     *  Insert or Update entry by translation code for the default locale.
     *
     *  @param  string  $code
     *  @param  string  $text
     *  @param string|null $group
     *
     *  @return bool
     */
    public function updateDefaultByCode($code, $text, $group = null);

    /**
     * Update translation.
     *
     * @param string      $locale
     * @param string      $item
     * @param string      $text
     * @param string|null $group
     */
    public function updateTranslation($locale, $item, $text, $group = null);

    /**
     *  Loads a localization array from a localization file into the database.
     *
     *  @param  array   $lines
     *  @param  string  $locale
     *  @param  string  $group
     *
     *  @return void
     */
    public function loadArray(array $lines, $locale, $group = null);

    /**
     *  Return all items formatted as if coming from a PHP language file.
     *
     *  @param  string $locale
     *
     *  @return array
     */
    public function loadSource($locale);

    /**
     *  Find a translation per locale, group and item values.
     *
     *  @param  string  $locale
     *  @param  string  $code
     *  @param  string  $group
     *
     *  @return Translation
     */
    public function findByLangCode($locale, $code, $group = null);

    /**
     *  Returns the validations errors of the last action executed.
     *
     *  @return \Illuminate\Support\MessageBag
     */
    public function validationErrors();

    /**
     * Delete translation by code
     *
     * @param string $item
     * @param string|null $group
     * @param string|null $locale
     * 
     * @return int
     */
    public function deleteByCode(string $item, ?string $group = null, ?string $locale = null);
}
