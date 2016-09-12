<?php

namespace Bicycle\FilesManager\Formatters\Image;

use Bicycle\FilesManager\Contracts;
use Bicycle\FilesManager\Formatters;

use Intervention\Image\Image;
use Intervention\Image\ImageManager;

/**
 * Image\BaseImageFormatter is base formatter for all image formatters.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
abstract class BaseImageFormatter extends Formatters\AbstractFormatter
{
    /**
     * @var integer|null
     */
    protected $quality;

    /**
     * @inheritdoc
     */
    protected $defaultExtension = 'jpg';

    /**
     * @var ImageManager
     */
    private $imageManager;

    /**
     * @param Image $image
     * @return Image|string|null
     */
    abstract protected function processImage(Image $image, Contracts\FileSource $source, Contracts\Storage $storage);

    /**
     * @param ImageManager $imageManager
     * @param string $name
     * @param Contracts\Context $context
     * @param array $config
     */
    public function __construct(ImageManager $imageManager, $name, Contracts\Context $context, array $config = [])
    {
        $this->imageManager = $imageManager;
        parent::__construct($name, $context, $config);
    }

    /**
     * @inheritdoc
     */
    public function format(Contracts\FileSource $source, Contracts\Storage $storage)
    {
        $contents = $source->contents();

        try {
            $img = $this->getImageManager()->make($contents->stream());
            $result = $this->processImage($img, $source, $storage);

            if ($result instanceof Image) {
                $extension = $this->generateExtension($source);
                $tmpFile = $this->generateNewTempFilename($extension);
                $result->save($tmpFile, $this->quality);
                $result = $tmpFile;
            }
        } finally {
            $contents->close();
        }

        return $result;
    }

    /**
     * @return ImageManager
     */
    public function getImageManager()
    {
        return $this->imageManager;
    }
}
