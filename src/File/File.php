<?php

namespace Bicycle\FilesManager\File;

use Bicycle\FilesManager\Contracts\Context as ContextInterface;
use Bicycle\FilesManager\Contracts\ExportableFileSource as FileInterface;
use Bicycle\FilesManager\Contracts\FileSource as FileSourceInterface;
use Bicycle\FilesManager\Contracts\StoredFileSource as StoredFileInterface;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * File objects that will be instantiated for each file attribute.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class File implements FileInterface, Arrayable, Jsonable, \JsonSerializable
{
    use Traits\FormatsAsProperties;
    use Traits\HelperMethods;

    /**
     * @var FileSourceInterface[]
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
     * @param boolean $absolute
     */
    public function url($format = null, $absolute = false)
    {
        return $absolute
            ? $this->absoluteUrl($format)
            : $this->source->url($format);
    }

    /**
     * @inheritdoc
     */
    public function absoluteUrl($format = null, $secure = null)
    {
        $url = $this->url($format, false);
        return app(UrlGenerator::class)->asset($url, $secure);
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
    public function lastModified($format = null)
    {
        return $this->source->lastModified($format);
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
     * @inheritdoc
     */
    public function isStored()
    {
        return $this->isStoredSource($this->source);
    }

    /**
     * @param FileSourceInterface $source
     * @return boolean
     */
    protected function isStoredSource($source)
    {
        $storage = $this->context()->storage(false);
        return $source instanceof StoredFileInterface && $source->getStorage() === $storage;
    }

    /**
     * @inheritdoc
     * @param array $options You may use the followings options:
     *  - 'deleteOld': boolean (default = true), whether old stored files should be also removed
     *  - 'validate': boolean (default = true), whether the file should be validated before saving
     *  - 'deleteOptions': array (default = [])
     */
    public function save(array $options = [])
    {
        if ($this->isStored()) {
            return;
        }

        if ($this->source->exists()) {
            $newRelativePath = $this->context->storage(false)->saveNewFile($this->source, $options);
            $this->setData($newRelativePath);
        } else {
            $this->setData(null);
        }

        if (!isset($options['deleteOld']) || $options['deleteOld']) {
            $deleteOptions = isset($options['deleteOptions']) ? $options['deleteOptions'] : [];
            foreach ($this->oldSources as $oldSource) {
                if ($this->isStoredSource($oldSource)) {
                    $oldSource->delete(null, $deleteOptions);
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
     *  - 'throwExceptions': boolean (default = false)
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

    /**
     * @return string
     */
    public function __toString()
    {
        try {
            return (string) $this->url();
        } catch (\Exception $ex) {
            trigger_error((string) $ex, E_USER_ERROR);
            return '';
        }
    }

    /**
     * @inheritdoc
     */
    public function toArray()
    {
        return $this->context()->getToArrayConverter()->convertToArray($this);
    }

    /**
     * @ineritdoc
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * @ineritdoc
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->context()->getToArrayConverter()->jsonSerialize($this);
    }
}
