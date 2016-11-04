<?php

namespace Solbeg\FilesManager\Validation;

/**
 * DefaultMessageTrait
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
trait DefaultMessageTrait
{
    /**
     * @var string|null
     */
    protected $message;

    /**
     * @return string
     */
    abstract protected function defaultMessage();

    /**
     * @inheritdoc
     */
    protected function errorMessage()
    {
        return $this->message ?: $this->defaultMessage();
    }
}
