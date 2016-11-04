<?php

namespace Solbeg\FilesManager\Contracts\Validators;

use Solbeg\FilesManager\Contracts\Validator;

interface MinSizeValidator extends Validator
{
    /**
     * @param boolean $formatted
     * @return integer|string
     */
    public function getMinSize($formatted = false);
}
