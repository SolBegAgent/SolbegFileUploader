<?php

namespace Bicycle\FilesManager\Contracts;

interface Validator
{
    /**
     * @param \Bicycle\FilesManager\Contracts\FileSource $source
     * @return string|null
     */
    public function validate(FileSource $source);
}
