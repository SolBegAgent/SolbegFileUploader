<?php

namespace Bicycle\FilesManager\File;

use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

use Bicycle\FilesManager\Contracts;
use Bicycle\FilesManager\Helpers\File as FileHelper;

/**
 * StoredFileSource keeps file that stored in context.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class StoredFileSource extends AbstractFileSource implements
    Contracts\StoredFileSource,
    Contracts\ExportableFileSource,
    Arrayable,
    Jsonable,
    \JsonSerializable
{
    use Traits\RelativePathToString;

    /**
     * @var string relative path to origin file in context
     */
    protected $relativePath;

    /**
     * @var Contracts\Storage
     */
    protected $storage;

    /**
     * @param Contracts\Storage $storage
     * @param string $relativePath
     * @param boolean $temp whether this file saved in temporary storage or not.
     */
    public function __construct(Contracts\Storage $storage, $relativePath)
    {
        $this->storage = $storage;
        $this->relativePath = $relativePath;
    }

    /**
     * @inheritdoc
     */
    public function exists($format = null)
    {
        return $this->storage->fileExists($this->relativePath(), $format);
    }

    /**
     * @inheritdoc
     */
    public function contents($format = null)
    {
        return $this->storage->fileContents($this->relativePath(), $format);
    }

    /**
     * @inheritdoc
     */
    public function relativePath()
    {
        return $this->relativePath;
    }

    /**
     * @inheritdoc
     */
    public function url($format = null)
    {
        return $this->storage->fileUrl($this->relativePath(), $format);
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
        return $this->storage->fileName($this->relativePath(), $format);
    }

    /**
     * @inheritdoc
     */
    public function basename($format = null)
    {
        return FileHelper::basename($this->name($format));
    }

    /**
     * @inheritdoc
     */
    public function mimeType($format = null)
    {
        return $this->storage->fileMimeType($this->relativePath(), $format);
    }

    /**
     * @inheritdoc
     */
    public function size($format = null)
    {
        return $this->storage->fileSize($this->relativePath(), $format);
    }

    /**
     * @inheritdoc
     */
    public function extension($format = null)
    {
        return FileHelper::extension($this->name($format));
    }

    /**
     * @return Contracts\Storage
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * @inheritdoc
     */
    public function delete($format = null, array $options = [])
    {
        $this->storage->deleteFile($this->relativePath(), $format, $options);
    }

    /**
     * @inheritdoc
     */
    public function formats()
    {
        return $this->storage->fileFormats($this->relativePath());
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize([
            'path' => $this->relativePath,
            'storage' => $this->storage->name(),
            'context' => $this->storage->context()->getName(),
        ]);
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $data = @unserialize($serialized);
        if (!is_array($data) || !isset($data['path'], $data['storage'], $data['context'])) {
            throw new \InvalidArgumentException("Cannot unserialize stored source from string: '$serialized'.");
        }

        $manager = Container::getInstance()->make('filesmanager');
        /* @var $manager \Bicycle\FilesManager\Manager */
        $context = $manager->context($data['context']);

        foreach ([true, false] as $temp) {
            if ($context->storage($temp)->name() === $data['storage']) {
                $storage = $context->storage($temp);
                break;
            }
        }
        if (!isset($storage)) {
            throw new \LogicException("Storage '$data[storage]' was not found in '$data[context]' context.");
        }

        $this->storage = $storage;
        $this->relativePath = $data['path'];
    }

    /**
     * @inheritdoc
     */
    public function isStored()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function toArray()
    {
        return $this->getStorage()->context()->getToArrayConverter()->convertToArray($this);
    }

    /**
     * @inheritdoc
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return $this->getStorage()->context()->getToArrayConverter()->jsonSerialize($this);
    }
}
