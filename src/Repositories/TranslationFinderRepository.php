<?php

namespace Translation\Repositories;

use Carbon\Carbon;
use Genesis\Repositories\Repository;
use Translation\Contracts\TranslationFinderContract;
use Translation\Models\Language;
use Translation\Models\LanguageTranslation;

class TranslationFinderRepository extends Repository implements TranslationFinderContract
{
    /**
     * The language model.
     *
     * @var unknown
     */
    protected $language;

    /**
     * The files system.
     *
     * @var unknown
     */
    protected $fs;

    /**
     * The cache file.
     *
     * @var string
     */
    protected $cacheFile = '.translation.cache';

    /**
     * The translation loader.
     *
     * @var unknown
     */
    protected $loader;

    /**
     * The default locale.
     *
     * @var string
     */
    protected $defaultLocale = 'en';

    /**
     * The insert limit.
     *
     * @var int
     */
    protected $insertLimit = 500;

    /**
     * The config.
     *
     * @var config
     */
    protected $config;

    /**
     * Class constructor.
     *
     * @param LanguageTranslation $model
     * @param Language            $language
     */
    public function __construct(LanguageTranslation $model, Language $language)
    {
        $this->language = $language;
        $this->fs = app('files');
        $this->config = app('config');
        $this->loader = app('translation.loader');
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     *
     * @see \Translation\Contracts\TranslationFinderContract::find()
     */
    public function find($cache = true, $path = null)
    {
        $keys = $this->findKeys($cache, $path);
        $count = count($keys);
        if ($count > 0) {
            $today = new Carbon();
            foreach (array_chunk($keys, $this->insertLimit) as $item) {
                $this->model->insert($item);
            }
        }

        $this->loader->synchronise($this->defaultLocale);

        return $count;
    }

    /**
     * {@inheritDoc}
     *
     * @see \Translation\Contracts\TranslationFinderContract::findGetKeys()
     */
    public function findGetKeys($cache = true, $path = null)
    {
        $keys = $this->findKeys($cache, $path);
        if (count($keys) > 0) {
            foreach (array_chunk($keys, $this->insertLimit) as $item) {
                $this->model->insert($item);
            }
        }

        $this->loader->synchronise($this->defaultLocale);

        return $keys;
    }

    /**
     * Get cached data.
     *
     * @return mixed
     */
    protected function getCache()
    {
        $fullpath = storage_path($this->cacheFile);
        if ($this->fs->exists($fullpath)) {
            return json_decode($this->fs->get($fullpath), true);
        }

        return [];
    }

    /**
     * Set cache.
     *
     * @param array $data
     *
     * @return bool
     */
    protected function setCache(array $data)
    {
        $fullpath = storage_path($this->cacheFile);

        return $this->fs->put($fullpath, json_encode($data));
    }

    /**
     * Find keys.
     *
     * @param bool   $cache
     * @param string $path
     *
     * @return array
     */
    protected function findKeys($cache = true, $path = null)
    {
        $directories = $this->config->get('translation.scan.directories');
        $exts = $this->config->get('translation.scan.ext');
        $basePath = base_path();

        $keys = [];
        $timestamp = (new Carbon())->toDateTimeString();
        $cacheData = $this->getCache();
        $lang = $this->language->where('locale', $this->defaultLocale)->firstOrFail();
        $directories = $path ? [$path] : $directories;
        foreach ($directories as $directory) {
            if (!$path) {
                $fullpath = $basePath.DIRECTORY_SEPARATOR.$directory;
            } else {
                $fullpath = $path;
            }

            foreach ($this->fs->allFiles($fullpath) as $file) {
                $lastModified = date('Y-m-d H:i:s', $file->getMTime());
                $extension = $file->getExtension();
                if (!in_array($extension, $exts) ||
                    ($cache && isset($cacheData[$fullpath]) && $cacheData[$fullpath] > $lastModified)) {
                    continue;
                }
                $contents = $this->fs->get($file->getRealPath());
                $regx = $this->config->get('translation.regex.'.$extension);
                preg_match_all($regx, $contents, $matches);
                if ($matches) {
                    // Remove first index, because its not correct
                    array_shift($matches);
                    $found = false;
                    foreach ($matches as $match) {
                        foreach ($match as $key) {
                            $keyGroup = null;
                            if (false !== strpos($key, '.')) {
                                $exploded = explode('.', str_replace('\\', '', $key));
                                $keyGroup = $exploded[0];
                                $item = trim(str_replace("{$keyGroup}.", '', $key));
                            } else {
                                $item = trim($key);
                            }

                            if (!$item) {
                                continue;
                            }

                            $uniqueKey = $item;
                            $text = str_replace('_', ' ', $item);
                            $prepare = $this->model->where('locale', $lang->locale)->where('item', $item);
                            if ($keyGroup) {
                                $prepare->where('group', $keyGroup);
                                $uniqueKey = $keyGroup.'.'.$uniqueKey;
                            }
                            $exist = $prepare->exists();

                            if (!$exist && !isset($keys[$uniqueKey])) {
                                $keys[$uniqueKey] = [
                                        'created_at' => $timestamp,
                                        'updated_at' => $timestamp,
                                        'group' => $keyGroup,
                                        'locale' => $lang->locale,
                                        'item' => $item,
                                        'text' => $text,
                                ];
                                $found = true;
                            } elseif ($exist && !isset($keys[$uniqueKey])) {
                                $cacheData[$file->getRealPath()] = $timestamp;
                            }
                        }
                    }
                    if ($found) {
                        $cacheData[$file->getRealPath()] = $timestamp;
                    }
                }
            }
        }

        // Store data to cache
        $this->setCache($cacheData);

        return $keys;
    }
}
