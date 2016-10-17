<?php

namespace Bicycle\FilesManager\Context;

use Bicycle\FilesManager\Contracts;
use Bicycle\FilesManager\Helpers;

/**
 * GarbageCollector collects old temporary files and removes them.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class GarbageCollector implements Contracts\GarbageCollector
{
    use Helpers\ConfigurableTrait;

    /**
     * Default life time of files in seconds.
     * Default is 1 day.
     * 
     * @var integer 
     */
    protected $lifetime = 86400;

    /**
     * @var Contracts\Storage
     */
    private $storage;

    /**
     * @param Contracts\Storage $storage
     * @param array $config
     */
    public function __construct(Contracts\Storage $storage, array $config = [])
    {
        $this->storage = $storage;
        $this->configure($config);
    }

    /**
     * @inheritdoc
     */
    public function collect($lifetime = null)
    {
        $result = [];
        foreach ($this->storage()->files() as $relativePath) {
            if ($this->isOutdatedFile($relativePath, $lifetime)) {
                $result[] = $relativePath;
            }
        }
        return $result;
    }

    /**
     * @param string $relativePath
     * @param integer $lifetime
     * @return boolean
     */
    public function isOutdatedFile($relativePath, $lifetime = null)
    {
        if ($lifetime === null) {
            $lifetime = $this->lifetime;
        }

        $timestamp = $this->storage()->fileLastModified($relativePath);
        return $timestamp + $lifetime <= time();
    }

    /**
     * @ihneritdoc
     */
    public function clean($relativePaths = null)
    {
        $files = $relativePaths === null ? $this->collect() : (array) $relativePaths;
        $this->storage()->deleteFiles($files);
    }

    /**
     * @return Contracts\Storage
     */
    public function storage()
    {
        return $this->storage;
    }
}
