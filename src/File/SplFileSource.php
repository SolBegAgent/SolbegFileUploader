<?php

namespace Bicycle\FilesManager\File;

use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

use Bicycle\FilesManager\Contracts\FileSource as FileSourceInterface;
use Bicycle\FilesManager\Helpers\File as FileHelper;

/**
 * SplFileSource
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class SplFileSource implements FileSourceInterface
{
    /**
     * @var \SplFileInfo
     */
    private $file;

    /**
     * @param \SplFileInfo $file
     */
    public function __construct(\SplFileInfo $file)
    {
        $this->file = $file;
    }

    /**
     * @return \SplFileInfo
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @inheritdoc
     */
    public function exists($format = null)
    {
        return $format === null ? $this->getFile()->isFile() : false;
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException if `$format` is not null.
     */
    public function readPath($format = null)
    {
        $this->assertFormatIsNull($format);
        return $this->getFile()->getPathname();
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException always
     */
    public function relativePath()
    {
        throw $this->createNotSupportedException('{class} does not support direct storing in database. You need save it before that.');
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException always
     */
    public function url($format = null)
    {
        $this->assertFormatIsNull($format);
        throw $this->createNotSupportedException('{class} does not support access by HTTP.');
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException if `$format` is not null.
     */
    public function name($format = null)
    {
        $this->assertFormatIsNull($format);
        return $this->getFile()->getFilename();
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException if `$format` is not null.
     */
    public function extension($format = null)
    {
        $this->assertFormatIsNull($format);
        return $this->getFile()->getExtension();
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException if `$format` is not null.
     */
    public function basename($format = null)
    {
        $this->assertFormatIsNull($format);
        return $this->getFile()->getBasename();
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException if `$format` is not null.
     */
    public function mimeType($format = null)
    {
        $this->assertFormatIsNull($format);
        $file = $this->getFile();

        if (!$file instanceof SymfonyFile) {
            $file = new SymfonyFile($file->getPathname());
        }
        return $file->getMimeType() ?: 'application/octet-stream';
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException if `$format` is not null.
     */
    public function size($format = null)
    {
        $this->assertFormatIsNull($format);
        return (int) $this->getFile()->getSize();
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

    /**
     * @param string $message
     * @return NotSupportedException
     */
    protected function createNotSupportedException($message)
    {
        return new NotSupportedException(strtr($message, [
            '{class}' => FileHelper::basename(static::class),
        ]));
    }

    /**
     * @inheritdoc
     */
    public function delete()
    {
        @unlink($this->getFile()->getPathname());
    }
}
