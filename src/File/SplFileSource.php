<?php

namespace Bicycle\FilesManager\File;

use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

use Bicycle\FilesManager\Contracts\FileSource as FileSourceInterface;
use Bicycle\FilesManager\Exceptions\FileSystemException;

/**
 * SplFileSource
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class SplFileSource implements FileSourceInterface
{
    use Traits\NotSupported, Traits\WithoutFormatting, Traits\WithoutRelativePath;

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
    protected function originExists()
    {
        return (bool) $this->getFile()->isFile();
    }

    /**
     * @inheritdoc
     * @throws FileSystemException
     */
    protected function originContents()
    {
        return new ContentStreams\Path($this->getFile()->getPathname());
    }

    /**
     * @inheritdoc
     * @throws \Bicycle\FilesManager\Exceptions\NotSupportedException always
     */
    protected function originUrl()
    {
        throw $this->createNotSupportedException('{class} does not support access by HTTP.');
    }

    /**
     * @inheritdoc
     */
    protected function originName()
    {
        return $this->getFile()->getFilename();
    }

    /**
     * @inheritdoc
     */
    protected function originMimeType()
    {
        $file = $this->getFile();
        if (!$file instanceof SymfonyFile) {
            $file = new SymfonyFile($file->getPathname());
        }
        return $file->getMimeType() ?: null;
    }

    /**
     * @inheritdoc
     */
    protected function originSize()
    {
        return (int) $this->getFile()->getSize();
    }

    /**
     * @inheritdoc
     */
    protected function deleteOrigin()
    {
        $path = $this->getFile()->getPathname();
        @unlink($path);
    }
}
