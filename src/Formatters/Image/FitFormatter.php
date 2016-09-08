<?php

namespace Bicycle\FilesManager\Formatters\Image;

use Bicycle\FilesManager\Contracts;
use Bicycle\FilesManager\Exceptions;

use Intervention\Image\Image;

/**
 * Image\FitFormatter formatter fits images.
 * @see http://image.intervention.io/api/fit
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class FitFormatter extends BaseImageFormatter
{
    /**
     * The width the image will be resized to after cropping out the best fitting aspect ratio.
     * 
     * @var integer|null
     */
    protected $width;

    /**
     * The height the image will be resized to after cropping out the best fitting aspect ratio.
     * If no height is given, method will use same value as width.
     * 
     * @var integer|null
     */
    protected $height;

    /**
     * Whether it need to keep image from being upsized.
     * 
     * @var boolean
     */
    protected $upsize = false;

    /**
     * Set a position where cutout will be positioned.
     * By default the best fitting aspect ration is centered.
     * 
     * @var string The possible values are:
     *  - 'top-left'
     *  - 'top'
     *  - 'top-right'
     *  - 'left'
     *  - 'center' (default)
     *  - 'right'
     *  - 'bottom-left'
     *  - 'bottom'
     *  - 'bottom-right'
     */
    protected $position = 'center';

    /**
     * @inheritdoc
     */
    protected function init()
    {
        if (!$this->width && !$this->height) {
            throw new Exceptions\InvalidConfigException('At least one of the properties "width" or "height" must be set in "' . static::class . '".');
        } elseif (!$this->width) {
            $this->width = $this->height;
        }
        return parent::init();
    }

    /**
     * @inheritdoc
     */
    protected function processImage(Image $image, Contracts\FileSource $source)
    {
        return $image->fit($this->width, $this->height, function ($constraint) {
            /* @var $constraint \Intervention\Image\Constraint */
            if ($this->upsize) {
                $constraint->upsize();
            }
        }, $this->position);
    }
}
