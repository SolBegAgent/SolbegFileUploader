<?php

namespace Solbeg\FilesManager\Contracts;

/**
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
interface FileNotFoundException
{
    /**
     * @return Storage
     */
    public function getStorage();

    /**
     * @return string
     */
    public function getRelativePath();

    /**
     * @return string|null
     */
    public function getFormat();

    /**
     * @param boolean $recheck
     * @return boolean
     */
    public function isOriginFileExists($recheck = false);

    /**
     * @param boolean $recheck
     * @return boolean
     */
    public function isRequestedFileExists($recheck = false);

    /**
     * @return \Exception|null
     */
    public function getPrevious();

    /**
     * @return string
     */
    public function getMessage();
}
