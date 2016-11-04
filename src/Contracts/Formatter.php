<?php

namespace Solbeg\FilesManager\Contracts;

interface Formatter
{
    /**
     * This method should format `$source` file and return path to temporary file.
     * This file will be automatically deleted in the future.
     * 
     * @param \Solbeg\FilesManager\Contracts\FileSource $source
     * @param \Solbeg\FilesManager\Contracts\Storage $storage
     * @return string|null path to temporary formatted file.
     * Null is meaning file was not converted.
     */
    public function format(FileSource $source, Storage $storage);
}
