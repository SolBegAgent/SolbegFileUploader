<?php

namespace Bicycle\FilesManager\File\ContentStreams;

use Bicycle\FilesManager\Contracts\ContentStream as ContentStreamInterface;

/**
 * Path works with file paths or urls.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class Path implements ContentStreamInterface
{
    use ToStringTrait;

    /**
     * @var string|null
     */
    private $path;

    /**
     * @var string|null
     */
    private $stream;

    /**
     * @param string $path full path to file or url.
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Magic PHP method that will be called after clonning of the object.
     * Clears stream if it was opened.
     */
    public function __clone()
    {
        $this->stream = null;
    }

    /**
     * @inheritdoc
     */
    public function stream()
    {
        if ($this->stream !== null) {
            rewind($this->stream);
            return $this->stream;
        }

        $resource = @fopen($this->path, 'rb');
        if (!$resource) {
            throw $this->createPathException();
        }
        return $this->stream = $resource;
    }

    /**
     * @inheritdoc
     */
    public function contents()
    {
        $result = @file_get_contents($this->path);
        if ($result === false || $result === null) {
            throw $this->createPathException();
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function close()
    {
        if ($this->stream !== null) {
            @fclose($this->stream);
            $this->stream = null;
        }
    }

    /**
     * @return \RuntimeException
     */
    protected function createPathException()
    {
        return new \RuntimeException("Cannot read contents by path: '$this->path'.");
    }
}
