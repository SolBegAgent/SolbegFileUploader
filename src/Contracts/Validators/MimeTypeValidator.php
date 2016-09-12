<?php

namespace Bicycle\FilesManager\Contracts\Validators;

use Bicycle\FilesManager\Contracts\Validator;

interface MimeTypeValidator extends Validator
{
    /**
     * @return array|null
     */
    public function getTypes();
}
