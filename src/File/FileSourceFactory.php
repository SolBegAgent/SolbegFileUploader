<?php

namespace Bicycle\FilesManager\File;

use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

use Bicycle\FilesManager\Contracts\Context as ContextInterace;
use Bicycle\FilesManager\Contracts\FileSourceFactory as FactoryInterface;

/**
 * FileSourceFactory creates FileSource instances.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class FileSourceFactory implements FactoryInterface
{
    /**
     * @var ContextInterace
     */
    protected $context;

    /**
     * @param ContextInterace $context
     */
    public function __construct(ContextInterace $context)
    {
        $this->context = $context;
    }

    /**
     * @param mixed $data
     * @return \Bicycle\FilesManager\Contracts\FileSource
     */
    public function make($data, $trust = false)
    {
        if (!$data) {
            return $this->emptyFile();
        } elseif ($data instanceof SymfonyUploadedFile) {
            return $this->uploadedFile($data);
        } elseif ($data instanceof \SplFileInfo) {
            return $this->simpleFile($data);
        } elseif (!is_string($data)) {
            throw new \InvalidArgumentException('Unknown type of $data: ' . gettype($data) . '.');
        } elseif ($this->context->fileExists($data, null, true)) {
            return $this->storedFile($data, true);
        } else {
            return $this->emptyFile();
        }
    }

    /**
     * @param SymfonyUploadedFile $uploadedFile
     * @return UploadedFileSource
     */
    public function uploadedFile(SymfonyUploadedFile $uploadedFile)
    {
        return new UploadedFileSource($uploadedFile);
    }

    /**
     * @param \SplFileInfo $file
     * @return SplFileSource
     */
    public function simpleFile(\SplFileInfo $file)
    {
        return new SplFileSource($file);
    }

    /**
     * @inheritdoc
     * @return EmptyFileSource
     */
    public function emptyFile()
    {
        return new EmptyFileSource();
    }

    /**
     * @inheritdoc
     * @return StoredFileSource
     */
    public function storedFile($path, $temp = false)
    {
        return new StoredFileSource($this->context, $path, $temp);
    }
}
