<?php

namespace Bicycle\FilesManager\File;

use Bicycle\FilesManager\Contracts;
use Bicycle\FilesManager\Helpers\File as FileHelper;

/**
 * StoredFileSource keeps file that stored in context.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class StoredFileSource implements Contracts\FileSource
{
    /**
     * @var string relative path to origin file in context
     */
    protected $relativePath;

    /**
     * @var Contracts\Context
     */
    protected $context;

    /**
     * @var boolean whether this file saved in temporary storage or not.
     */
    protected $isTemporary = false;

    /**
     * @param Contracts\Context $context
     * @param string $relativePath
     * @param boolean $temp whether this file saved in temporary storage or not.
     */
    public function __construct(Contracts\Context $context, $relativePath, $temp = false)
    {
        $this->context = $context;
        $this->relativePath = $relativePath;
        $this->isTemporary = (bool) $temp;
    }

    /**
     * @inheritdoc
     */
    public function exists($format = null)
    {
        return $this->context->fileExists($this->relativePath(), $format, $this->isTemporary());
    }

    /**
     * @inheritdoc
     */
    public function readPath($format = null)
    {
        return $this->context->fileReadPath($this->relativePath(), $format, $this->isTemporary());
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
        return $this->context->fileUrl($this->relativePath(), $format, $this->isTemporary());
    }

    /**
     * @inheritdoc
     */
    public function name($format = null)
    {
        return $this->context->fileName($this->relativePath(), $format, $this->isTemporary());
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
        return $this->context->fileMimeType($this->relativePath(), $format, $this->isTemporary());
    }

    /**
     * @inheritdoc
     */
    public function size($format = null)
    {
        return $this->context->fileSize($this->relativePath(), $format, $this->isTemporary());
    }

    /**
     * @inheritdoc
     */
    public function extension($format = null)
    {
        return FileHelper::extension($this->name($format));
    }

    /**
     * @return boolean
     */
    public function isTemporary()
    {
        return $this->isTemporary;
    }

    /**
     * Deletes file from context's storage.
     */
    public function delete()
    {
        $this->context->deleteFile($this, $this->isTemporary());
    }
}
