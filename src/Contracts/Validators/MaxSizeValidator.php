<?php

namespace Solbeg\FilesManager\Contracts\Validators;

use Solbeg\FilesManager\Contracts\Validator;

interface MaxSizeValidator extends Validator
{
    /**
     * @param boolean $formatted
     * @return integer|string
     */
    public function getMaxSize($formatted = false);
}
