<?php

namespace Solbeg\FilesManager\Formatters\Image;

use Intervention\Image\Image;
use Intervention\Image\ImageManager;

use Solbeg\FilesManager\Contracts;
use Solbeg\FilesManager\Formatters;

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
    protected $defaultExtension = 'png';

    /**
     * @var boolean whether the image must be auto orientated by command:
     * http://image.intervention.io/api/orientate
     */
    protected $orientate = true;

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
     * @param ImageManager $image
     * @param string $name
     * @param Contracts\Context $context
     * @param array $config
     */
    public function __construct(ImageManager $image, $name, Contracts\Context $context, array $config = [])
    {
        $this->imageManager = $image;
        parent::__construct($name, $context, $config);
    }

    /**
     * @inheritdoc
     */
    public function format(Contracts\FileSource $source, Contracts\Storage $storage)
    {
        if ($this->orientate) {
            $img = $this->orientate($source);
        } else {
            $img = $this->makeImageFromSource($source);
        }

        $result = $this->processImage($img, $source, $storage);

        if ($result instanceof Image) {
            $extension = $this->generateExtension($source);
            $tmpFile = $this->generateNewTempFilename($extension);
            $result->save($tmpFile, $this->quality);
            $result = $tmpFile;
        }

        return $result;
    }

    /**
     * @param Contracts\FileSource $source
     * @return Image
     */
    public function orientate(Contracts\FileSource $source)
    {
        // It is required to save file to local file system,
        // because otherwise intervention/image exif command
        // cannot read orientation.
        $tmpPath = $this->generateNewTempFilename($source->extension());
        $contents = $source->contents();
        $handle = fopen($tmpPath, 'wb');

        try {
            stream_copy_to_stream($contents->stream(), $handle);
            return $this->getImageManager()->make($tmpPath)->orientate();
        } finally {
            $contents->close();
            @fclose($handle);
            @unlink($tmpPath);
        }
    }

    /**
     * @return ImageManager
     */
    public function getImageManager()
    {
        return $this->imageManager;
    }

    /**
     * @param Contracts\FileSource $source
     * @return Image
     */
    public function makeImageFromSource(Contracts\FileSource $source)
    {
        if (method_exists($source, 'image')) {
            return $source->image();
        }

        $contents = $source->contents();
        try {
            return $this->getImageManager()->make($contents->stream());
        } finally {
            $contents->close();
        }
    }
}
