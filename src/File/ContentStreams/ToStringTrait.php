<?php

namespace Solbeg\FilesManager\File\ContentStreams;

/**
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
trait ToStringTrait
{
    /**
     * @return string
     */
    abstract public function contents();

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        try {
            return (string) $this->contents();
        } catch (\Exception $ex) {
            trigger_error((string) $ex, E_USER_ERROR);
            return '';
        }
    }
}
