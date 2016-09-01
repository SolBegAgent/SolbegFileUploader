<?php

namespace Bicycle\FilesManager\Exceptions;

use Bicycle\FilesManager\Contracts\Context;

class FileNotFoundException extends FileSystemException
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var string
     */
    private $relativePath;

    /**
     * @var boolean
     */
    private $isTemporary = false;

    /**
     * @param Context $context
     * @param string $relativePath
     * @param boolean $temp
     * @param string|null $message
     * @param integer $code
     * @param \Exception $previous
     */
    public function __construct(Context $context, $relativePath, $temp = false, $message = null, $code = 0, \Exception $previous = null)
    {
        $this->context = $context;
        $this->relativePath = $relativePath;
        $this->isTemporary = (bool) $temp;

        if ($message === null || $message === '') {
            $message = $this->generateMessage();
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    protected function generateMessage()
    {
        $label = $this->isTemporary ? 'Temporary file' : 'File';
        return "$label with relative path '$this->relativePath' does not exist in '{$this->context->getName()}' context.";
    }

    /**
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return string
     */
    public function getRelativePath()
    {
        return $this->relativePath;
    }

    /**
     * @return boolean
     */
    public function isTemporaryFile()
    {
        return $this->isTemporary;
    }
}
