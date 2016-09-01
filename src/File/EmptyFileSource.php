<?php

namespace Bicycle\FilesManager\File;

use Bicycle\FilesManager\Contracts\FileSource;
use Bicycle\FilesManager\Exceptions\NotSupportedException;

/**
 * EmptyFileSource
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class EmptyFileSource implements FileSource
{
    /**
     * @param string $format
     * @return boolean
     */
    public function exists($format = null)
    {
        return false;
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException always
     */
    public function readPath($format = null)
    {
        throw $this->createNotSupportedException(__FUNCTION__);
    }

    /**
     * @inheritdoc
     * @return null always
     */
    public function relativePath()
    {
        return null;
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException always
     */
    public function url($format = null)
    {
        throw $this->createNotSupportedException(__FUNCTION__);
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException always
     */
    public function name($format = null)
    {
        throw $this->createNotSupportedException(__FUNCTION__);
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException always
     */
    public function basename($format = null)
    {
        throw $this->createNotSupportedException(__FUNCTION__);
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException always
     */
    public function extension($format = null)
    {
        throw $this->createNotSupportedException(__FUNCTION__);
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException always
     */
    public function mimeType($format = null)
    {
        throw $this->createNotSupportedException(__FUNCTION__);
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException always
     */
    public function size($format = null)
    {
        throw $this->createNotSupportedException(__FUNCTION__);
    }

    /**
     * @param string $method
     * @return NotSupportedException
     */
    protected function createNotSupportedException($method)
    {
        return new NotSupportedException("File is empty and does not support the `$method()` method.");
    }

    /**
     * @inheritdoc
     */
    public function delete()
    {
        // nothing to do
    }
}
