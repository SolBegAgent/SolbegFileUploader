<?php

namespace Solbeg\FilesManager\Exceptions;

/**
 * ReadOnlyPropertyException
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class ReadOnlyPropertyException extends UnknownPropertyException
{
    /**
     * @inheritdoc
     */
    protected function generateMessage()
    {
        return "Cannot change the read-only property \"{$this->getPropertyName()}\" in \"{$this->getObjectClassName()}\" class.";
    }
}
