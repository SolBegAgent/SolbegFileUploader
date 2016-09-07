<?php

namespace Bicycle\FilesManager\File;

use Bicycle\FilesManager\Contracts;
use Bicycle\FilesManager\Helpers\File as FileHelper;

/**
 * StoredFileSource keeps file that stored in context.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class StoredFileSource implements Contracts\StoredFileSource
{
    /**
     * @var string relative path to origin file in context
     */
    protected $relativePath;

    /**
     * @var Contracts\Storage
     */
    protected $storage;

    /**
     * @param Contracts\Storage $storage
     * @param string $relativePath
     * @param boolean $temp whether this file saved in temporary storage or not.
     */
    public function __construct(Contracts\Storage $storage, $relativePath)
    {
        $this->storage = $storage;
        $this->relativePath = $relativePath;
    }

    /**
     * @inheritdoc
     */
    public function exists($format = null)
    {
        return $this->storage->fileExists($this->relativePath(), $format);
    }

    /**
     * @inheritdoc
     */
    public function contents($format = null)
    {
        return $this->storage->fileContents($this->relativePath(), $format);
    }

    /**
     * @inheritdoc
     */
    public function relativePath()
    {
        return $this->relativePath;
    }

    /**
     * @inheritdoc
     */
    public function url($format = null)
    {
        return $this->storage->fileUrl($this->relativePath(), $format);
    }

    /**
     * @inheritdoc
     */
    public function name($format = null)
    {
        return $this->storage->fileName($this->relativePath(), $format);
    }

    /**
     * @inheritdoc
     */
    public function basename($format = null)
    {
        return FileHelper::basename($this->name($format));
    }

    /**
     * @inheritdoc
     */
    public function mimeType($format = null)
    {
        return $this->storage->fileMimeType($this->relativePath(), $format);
    }

    /**
     * @inheritdoc
     */
    public function size($format = null)
    {
        return $this->storage->fileSize($this->relativePath(), $format);
    }

    /**
     * @inheritdoc
     */
    public function extension($format = null)
    {
        return FileHelper::extension($this->name($format));
    }

    /**
     * @return Contracts\Storage
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * @inheritdoc
     */
    public function delete($format = null, array $options = [])
    {
        $this->storage->deleteFile($this->relativePath(), $format, $options);
    }

    /**
     * @inheritdoc
     */
    public function formats()
    {
        return $this->storage->fileFormats($this->relativePath());
    }

    /**
     * @inheritdoc
     */
    public function isStored()
    {
        return $this->storage === $this->storage->context()->storage(false);
    }
}
