<?php

namespace Bicycle\FilesManager\File\Traits;

/**
 * RelativePathToString returns relative path when object is converting to string.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
trait RelativePathToString
{
    /**
     * @return string|null
     */
    abstract protected function relativePath();

    /**
     * @return string
     */
    public function __toString()
    {
        try {
            return (string) $this->relativePath();
        } catch (\Exception $ex) {
            trigger_error((string) $ex, E_USER_ERROR);
            return '';
        }
    }
}
