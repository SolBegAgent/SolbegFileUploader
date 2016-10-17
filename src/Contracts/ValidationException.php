<?php

namespace Bicycle\FilesManager\Contracts;

/**
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
interface ValidationException
{
    /**
     * @return FileSource
     */
    public function getSource();

    /**
     * @return Context
     */
    public function getContext();

    /**
     * @return Validator[]
     */
    public function getFailedValidators();

    /**
     * @return array in rule => message format.
     */
    public function getMessages();

    /**
     * @return string
     */
    public function getFirstMessage();
}
