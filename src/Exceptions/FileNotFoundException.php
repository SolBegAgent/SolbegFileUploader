<?php

namespace Solbeg\FilesManager\Exceptions;

use Solbeg\FilesManager\Contracts\Storage;
use Solbeg\FilesManager\Contracts\FileNotFoundException as ExceptionInterface;

class FileNotFoundException extends FileSystemException implements ExceptionInterface
{
    /**
     * @var Storage
     */
    private $storage;

    /**
     * @var string
     */
    private $relativePath;

    /**
     * @var boolean|null
     */
    private $isOriginFileExists = null;

    /**
     * @param Storage $storage
     * @param string $relativePath
     * @param string|null $message
     * @param integer $code
     * @param \Exception $previous
     */
    public function __construct(Storage $storage, $relativePath, $message = null, $code = 0, \Exception $previous = null)
    {
        $this->storage = $storage;
        $this->relativePath = $relativePath;

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
        return implode(' ', [
            "File with relative path '$this->relativePath' does not exist",
            "on the '{$this->storage->name()}' storage",
            "of the '{$this->storage->context()->getName()}' context.",
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * @inheritdoc
     */
    public function getRelativePath()
    {
        return $this->relativePath;
    }

    /**
     * @inheritdoc
     * @return null
     */
    public function getFormat()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function isOriginFileExists($recheck = false)
    {
        if ($recheck || $this->isOriginFileExists === null) {
            $this->isOriginFileExists = (bool) $this->getStorage()->fileExists($this->getRelativePath(), null);
        }
        return $this->isOriginFileExists;
    }

    /**
     * @inheritdoc
     */
    public function isRequestedFileExists($recheck = false)
    {
        return $this->isOriginFileExists($recheck);
    }
}
