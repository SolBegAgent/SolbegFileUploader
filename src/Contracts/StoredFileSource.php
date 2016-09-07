<?php

namespace Bicycle\FilesManager\Contracts;

interface StoredFileSource extends FileSource
{
    /**
     * @return Storage
     */
    public function getStorage();
}
