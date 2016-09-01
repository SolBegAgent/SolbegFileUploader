<?php

namespace Bicycle\FilesManager\File;

use Bicycle\FilesManager\Contracts\Context as ContextInterface;
use Bicycle\FilesManager\Contracts\FileSource as FileSourceInterface;

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
        $this->oldSources[] = $this->source;
        if ($data instanceof FileSourceInterface) {
            $this->source = $data;
        } else {
            $this->source = $this->context->getSourceFactory()->make($data);
        }
        return $this;
    }

    /**
     * Returns path that may be used to read file content, e.g. by `file_get_contents()` function.
     * 
     * @param string|null $format the name of formatted version of the file.
     * Null means original version.
     * @return string
     */
    public function readPath($format = null)
    {
        return $this->source->readPath($format);
    }

    /**
     * Returns relative path to the original file. This path will be stored in database.
     * 
     * @return string|null relative path or null if file does not exist.
     */
    public function relativePath()
    {
        return $this->source->relativePath();
    }

    /**
     * Returns url to the file.
     * 
     * @param string|null $format the name of formatted version of the file.
     * Null means original version.
     * @return string
     */
    public function url($format = null)
    {
        return $this->source->url($format);
    }

    /**
     * Returns name of the file.
     * 
     * @param string|null $format the name of formatted version of the file.
     * Null means original version.
     * @return string
     */
    public function name($format = null)
    {
        return $this->source->name($format);
    }

    /**
     * Returns basename of the file (file name without extension)
     * 
     * @param string|null $format the name of formatted version of the file.
     * Null means original version.
     * @return string
     */
    public function basename($format = null)
    {
        return $this->source->basename($format);
    }

    /**
     * Returns MIME type of the file.
     * 
     * @param string|null $format the name of formatted version of the file.
     * Null means original version.
     * @return string
     */
    public function mimeType($format = null)
    {
        return $this->source->mimeType($format);
    }

    /**
     * Returns size of the file in bytes.
     * 
     * @param string|null $format the name of formatted version of the file.
     * Null means original version.
     * @return integer
     */
    public function size($format = null)
    {
        return $this->source->size($format);
    }

    /**
     * Returns extension of the file.
     * 
     * @param string|null $format the name of formatted version of the file.
     * Null means original version.
     * @return string|null file extension or null if file has not it.
     */
    public function extension($format = null)
    {
        return $this->source->extension($format);
    }

    /**
     * Checks whether the original or formatted version of file exists in file system.
     * 
     * @param string|null $format the name of formatted version of the file.
     * Null means original version.
     * @return boolean whether file exists or not.
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
        if ($source instanceof StoredFileSource && !$source->isTemporary()) {
            return;
        }

        $newSource = $this->context->saveNewFile($source, false);
        $this->setData($newSource);

        if ($deleteOld) {
            foreach ($this->oldSources as $oldSource) {
                if ($oldSource instanceof StoredFileSource && !$oldSource->isTemporary()) {
                    $oldSource->delete();
                }
            }
        }
        $this->oldSources = [];
    }

    /**
     * @inheritdoc
     */
    public function delete()
    {
        $this->source->delete();
        $this->setData(null);
        $this->oldSources = [];
    }
}
