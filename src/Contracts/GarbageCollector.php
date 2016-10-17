<?php

namespace Bicycle\FilesManager\Contracts;

/**
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
interface GarbageCollector
{
    /**
     * @param integer $lifetime Life time of files in seconds.
     * @return array array of relative paths of files, that should be cleaned.
     */
    public function collect($lifetime = null);

    /**
     * Cleans files and its formatted versions.
     * 
     * @param string|string[]|null relative paths that should be removed.
     *  - string: one relative path
     *  - array: array of relative paths
     *  - null: result of `collect()` will be used.
     */
    public function clean($relativePaths = null);
}
