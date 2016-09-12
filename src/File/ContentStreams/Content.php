<?php

namespace Bicycle\FilesManager\File\ContentStreams;

use Bicycle\FilesManager\Contracts\ContentStream as ContentStreamInterface;

/**
 * Content works with strings (contents).
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class Content implements ContentStreamInterface
{
    use ToStringTrait;

    /**
     * @var integer max size of content that will be stored in memory.
     * Default is 5 MB. If content exceed this limit the content will be stored in temp file.
     * @see http://php.net/manual/ru/wrappers.php.php#refsect2-wrappers.php-unknown-unknown-unknown-unknown-unknown-descriptios
     */
    public static $maxMemory = 5242880;

    /**
     * @var string
     */
    private $contents;

    /**
     * @var resource|null
     */
    private $stream;

    /**
     * @param string $contents
     */
    public function __construct($contents)
    {
        $this->contents = (string) $contents;
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
        if ($this->stream === null) {
            $this->stream = $this->createStream();
        }
        rewind($this->stream);
        return $this->stream;
    }

    /**
     * @return resource
     * @throws \Exception
     */
    protected function createStream()
    {
        $resource = fopen('php://temp/maxmemory:' . intval(static::$maxMemory), 'r+b');
        try {
            fwrite($resource, $this->contents());
        } catch (\Exception $ex) {
            @fclose($resource);
            throw $ex;
        }
        return $resource;
    }

    /**
     * @inheritdoc
     */
    public function contents()
    {
        if ($this->contents === null) {
            throw new \RuntimeException('The content has been already released from memory.');
        }
        return $this->contents;
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
        $this->contents = null;
    }
}
