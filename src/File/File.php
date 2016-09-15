<?php

namespace Bicycle\FilesManager\File;

use Bicycle\FilesManager\Contracts\Context as ContextInterface;
use Bicycle\FilesManager\Contracts\FileSource as FileSourceInterface;
use Bicycle\FilesManager\Contracts\StoredFileSource as StoredFileInterface;

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
     * @param array $options You may use the followings options:
     *  - 'deleteOld': boolean (default = true), whether old stored files should be also removed
     *  - 'validate': boolean (default = true), whether the file should be validated before saving
     */
    public function save(array $options = [])
    {
        $source = $this->source;
        $storage = $this->context->storage(false);
        if ($source instanceof StoredFileInterface && $source->getStorage() === $storage) {
            return;
        }

        if ($source->exists()) {
            $this->setData($storage->saveNewFile($source, $options));
        } else {
            $this->setData(null);
        }

        if (!isset($options['deleteOld']) || $options['deleteOld']) {
            foreach ($this->oldSources as $oldSource) {
                if ($oldSource instanceof StoredFileInterface && $oldSource->getStorage() === $storage) {
                    $oldSource->delete();
                }
            }
            $this->oldSources = [];
        }
    }

    /**
     * @inheritdoc
     * @param array $options You may use the followings options:
     *  - 'clearFormattedFiles': boolean (default = true), whether formatted files should be also removed.
     *  - 'clearEmptyDirs': boolean (default = true), whether empty directories should be also removed.
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

    /**
     * @return ContextInterface
     */
    public function context()
    {
        return $this->context;
    }
}
