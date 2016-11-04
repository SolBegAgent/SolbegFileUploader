<?php

namespace Solbeg\FilesManager\Contracts\Validators;

use Solbeg\FilesManager\Contracts\Validator;

interface ExtensionValidator extends Validator
{
    /**
     * @return array|null
     */
    public function getExtensions();
}
