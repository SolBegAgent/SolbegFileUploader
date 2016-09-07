<?php

namespace Bicycle\FilesManager\File\Traits;

use Bicycle\FilesManager\Helpers\File as FileHelper;
use Bicycle\FilesManager\Exceptions\NotSupportedException;

/**
 * WithoutFormatting Trait
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
trait WithoutFormatting
{
    /**
     * @param string $message
     * @return NotSupportedException
     */
    abstract protected function createNotSupportedException($message);

    /**
     * @return boolean
     */
    abstract protected function originExists();
    /**
     * @return string
     */
    abstract protected function originContents();
    /**
     * @return string
     */
    abstract protected function originUrl();
    /**
     * @return string
     */
    abstract protected function originName();
    /**
     * @return string|null
     */
    abstract protected function originMimeType();
    /**
     * @return integer
     */
    abstract protected function originSize();
    /**
     * Deletes origin file.
     */
    abstract protected function deleteOrigin();

    /**
     * @return string
     */
    protected function originBasename()
    {
        return FileHelper::basename($this->originName());
    }

    /**
     * @return string
     */
    protected function originExtension()
    {
        return FileHelper::extension($this->originName());
    }

    /**
     * @inheritdoc
     */
    public function exists($format = null)
    {
        try {
            $this->assertFormatIsNull($format);
            return $this->originExists();
        } catch (NotSupportedException $ex) {
            return false;
        }
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException if `$format` is not null.
     */
    public function contents($format = null)
    {
        $this->assertFormatIsNull($format);
        return $this->originContents();
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException if `$format` is not null.
     */
    public function url($format = null)
    {
        $this->assertFormatIsNull($format);
        return $this->originUrl();
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException if `$format` is not null.
     */
    public function name($format = null)
    {
        $this->assertFormatIsNull($format);
        return $this->originName();
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException if `$format` is not null.
     */
    public function extension($format = null)
    {
        $this->assertFormatIsNull($format);
        return $this->originExtension();
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException if `$format` is not null.
     */
    public function basename($format = null)
    {
        $this->assertFormatIsNull($format);
        return $this->originBasename();
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException if `$format` is not null.
     */
    public function mimeType($format = null)
    {
        $this->assertFormatIsNull($format);
        return $this->originMimeType();
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException if `$format` is not null.
     */
    public function size($format = null)
    {
        $this->assertFormatIsNull($format);
        return $this->originSize();
    }

    /**
     * @inheritdoc
     */
    public function formats()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function delete($format = null)
    {
        try {
            $this->assertFormatIsNull($format);
            $this->deleteOrigin();
        } catch (NotSupportedException $ex) {
            // nothing to do
        }
    }

    /**
     * @param string|null $format
     * @throws NotSupportedException
     */
    protected function assertFormatIsNull($format)
    {
        if ($format !== null) {
            throw $this->createNotSupportedException('{class} does not support file formatting.');
        }
    }
}
