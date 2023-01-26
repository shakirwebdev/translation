<?php

namespace Translation\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Translation\Models\LanguageTranslation;

trait Translatable
{
    protected $ignoreSaveEvent = false;
    protected $ignoreDeleteEvent = false;

    /**
     * Ignore delete event.
     *
     * @return self
     */
    public function ignoreDeleteEvent(): self
    {
        $this->ignoreDeleteEvent = true;

        return $this;
    }

    /**
     * Ignore save event.
     *
     * @return self
     */
    public function ignoreSaveEvent(): self
    {
        $this->ignoreSaveEvent = true;

        return $this;
    }

    /**
     * Check if need to ignore save event.
     *
     * @return bool
     */
    public function shouldIgnoreDeleteEvent(): bool
    {
        return $this->ignoreDeleteEvent;
    }

    /**
     * Check if need to ignore save event.
     *
     * @return bool
     */
    public function shouldIgnoreSaveEvent(): bool
    {
        return $this->ignoreSaveEvent;
    }

    /**
     *  Register Model observer.
     *
     *  @return void
     */
    public static function bootTranslatable()
    {
        static::observe(new TranslatableObserver());
    }

    /**
     * Handle dynamic calls to the object.
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function __call($method, $args)
    {
        $attribute = str_replace($this->getTransRelationSuffix(), '', $method);
        if ($this->isTranslatable($attribute)) {
            return $this->hasMany(LanguageTranslation::class, 'item', 'raw'.$attribute)->where('group', $this->getTranslationGroup());
        }

        return parent::__call($method, $args);
    }

    /**
     *  Hijack parent's getAttribute to get the translation of the given field instead of its value.
     *
     *  @param  string  $key  Attribute name
     * @param mixed $attribute
     *
     *  @return mixed
     */
    public function getAttribute($attribute)
    {
        // Return the raw value of a translatable attribute if requested
        if ($this->rawValueRequested($attribute)) {
            $rawAttribute = Str::snake(str_replace('raw', '', $attribute));

            return $this->attributes[$rawAttribute];
        }

        if ($this->isTranslationsAttribute($attribute)) {
            $attribute = str_replace('_translations', '', $attribute);

            return $this->{$attribute.$this->getTransRelationSuffix()}()->pluck('text', 'locale')->toArray();
        }

        // Return the translation for the given attribute if available
        if ($this->isTranslated($attribute) && $this->attributes[$attribute]) {
            return $this->translate($this->attributes[$attribute]);
        }
        // Return parent
        return parent::getAttribute($attribute);
    }

    /**
     * Check if translations attribute.
     *
     * @param string $attribute
     *
     * @return bool
     */
    protected function isTranslationsAttribute(string $attribute): bool
    {
        foreach ($this->translatableAttributes() as $name) {
            if ($attribute == $name.'_translations') {
                return true;
            }
        }

        return false;
    }

    /**
     *  Extend parent's attributesToArray so that _translation attributes do not appear in array, and translatable attributes are translated.
     *
     *  @return array
     */
    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();

        foreach ($this->translatableAttributes as $translateable) {
            if (isset($attributes[$translateable])) {
                $item = $attributes[$translateable];
                $attributes[$translateable] = $this->translate($item);
                if ($key = $this->getKeyAttribute($translateable)) {
                    $attributes[$key] = $item;
                }
            }
        }

        return $attributes;
    }

    /**
     * Get the translation key attribute to be appended.
     *
     * @param string $column
     *
     * @return string
     */
    public function getKeyAttribute($column)
    {
        return str_replace(
            $this->getPlaceholders(),
            $column,
            Config::get('translation.key')
        );
    }

    /**
     *  Check if the attribute being queried is the raw value of a translatable attribute.
     *
     *  @param  string $attribute
     *
     *  @return bool
     */
    public function rawValueRequested($attribute)
    {
        if (0 === strrpos($attribute, 'raw')) {
            $rawAttribute = Str::snake(str_replace('raw', '', $attribute));

            return $this->isTranslatable($rawAttribute);
        }

        return false;
    }

    /**
     * @param $attribute
     */
    public function getRawAttribute($attribute)
    {
        return Arr::get($this->attributes, $attribute, '');
    }

    /**
     *  Check if an attribute is translatable.
     *
     *  @return bool
     *
     * @param mixed $attribute
     */
    public function isTranslatable($attribute)
    {
        return in_array($attribute, $this->translatableAttributes);
    }

    /**
     *  Check if a translation exists for the given attribute.
     *
     *  @param  string $attribute
     *
     *  @return bool
     */
    public function isTranslated($attribute)
    {
        return $this->isTranslatable($attribute);
    }

    /**
     *  Return the translatable attributes array.
     *
     *  @return  array
     */
    public function translatableAttributes()
    {
        return $this->translatableAttributes;
    }

    /**
     * Get translation group.
     *
     * @return string
     */
    public function getTranslationGroup()
    {
        return 'model';
    }

    /**
     * Translate item.
     *
     * @param [type] $item
     *
     * @return void
     */
    protected function translate($item)
    {
        return __($this->getTranslationGroup().'.'.$item);
    }

    /**
     * Model relationship suffix.
     *
     * @return string
     */
    protected function getTransRelationSuffix()
    {
        return 'Translations';
    }

    /**
     * Get placeholders.
     *
     * @return array
     */
    protected function getPlaceholders()
    {
        return [':attribute'];
    }

    /**
     * Join to translation table.
     *
     * @param Builder     $query
     * @param string|null $column
     * @param string|null $alias
     * @param string|null $locale
     *
     * @return Builder
     */
    public function scopeJoinTranslation(Builder $query, ?string $column, ?string $alias = null, ?string $locale = null): Builder
    {
        $locale = $locale ?: Config::get('app.locale');
        $table = 'language_translations'.($alias ? " as $alias" : '');
        $alias = $alias ?: 'language_translations';

        return $query->leftJoin($table, function ($join) use ($column, $locale, $alias) {
            $join->on($this->qualifyColumn($column), '=', $alias.'.item');
            $join->where($alias.'.locale', $locale);
        });
    }
}
