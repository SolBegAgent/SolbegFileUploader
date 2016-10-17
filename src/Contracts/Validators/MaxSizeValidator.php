<?php

namespace Bicycle\FilesManager\Contracts\Validators;

use Bicycle\FilesManager\Contracts\Validator;

interface MaxSizeValidator extends Validator
{
    /**
     * @param boolean $formatted
     * @return integer|string
     */
    public function getMaxSize($formatted = false);
}
