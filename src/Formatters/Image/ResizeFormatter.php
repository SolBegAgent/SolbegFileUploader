<?php

namespace Solbeg\FilesManager\Formatters\Image;

use Intervention\Image\Image;

use Solbeg\FilesManager\Contracts;
use Solbeg\FilesManager\Exceptions;

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
     * The new width of the image.
     * 
     * @var integer
     */
    protected $width;

    /**
     * The new height of the image.
     * 
     * @var integer
     */
    protected $height;

    /**
     * Constraint the current aspect-ratio of the image.
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
        if (!$this->width && !$this->height) {
            throw new Exceptions\InvalidConfigException('At least one of the properties "width" or "height" must be set in "' . $this->getName() . '" formatter.');
        }
        return parent::init();
    }

    /**
     * @inheritdoc
     */
    protected function processImage(Image $image, Contracts\FileSource $source, Contracts\Storage $storage)
    {
        return $image->resize($this->width ?: null, $this->height ?: null, function ($constraint) {
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
