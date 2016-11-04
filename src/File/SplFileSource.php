<?php

namespace Solbeg\FilesManager\File;

use Solbeg\FilesManager\Exceptions\FileSystemException;

use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

/**
 * SplFileSource
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class SplFileSource extends AbstractFileSource
{
    use Traits\NotSupported;
    use Traits\WithoutFormatting;
    use Traits\WithoutRelativePath;

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
     * @throws \Solbeg\FilesManager\Exceptions\NotSupportedException always
     */
    protected function originUrl()
    {
        throw $this->createNotSupportedException('{class} does not support access by HTTP.');
    }

    /**
     * @inheritdoc
     */
    protected function originBasename()
    {
        return $this->getFile()->getBasename();
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
    protected function originLastModified()
    {
        return (int) $this->getFile()->getMTime();
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
