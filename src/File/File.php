<?php

namespace Bicycle\FilesManager\File;

use Bicycle\FilesManager\Contracts\Context as ContextInterface;
use Bicycle\FilesManager\Contracts\FileSource as FileSourceInterface;
use Bicycle\FilesManager\Contracts\StoredFileSource as StoredFileSourceInterface;

/**
 * File objects that will be instantiated for each file attribute.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class File implements FileSourceInterface
{
    /**
     * @var FileSourceInterface|null
     */
    private $oldSources = [];

    /**
     * @var FileSourceInterface
     */
    private $source;

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * @param ContextInterface $context
     * @param mixed $source it may be either file source or file data for creating source.
     */
    public function __construct(ContextInterface $context, $source = null)
    {
        $this->context = $context;
        $this->setData($source);
    }

    /**
     * @param FileSourceInterface $data
     * @return static $this
     */
    public function setData($data)
    {
        if ($this->source !== null) {
            $this->oldSources[] = $this->source;
        }
        if ($data instanceof FileSourceInterface) {
            $this->source = $data;
        } else {
            $this->source = $this->context->getSourceFactory()->make($data);
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function contents($format = null)
    {
        return $this->source->contents($format);
    }

    /**
     * @inheritdoc
     */
    public function relativePath()
    {
        return $this->source->relativePath();
    }

    /**
     * @inheritdoc
     */
    public function url($format = null)
    {
        return $this->source->url($format);
    }

    /**
     * @inheritdoc
     */
    public function name($format = null)
    {
        return $this->source->name($format);
    }

    /**
     * @inheritdoc
     */
    public function basename($format = null)
    {
        return $this->source->basename($format);
    }

    /**
     * @inheritdoc
     */
    public function mimeType($format = null)
    {
        return $this->source->mimeType($format);
    }

    /**
     * @inheritdoc
     */
    public function size($format = null)
    {
        return $this->source->size($format);
    }

    /**
     * @inheritdoc
     */
    public function extension($format = null)
    {
        return $this->source->extension($format);
    }

    /**
     * @inheritdoc
     */
    public function exists($format = null)
    {
        return $this->source->exists($format);
    }

    /**
     * This method is inverse version of `exists()` method.
     * @see exists()
     * 
     * @param string|null $format the name of formatted version of the file.
     * Null means original version.
     * @return boolean whether file exists or not.
     */
    public function isEmpty($format = null)
    {
        return !$this->exists($format);
    }

    /**
     * Saves this file if not saved.
     * @param boolean $deleteOld if true then all old stored non-temporary sources will be deleted.
     */
    public function save($deleteOld = false)
    {
        $source = $this->source;
        $storage = $this->context->storage(false);
        if ($source instanceof StoredFileSourceInterface && $source->isStored()) {
            return;
        }

        $this->setData($storage->saveNewFile($source));

        if ($deleteOld) {
            foreach ($this->oldSources as $oldSource) {
                if ($oldSource instanceof StoredFileSourceInterface && $source->isStored()) {
                    $oldSource->delete();
                }
            }
            $this->oldSources = [];
        }
    }

    /**
     * @inheritdoc
     * @param array $options
     */
    public function delete($format = null, array $options = [])
    {
        $this->source->delete($format, $options);
        if ($format === null) {
            $this->setData(null);
            $this->oldSources = [];
        }
    }

    /**
     * @inheritdoc
     */
    public function formats()
    {
        return $this->source->formats();
    }
}
