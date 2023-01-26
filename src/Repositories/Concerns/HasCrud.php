<?php

namespace Translation\Repositories\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Translation\Contracts\LanguageContract;
use Translation\Contracts\TranslationContract;

trait HasCrud
{
    /**
     * Find a record by ID.
     *
     * @param mixed $id
     * @param array $with
     * @param mixed $select
     *
     * @return mixed
     */
    public function findById($id, array $with = [], $select = ['*']): mixed
    {
        return $this->model->with($with)->find($id, $select);
    }

    /**
     * Find a record by ID or Fail.
     *
     * @param mixed $id
     * @param array $with
     * @param mixed $select
     *
     * @return mixed
     */
    public function findByIdOrFail($id, array $with = [], $select = ['*']): mixed
    {
        return $this->model->with($with)->findOrFail($id, $select);
    }

    /**
     * Add a new record.
     *
     * @param array $attributes
     * @param bool  $forceFill
     *
     * @return mixed
     */
    public function add(array $attributes = [], bool $forceFill = false): mixed
    {
        $model = $this->model->newInstance();

        if ($translateables = $this->model->translatableAttributes()) {
            $locales = $this->getLocales();
            $tansRepository = $this->getTranslationRepository();
            foreach ($translateables as $item) {
                if (isset($attributes[$item])) {
                    $rawitem = $item.Str::random(20);
                    $empty = true;
                    foreach ($locales as $locale) {
                        if ($text = ($attributes[$item][$locale] ?? null)) {
                            $tansRepository->updateTranslation(
                                $locale,
                                $rawitem,
                                $text,
                                $model->getTranslationGroup()
                            );
                            $empty = false;
                        }
                    }
                    $attributes[$item] = $empty ? null : $rawitem;
                }
            }
        }

        if ($forceFill) {
            $model->forceFill($attributes);
        } else {
            $model->fill($attributes);
        }

        if ($model->ignoreSaveEvent()->save()) {
            return $model;
        }

        return false;
    }

    /**
     * Edit a specific record.
     *
     * @param mixed $id
     * @param array $attributes
     * @param bool  $forceFill
     *
     * @return mixed
     */
    public function edit($id, array $attributes = [], bool $forceFill = false): mixed
    {
        $model = $this->model->findOrFail($id);
        if ($translateables = $this->model->translatableAttributes()) {
            $locales = $this->getLocales();
            $tansRepository = $this->getTranslationRepository();
            foreach ($translateables as $item) {
                if (isset($attributes[$item])) {
                    $rawitem = $model->{'raw'.$item} ?: $item.Str::random(20);
                    $empty = true;
                    foreach ($locales as $locale) {
                        $text = $attributes[$item][$locale] ?? null;
                        if ($text) {
                            $tansRepository->updateTranslation(
                                $locale,
                                $rawitem,
                                $text,
                                $model->getTranslationGroup()
                            );
                            $empty = false;
                        } else {
                            $tansRepository->deleteByCode(
                                $rawitem,
                                $model->getTranslationGroup(),
                                $locale
                            );
                        }
                    }
                    $attributes[$item] = $empty ? null : $rawitem;
                }
            }
        }

        if ($forceFill) {
            $model->forceFill($attributes);
        } else {
            $model->fill($attributes);
        }

        if ($model->ignoreSaveEvent()->save()) {
            return $model;
        }

        return false;
    }

    /**
     * Delete a specific record.
     *
     * @param mixed $ids
     *
     * @return int
     */
    public function delete($ids): int
    {
        return $this->model->destroy($ids);
    }

    /**
     * Delete all recrods.
     *
     * @return int
     */
    public function deleteAll(): int
    {
        return $this->model->newQuery()->delete();
    }

    /**
     * Retrieve all records.
     *
     * @param array $with
     * @param int   $perPage
     * @param mixed $select
     * @param array $sort
     *
     * @return Collection
     */
    public function all(array $with = [], int $perPage = 0, $select = ['*'], array $sort = ['created_at' => 'desc']): Collection
    {
        $results = $this->model->with($with);

        foreach ($sort as $column => $order) {
            $results->orderBy($column, $order);
        }

        return $perPage ? $results->paginate($perPage, $select) : $results->get($select);
    }

    /**
     * Get locales.
     *
     * @return array
     */
    protected function getLocales()
    {
        return resolve(LanguageContract::class)->all()->pluck('locale');
    }

    /**
     * Default locale.
     *
     * @return void
     */
    protected function defaultLocale()
    {
        return Config::get('app.locale');
    }

    /**
     * Get Translation repository.
     *
     * @return TranslationContract
     */
    protected function getTranslationRepository()
    {
        return resolve(TranslationContract::class);
    }
}
