<?php

namespace Translation\Contracts;

use Genesis\Repositories\Contracts\ActiveContract;
use Genesis\Repositories\Contracts\CrudContract;
use Genesis\Repositories\Contracts\SlugContract;
use Genesis\Repositories\Contracts\SoftDeleteableContract;

interface LanguageContract extends CrudContract, SlugContract, SoftDeleteableContract, ActiveContract
{
}
