<?php

namespace Bicycle\FilesManager\Contracts\Validators;

use Bicycle\FilesManager\Contracts\Validator;

interface ExtensionValidator extends Validator
{
    /**
     * @return array|null
     */
    public function getExtensions();
}
