<?php

namespace Translation\Repositories;

use Genesis\Repositories\Concerns\HasCrud;
use Genesis\Repositories\Concerns\HasSlug;
use Genesis\Repositories\Concerns\HasSoftDelete;
use Genesis\Repositories\Repository;
use Illuminate\Support\Arr;
use Translation\Contracts\LanguageContract;
use Translation\Contracts\TranslationContract;
use Translation\Models\LanguageTranslation;

class TranslationRepository extends Repository implements TranslationContract
{
    use HasCrud;
    use HasSlug;
    use HasSoftDelete;

    /**
     * The default locale.
     *
     * @var string
     */
    protected $defaultLocale;

    /**
     *  Validation errors.
     *
     *  @var \Illuminate\Support\MessageBag
     */
    protected $errors;

    /**
     * The language repository.
     *
     * @var LanguageContract
     */
    protected $language;

    /**
     * Constructor.
     *
     * @param LanguageTranslation $model
     * @param Language            $language
     */
    public function __construct(LanguageTranslation $model, LanguageRepository $language)
    {
        $this->defaultLocale = config('app.locale', 'en');
        $this->slug = 'item';
        $this->language = $language;
        parent::__construct($model);
    }

    /**
     * Get locales from DB.
     *
     * @return collection
     *
     * @param mixed $active
     */
    public function getLocales($active = true)
    {
        return $active
                ? $this->language->allActive()
                : $this->language->all();
    }

    /**
     * {@inheritDoc}
     *
     * @see \Translation\Contracts\TranslationContract::updateDefaultByCode()
     */
    public function updateDefaultByCode($code, $text, $group = null)
    {
        $item = $code;
        $locale = $this->defaultLocale;
        $prepare = $this->model
                            ->whereLocale($locale)
                            ->whereItem($item);
        if ($group) {
            $prepare->whereGroup($group);
        }

        $translation = $prepare->first();
        if (!$translation) {
            return $this->add(compact('locale', 'item', 'text', 'group'));
        }

        return $this->edit($translation->id, compact('locale', 'item', 'text', 'group'));
    }

    /**
     * {@inheritDoc}
     *
     * @see \Translation\Contracts\TranslationContract::loadArray()
     */
    public function loadArray(array $lines, $locale, $group = null)
    {
        // Transform the lines into a flat dot array:
        $lines = Arr::dot($lines);
        foreach ($lines as $item => $text) {
            if (is_string($text)) {
                // Check if the entry exists in the database:
                $prepare = $this->model->whereLocale($locale)
                                    ->whereItem($item);
                if ($group) {
                    $prepare->where('group', $group);
                }

                $translation = $prepare->first();

                // If the translation already exists, we update the text:
                if ($translation) {
                    $translation->text = $text;
                    $translation->save();
                }
                // If no entry was found, create it:
                else {
                    $this->add(compact('locale', 'group', 'item', 'text'));
                }
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @see \Translation\Contracts\TranslationContract::loadSource()
     */
    public function loadSource($locale)
    {
        return $this->model
                    ->whereLocale($locale)
                    ->get()
                    ->keyBy(function ($translation) {
                        return $translation['group']
                                ? $translation['group'].'.'.$translation['item']
                                : $translation['item'];
                    })
                    ->map(function ($translation) {
                        return $translation['text'];
                    })
                    ->toArray();
    }

    /**
     * {@inheritDoc}
     *
     * @see \Translation\Contracts\TranslationContract::findByLangCode()
     */
    public function findByLangCode($locale, $code, $group = null)
    {
        $prepare = $this->model->whereLocale($locale)->whereItem($code);
        if ($group) {
            $prepare->where('group', $group);
        }

        return $prepare->first();
    }

    /**
     *  Returns the validations errors of the last action executed.
     *
     *  @return \Illuminate\Support\MessageBag
     */
    public function validationErrors()
    {
        return $this->errors;
    }

    /**
     * {@inheritDoc}
     *
     * @see \Translation\Contracts\TranslationContract::updateTranslation()
     */
    public function updateTranslation($locale, $item, $text, $group = null)
    {
        $translation = $this->findByLangCode($locale, $item, $group);
        if ($translation) {
            $translation->text = $text;
            if ($translation->save()) {
                return $translation;
            }
        } else {
            return $this->add(compact('locale', 'group', 'item', 'text'));
        }

        return false;
    }

    /**
     *  Validate the given attributes.
     *
     *  @param  array    $attributes
     *
     *  @return bool
     */
    protected function validate(array $attributes)
    {
        $table = $this->model->getTable();
        $locale = Arr::get($attributes, 'locale', '');
        $rules = [
                'locale' => 'required',
                'group' => '', // group may be empty
                'item' => "required|unique:{$table},item,NULL,id,locale,{$locale}",
                'text' => '', // Translations may be empty
                ];
        $validator = $this->app['validator']->make($attributes, $rules);
        if ($validator->fails()) {
            $this->errors = $validator->errors();

            return false;
        }

        return true;
    }

    /**
     * Delete translation by code
     *
     * @param string $item
     * @param string|null $group
     * @param string|null $locale
     * 
     * @return int
     */
    public function deleteByCode(string $item, ?string $group = null, ?string $locale = null)
    {
        $prepare = $this->model->whereItem($item);
        if ($group) {
            $prepare->whereGroup($group);
        }
        if ($locale) {
            $prepare->whereLocale($locale);
        }
        return $prepare->delete();
    }
}
