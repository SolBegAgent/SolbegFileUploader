<?php

namespace Bicycle\FilesManager\Contracts;

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
     * Creates FileSource for stored in context file.
     * 
     * @param string $path
     * @param boolean $temp
     * @return FileSource
     */
    public function storedFile($path, $temp = false);

    /**
     * @return FileSource
     */
    public function emptyFile();
}
