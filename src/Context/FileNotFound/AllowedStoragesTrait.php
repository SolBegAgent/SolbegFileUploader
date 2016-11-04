<?php

namespace Solbeg\FilesManager\Context\FileNotFound;

/**
 * AllowedStoragesTrait
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
trait AllowedStoragesTrait
{
    /**
     * @var array|string|null
     */
    protected $onlyStorages = null;

    /**
     * @var array|string|null
     */
    protected $exceptStorages = null;

    /**
     * @param \Solbeg\FilesManager\Contracts\FileNotFoundException $exception
     * @return boolean
     */
    protected function isAllowedStorage($exception)
    {
        $storageName = $exception->getStorage()->name();
        if ($this->exceptStorages !== null && in_array($storageName, (array) $this->exceptStorages, true)) {
            return false;
        } elseif ($this->onlyStorages === null) {
            return true;
        } else {
            return in_array($storageName, (array) $this->onlyStorages, true);
        }
    }
}
