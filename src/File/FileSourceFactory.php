<?php

namespace Bicycle\FilesManager\File;

use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

use Bicycle\FilesManager\Contracts\Context as ContextInterace;
use Bicycle\FilesManager\Contracts\Storage as StorageInterface;
use Bicycle\FilesManager\Contracts\FileSource as SourceInterface;
use Bicycle\FilesManager\Contracts\FileSourceFactory as FactoryInterface;
use Bicycle\FilesManager\Exceptions;

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
    public function make($data)
    {
        $storage = $this->context->storage(true);

        if (!$data) {
            return $this->emptyFile($storage);
        } elseif ($data instanceof SymfonyUploadedFile) {
            return $this->uploadedFile($data);
        } elseif ($data instanceof \SplFileInfo) {
            return $this->simpleFile($data);
        } elseif (!is_string($data)) {
            throw new \InvalidArgumentException('Unknown type of $data: ' . gettype($data) . '.');
        } elseif ($storage->fileExists($data, null)) {
            return $this->storedFile($data, $storage);
        } else {
            return $this->emptyFile($storage);
        }
    }

    /**
     * @inheritdoc
     * @return UploadedFileSource
     */
    public function uploadedFile(SymfonyUploadedFile $uploadedFile)
    {
        return new UploadedFileSource($uploadedFile);
    }

    /**
     * @inheritdoc
     * @return SplFileSource
     */
    public function simpleFile(\SplFileInfo $file)
    {
        return new SplFileSource($file);
    }

    /**
     * @inheritdoc
     * @return ContentFileSource
     */
    public function contentFile($content, $name, $mimeType = null, $url = null)
    {
        return new ContentFileSource($content, $name, $mimeType, $url);
    }

    /**
     * @inheritdoc
     * @return UrlFileSource
     */
    public function urlFile($url)
    {
        return new UrlFileSource($url);
    }

    /**
     * @inheritdoc
     * @return StoredFileSource
     */
    public function storedFile($path, StorageInterface $storage)
    {
        return new StoredFileSource($storage, $path);
    }

    /**
     * @inheritdoc
     * @return FixedFormatFileSource
     */
    public function formattedFile(SourceInterface $source, $fixedFormat = null, $always = false)
    {
        return new FixedFormatFileSource($source, $fixedFormat, $always);
    }

    /**
     * @inheritdoc
     * @return EmptyFileSource
     */
    public function emptyFile(StorageInterface $storage = null)
    {
        return new EmptyFileSource($storage ? function ($method, $format = null) use ($storage) {
            $exception = $format === null
                ? new Exceptions\FileNotFoundException($storage, null)
                : new Exceptions\FormattedFileNotFoundException($storage, $format, null);
            $resultSource = $storage->context()->handleFileNotFound($exception);
            return $resultSource ? $resultSource->{$method}() ?: null : false;
        } : null);
    }
}
