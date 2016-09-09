<?php

namespace Bicycle\FilesManager\Formatters\Image;

use Bicycle\FilesManager\Contracts;
use Bicycle\FilesManager\Exceptions;

use Intervention\Image\Image;

/**
 * Image\ResizeFormatter formatter fits images.
 * @see http://image.intervention.io/api/resize
 * 
 * Resizes an image based on given width and/or height.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class ResizeFormatter extends BaseImageFormatter
{
    /**
     * The new width of the image (required).
     * 
     * @var integer
     */
    protected $width;

    /**
     * The new height of the image (required).
     * 
     * @var integer
     */
    protected $height;

    /**
     * Constraint the current aspect-ratio if the image.
     * 
     * @var boolean
     */
    protected $aspectRatio = true;

    /**
     * Whether it need to keep image from being upsized.
     * 
     * @var boolean
     */
    protected $upsize = false;

    /**
     * @inheritdoc
     */
    protected function init()
    {
        if (!$this->width || !$this->height) {
            throw new Exceptions\InvalidConfigException('The properties "width" and "height" must be set in "' . $this->getName() . '" formatter.');
        }
        return parent::init();
    }

    /**
     * @inheritdoc
     */
    protected function processImage(Image $image, Contracts\FileSource $source, Contracts\Storage $storage)
    {
        return $image->resize($this->width, $this->height, function ($constraint) {
            /* @var $constraint \Intervention\Image\Constraint */
            if ($this->aspectRatio) {
                $constraint->aspectRatio();
            }
            if ($this->upsize) {
                $constraint->upsize();
            }
        });
    }
}
