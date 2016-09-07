<?php

namespace Bicycle\FilesManager\Contracts;

interface StoredFileSource extends FileSource
{
    /**
     * @return boolean
     */
    public function isStored();
}
