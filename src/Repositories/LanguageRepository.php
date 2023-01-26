<?php

namespace Translation\Repositories;

use Genesis\Repositories\Concerns\HasActive;
use Genesis\Repositories\Concerns\HasCrud;
use Genesis\Repositories\Concerns\HasSlug;
use Genesis\Repositories\Concerns\HasSoftDelete;
use Genesis\Repositories\Repository;
use Translation\Contracts\LanguageContract;
use Translation\Models\Language;

class LanguageRepository extends Repository implements LanguageContract
{
    use HasCrud;
    use HasSlug;
    use HasSoftDelete;
    use HasActive;

    /**
     * Class constructor.
     *
     * @param Language $model
     */
    public function __construct(Language $model)
    {
        $this->slug = 'locale';
        parent::__construct($model);
    }
}
