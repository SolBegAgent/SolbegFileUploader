<?php

namespace Solbeg\FilesManager\Contracts;

interface StoredFileSource extends FileSource, \Serializable
{
    /**
     * @return Storage
     */
    public function getStorage();
}
