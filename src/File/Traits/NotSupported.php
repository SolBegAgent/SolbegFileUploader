<?php

namespace Solbeg\FilesManager\File\Traits;

use Solbeg\FilesManager\Exceptions\NotSupportedException;
use Solbeg\FilesManager\Helpers\File as FileHelper;

/**
 * NotSupported Trait
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
trait NotSupported
{
    /**
     * @param string $message
     * @return NotSupportedException
     */
    protected function createNotSupportedException($message)
    {
        return new NotSupportedException(strtr($message, [
            '{class}' => FileHelper::basename(get_class($this)),
        ]));
    }
}
