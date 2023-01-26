<?php

namespace Translation\Models\Concerns;

use Illuminate\Support\Str;
use Translation\Contracts\TranslationContract;

class TranslatableObserver
{
    /**
     *  Save translations when model is saved.
     *
     *  @param  Model $model
     *
     *  @return void
     */
    public function saving($model)
    {
        if ($model->shouldIgnoreSaveEvent()) {
            return;
        }

        $translationRepository = resolve(TranslationContract::class);
        foreach ($model->translatableAttributes() as $attribute) {
            // If the value of the translatable attribute has changed:
            if ($model->isDirty($attribute)) {
                $item = trim($model->getRawAttribute($attribute));
                $text = $model->{$attribute};
                if ($item) {
                    if (!$model->exists) {
                        $text = $item;
                        $item = $attribute.Str::random(20);
                        $model->setAttribute($attribute, $item);
                    }
                    $translationRepository->updateDefaultByCode(
                        $item,
                        $text,
                        $model->getTranslationGroup(),
                    );
                } else {
                    $translationRepository->deleteByCode($item);
                }
            }
        }
    }

    /*
     *  Delete translations when model is deleted.
     *
     *  @param  Model $model
     *  @return void
     */
    public function deleted($model)
    {
        if ($model->shouldIgnoreDeleteEvent()) {
            return;
        }

        $translationRepository = resolve(TranslationContract::class);
        foreach ($model->translatableAttributes() as $attribute) {
            if ($item = $model->{'raw'.$attribute}) {
                $translationRepository->deleteByCode($item);
            }
        }
    }
}
