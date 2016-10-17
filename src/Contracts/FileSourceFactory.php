<?php

namespace Bicycle\FilesManager\Contracts;

use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

interface FileSourceFactory
{
    /**
     * Creates FileSource instance according to `$data`.
     * 
     * @param mixed $data Either empty value or uploaded file or relative path to temp file.
     * @return FileSource
     */
    public function make($data);

    /**
     * @param SymfonyUploadedFile $uploadedFile
     * @return FileSource
     */
    public function uploadedFile(SymfonyUploadedFile $uploadedFile);

    /**
     * @param \SplFileInfo $file
     * @return FileSource
     */
    public function simpleFile(\SplFileInfo $file);

    /**
     * @param string $content
     * @param string $name
     * @param string|null $mimeType
     * @param string|null $url
     * @return FileSource
     */
    public function contentFile($content, $name, $mimeType = null, $url = null);

    /**
     * @param string $url
     * @return FileSource
     */
    public function urlFile($url);

    /**
     * Creates FileSource for stored in context file.
     * 
     * @param string $path
     * @param Storage $storage
     * @return FileSource
     */
    public function storedFile($path, Storage $storage);

    /**
     * @param FileSource $source
     * @param string $fixedFormat
     * @param boolean $always
     * @return FileSource
     */
    public function formattedFile(FileSource $source, $fixedFormat = null, $always = false);

    /**
     * @param Storage $storage
     * @return FileSource
     */
    public function emptyFile(Storage $storage = null);
}
