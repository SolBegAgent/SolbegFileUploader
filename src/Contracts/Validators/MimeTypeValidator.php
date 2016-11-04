<?php

namespace Solbeg\FilesManager\Contracts\Validators;

use Solbeg\FilesManager\Contracts\Validator;

interface MimeTypeValidator extends Validator
{
    /**
     * @return array|null
     */
    public function getTypes();
}
