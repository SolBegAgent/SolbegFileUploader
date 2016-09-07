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
     * @var callable|null
     */
    private $defaultCallback;

    /**
     * @param callable|null $defaultCallback
     */
    public function __construct(callable $defaultCallback = null)
    {
        $this->defaultCallback = $defaultCallback;
    }

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
     * @throws NotSupportedException
     */
    public function contents($format = null)
    {
        return $this->process(__FUNCTION__, $format);
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
        return $this->process(__FUNCTION__, $format);
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException always
     */
    public function name($format = null)
    {
        return $this->process(__FUNCTION__, $format);
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException always
     */
    public function basename($format = null)
    {
        return $this->process(__FUNCTION__, $format);
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException always
     */
    public function extension($format = null)
    {
        return $this->process(__FUNCTION__, $format);
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException always
     */
    public function mimeType($format = null)
    {
        return $this->process(__FUNCTION__, $format);
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException always
     */
    public function size($format = null)
    {
        return $this->process(__FUNCTION__, $format);
    }

    /**
     * @inheritdoc
     */
    public function delete($format = null)
    {
        // nothing to do
    }

    /**
     * @inheritdoc
     */
    public function formats()
    {
        return [];
    }

    /**
     * @param string|null $format
     * @return FileSource|null
     * @throws NotSupportedException
     */
    protected function process($method, $format = null)
    {
        if (!$this->defaultCallback) {
            throw $this->createNotSupportedException($method);
        } elseif (false === $result = call_user_func($this->defaultCallback, $method, $format, $this)) {
            throw $this->createNotSupportedException($method);
        }
        return $result;
    }

    /**
     * @param string $method
     * @return NotSupportedException
     */
    protected function createNotSupportedException($method)
    {
        return new NotSupportedException("File is empty and does not support the `$method()` method.");
    }
}
