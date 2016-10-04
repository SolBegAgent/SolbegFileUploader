<?php

namespace Bicycle\FilesManager\File\Traits;

use Bicycle\FilesManager\Exceptions\NotSupportedException;
use Bicycle\FilesManager\Helpers\File as FileHelper;

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
