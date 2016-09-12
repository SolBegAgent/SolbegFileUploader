<?php

namespace Bicycle\FilesManager\Contracts\Validators;

use Bicycle\FilesManager\Contracts\Validator;

interface MinSizeValidator extends Validator
{
    /**
     * @param boolean $formatted
     * @return integer|string
     */
    public function getMinSize($formatted = false);
}
