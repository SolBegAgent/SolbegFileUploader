<?php

namespace Bicycle\FilesManager\Exceptions;

use Bicycle\FilesManager\Contracts\Storage;

class FormattedFileNotFoundException extends FileNotFoundException
{
    /**
     * @var string
     */
    private $format;

    /**
     * @var boolean|null
     */
    private $isRequestedFileExists = null;

    /**
     * @param Storage $storage
     * @param string $format
     * @param string $relativePath
     * @param string|null $message
     * @param integer $code
     * @param \Exception $previous
     */
    public function __construct(Storage $storage, $format, $relativePath, $message = null, $code = 0, \Exception $previous = null)
    {
        $this->format = $format;
        parent::__construct($storage, $relativePath, $message, $code, $previous);
    }

    /**
     * @return string
     */
    protected function generateMessage()
    {
        return implode(' ', [
            "Formatted as '$this->format' version of",
            "file with relative path '{$this->getRelativePath()}' does not exist",
            "on the '{$this->getStorage()->name()}' storage",
            "of the '{$this->getStorage()->context()->getName()}' context.",
        ]);
    }

    /**
     * @inheritdoc
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @inheritdoc
     */
    public function isRequestedFileExists($recheck = false)
    {
        if ($recheck || $this->isRequestedFileExists === null) {
            $this->isRequestedFileExists = $this->getStorage()->fileExists($this->getRelativePath(), $this->getFormat());
        }
        return $this->isRequestedFileExists;
    }
}
