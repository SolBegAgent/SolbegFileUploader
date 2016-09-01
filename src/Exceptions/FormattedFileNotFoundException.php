<?php

namespace Bicycle\FilesManager\Exceptions;

use Bicycle\FilesManager\Contracts\Context;

class FormattedFileNotFoundException extends FileNotFoundException
{
    /**
     * @var string
     */
    private $format;

    /**
     * @param Context $context
     * @param string $format
     * @param string $relativePath
     * @param boolean $temp
     * @param string|null $message
     * @param integer $code
     * @param \Exception $previous
     */
    public function __construct(Context $context, $format, $relativePath, $temp = false, $message = null, $code = 0, \Exception $previous = null)
    {
        $this->format = $format;
        parent::__construct($context, $relativePath, $temp, $message, $code, $previous);
    }

    /**
     * @return string
     */
    protected function generateMessage()
    {
        $label = $this->isTemporaryFile() ? 'temporary file' : 'file';
        return "Formatted as '$this->format' version of $label '{$this->getRelativePath()}' does not exist in '{$this->getContext()->getName()}' context.";
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }
}
