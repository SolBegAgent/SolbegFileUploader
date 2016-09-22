<?php

namespace Bicycle\FilesManager\Contracts;

/**
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
interface ExportableFileSource extends FileSource
{
    /**
     * @param string|null $format
     * @param boolean|null $secure
     * @return string
     */
    public function absoluteUrl($format = null, $secure = null);

    /**
     * @param string|null $format
     * @return \Intervention\Image\Image
     */
    public function image($format = null);

    /**
     * @return boolean
     */
    public function isStored();
}
