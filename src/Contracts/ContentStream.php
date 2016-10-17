<?php

namespace Bicycle\FilesManager\Contracts;

interface ContentStream
{
    /**
     * Opens stream to read contents.
     * 
     * @return resource
     */
    public function stream();

    /**
     * Reads and returns file contents.
     * 
     * @return string
     */
    public function contents();

    /**
     * Closes all opened streams.
     * Removes content from memory.
     */
    public function close();

    /**
     * The object must be convertable to string.
     * So it method will return contents as string.
     * 
     * @return string
     */
    public function __toString();
}
