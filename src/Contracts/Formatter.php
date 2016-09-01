<?php

namespace Bicycle\FilesManager\Contracts;

interface Formatter
{
    /**
     * This method should format `$source` file and return path to temporary file.
     * This file will be automatically in the future.
     * 
     * @param \Bicycle\FilesManager\Contracts\FileSource $source
     * @return string|null path to temporary formatted file.
     * Null is meaning file was not converted.
     */
    public function format(FileSource $source);
}
