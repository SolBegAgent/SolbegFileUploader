<?php

namespace Bicycle\FilesManager\File\ContentStreams;

use Bicycle\FilesManager\Contracts\ContentStream as ContentStreamInterface;

/**
 * Stream works with stream resources.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class Stream implements ContentStreamInterface
{
    use ToStringTrait;

    /**
     * @var resource
     */
    private $stream;

    /**
     * @param resource $stream
     * @throws \InvalidArgumentException
     */
    public function __construct($stream)
    {
        if (!is_resource($stream) || get_resource_type($stream) !== 'stream') {
            throw new \InvalidArgumentException('Param $stream must be a valid resource of a stream.');
        }
        $this->stream = $stream;
    }

    /**
     * @inheritdoc
     * @throws \RuntimeException
     */
    public function stream()
    {
        if ($this->stream === null) {
            throw new \RuntimeException('The stream has been already closed.');
        }
        rewind($this->stream);
        return $this->stream;
    }

    /**
     * @inheritdoc
     */
    public function contents()
    {
        return stream_get_contents($this->stream());
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
}
