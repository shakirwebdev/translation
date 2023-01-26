<?php

namespace Translation\Contracts;

interface TranslationFinderContract
{
    /**
     * Find translation keys from directory.
     *
     * @param bool   $cache
     * @param string $path
     *
     * @return bool
     */
    public function find($cache = true, $path = null);

    /**
     * Find translation keys from directory and return the translated items.
     *
     * @param bool   $cache
     * @param string $path
     *
     * @return array
     */
    public function findGetKeys($cache = true, $path = null);
}
