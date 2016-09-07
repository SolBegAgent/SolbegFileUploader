<?php

namespace Bicycle\FilesManager\Context\FileNotFound;

/**
 * AllowedStoragesTrait
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
trait AllowedStoragesTrait
{
    /**
     * @var array|null
     */
    protected $onlyStorages = null;

    /**
     * @var array|null
     */
    protected $exceptStorages = null;

    /**
     * @param \Bicycle\FilesManager\Contracts\FileNotFoundException $exception
     * @return boolean
     */
    protected function isAllowedStorage($exception)
    {
        $storageName = $exception->getStorage()->name();
        if (is_array($this->exceptStorages) && in_array($storageName, $this->exceptStorages, true)) {
            return false;
        } elseif (!is_array($this->onlyStorages)) {
            return true;
        } else {
            return in_array($storageName, $this->onlyStorages, true);
        }
    }
}
