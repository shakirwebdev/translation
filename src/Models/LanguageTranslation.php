<?php

namespace Translation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class LanguageTranslation extends Model
{
    protected $fillable = [
            'locale',
            'group',
            'item',
            'text',
    ];

    /**
     * This model's relation to language.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function language()
    {
        return $this->belongsTo(Language::class, 'locale', 'locale');
    }

    /**
     * Get translation locale.
     *
     * @return string
     */
    public function getTranslations()
    {
        return $this->newQuery()->whereItem($this->item)->pluck('text', 'locale');
    }

    /**
     * Listening to events.
     *
     * @throws Exception
     */
    protected static function boot()
    {
        parent::boot();

        if ('database' != Config::get('translation.default')) {
            static::created(function ($translation) {
                $loader = app('translation.loader');
                $item = $translation->group
                        ? $translation->group.'.'.$translation->item
                        : $translation->item;
                $loader->updateTranslation($translation->locale, $item, $translation->text);
            });

            static::updated(function ($translation) {
                $loader = app('translation.loader');
                $item = $translation->group
                        ? $translation->group.'.'.$translation->item
                        : $translation->item;
                $loader->updateTranslation($translation->locale, $item, $translation->text);
            });
        }
    }
}
