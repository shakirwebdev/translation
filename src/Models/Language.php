<?php

namespace Translation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Language extends Model
{
    use SoftDeletes;

    protected $fillable = [
            'locale',
            'name',
    ];

    /**
     * This model's relation to translations.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function translations()
    {
        return $this->hasMany(LanguageTranslation::class, 'locale', 'locale');
    }

    /**
     * Listening to events.
     *
     * @throws Exception
     */
    protected static function boot()
    {
        parent::boot();
        static::deleted(function ($language) {
            $language->locale = $language->locale.'.deleted.'.$language->id;
            $language->name = $language->name.'.deleted.'.$language->id;
            $language->save();
        });
    }
}
